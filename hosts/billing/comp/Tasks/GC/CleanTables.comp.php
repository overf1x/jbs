<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['CleanTablesSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# зачищаем таблицу задач
$Where = Array(
		SPrintF('`ExecuteDate` < UNIX_TIMESTAMP() - %u',$Settings['TableTasksStoryPeriod'] * 24 * 3600),
		'`UserID` != 1','`TypeID` != "Dispatch"'
		);
#-------------------------------------------------------------------------------
$IsDelete = DB_Delete('Tasks',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);

#-------------------------------------------------------------------------------
Sleep(1);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# костыль к рассыльщику SMS
$Where = Array(
		'`CreateDate` < UNIX_TIMESTAMP() - 24 * 3600',
		"`TypeID` = 'SMS'"
		);
#-------------------------------------------------------------------------------
$IsDelete = DB_Delete('Tasks',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Sleep(1);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# зачищаем таблицу ServersUpTime
$Where = SPrintF('`TestDate` < UNIX_TIMESTAMP() - %u',$Settings['TableServersUpTimeStoryPeriod'] * 24 * 3600);
#-------------------------------------------------------------------------------
$IsDelete = DB_Delete('ServersUpTime',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
Sleep(1);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# зачищаем таблицу RequestLog
$Where = SPrintF('`CreateDate` < UNIX_TIMESTAMP() - %u',$Settings['TableRequestLogStoryPeriod'] * 24 * 3600);
#-------------------------------------------------------------------------------
$IsDelete = DB_Delete('RequestLog',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
Sleep(1);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara, 2011-12-27 in 14:09 MSK, for JBS-232
# проставляем тикеты как оповещённые, если больше недели прошло
$IsUpdate = DB_Update('EdesksMessages',Array('IsNotify'=>'yes'),Array('Where'=>SPrintF('`CreateDate` < %u',(Time() - 7*24*3600))));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
Sleep(1);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara 2012-09-28 in 13:54 MSK, for JBS-377
$Where = '(SELECT `ID` FROM `Users` WHERE `Events`.`UserID`=`Users`.`ID`) IS NULL';
#--------------------------------------------------------------------------------
$IsDelete = DB_Delete('Events',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
Sleep(1);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# Удаляем бонусы c датой окончания меньше текущей даты ... и года, например
$IsDelete = DB_Delete('Bonuses',Array('Where'=>'`ExpirationDate` < UNIX_TIMESTAMP() - 365*24*60*60'));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
Sleep(1);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# Удаляем бонусы c датой окончания меньше текущей даты ... и года, например
$IsDelete = DB_Delete('Bonuses',Array('Where'=>'`DaysRemainded` = 0 AND `CreateDate` < UNIX_TIMESTAMP() - 365*24*60*60'));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
Sleep(1);
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara for JBS-783
# искуственно ограничиваем запрос, иначе может быть слишком большая выборка.
# а так, за месяц, гарантированно переберутся все дни, и все лишние записи удалятся
$Where = 'DAYOFMONTH(FROM_UNIXTIME(`StatusDate`)) = DAYOFMONTH(FROM_UNIXTIME(UNIX_TIMESTAMP()))';
#--------------------------------------------------------------------------------
$StatusesHistory = DB_Select('StatusesHistory',Array('ID','ModeID','RowID'),Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($StatusesHistory)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#--------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Tasks/GC/CleanTables]: StatusesHistory = %s',print_r($StatusesHistory,true)));
	#--------------------------------------------------------------------------------
	$Keys = Array();
	#--------------------------------------------------------------------------------
	foreach($StatusesHistory as $History){
		#--------------------------------------------------------------------------------
		# перебираем только уникальные значения
		$Key = SPrintF('%s-%s',$History['ModeID'],$History['RowID']);
		#--------------------------------------------------------------------------------
		if(In_Array($Key,$Keys))
			continue;
		#--------------------------------------------------------------------------------
		#Debug(SPrintF('[comp/Tasks/GC/CleanTables]: ID = %s, ModeID = %s, RowID = %s',$History['ID'],$History['ModeID'],$History['RowID']));
		#--------------------------------------------------------------------------------
		$Keys[] = $Key;
		#--------------------------------------------------------------------------------
		#--------------------------------------------------------------------------------
		$Row = DB_Select($History['ModeID'],Array('ID'),Array('UNIQ','Where'=>SPrintF("`ID` = %u",$History['RowID'])));
		#--------------------------------------------------------------------------------
		switch(ValueOf($Row)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			#--------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/GC/CleanTables]: orphaned row ID = %s, ModeID = %s, RowID = %s',$History['ID'],$History['ModeID'],$History['RowID']));
			#--------------------------------------------------------------------------------
			$IsDelete = DB_Delete('StatusesHistory',Array('Where'=>SPrintF('`ModeID` = "%s" AND `RowID` = %u',$History['ModeID'],$History['RowID'])));
			if(Is_Error($IsDelete))
				return ERROR | @Trigger_Error(500);
			#--------------------------------------------------------------------------------
			break;
			#--------------------------------------------------------------------------------
		case 'array':
			#--------------------------------------------------------------------------------
			break;
			#--------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#--------------------------------------------------------------------------------
	}
	#--------------------------------------------------------------------------------
	break;
	#--------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# JBS-1116: чистим таблицу событий
$Garbages = Array(
		'Пользователь вошел в систему %',
		'Сообщение для %',
		'Пользователь вышел %',
		'Не удалость автоматически оплатить %',
		'Добавлено новое сообщение %',
		'Владелец для заказа %',
		'Создан новый запрос %',
		'Автоматическая отмена заказа %',
		'Отмененный заказ %',
		'SMS сообщение для %',
		'Не удалось отправить SMS сообщение %',
		'Замена основного сервера группы %',
		'Задание % вернуло ошибку выполнения',
		'Уведомление о условно оплаченном счете %',
		'Уведомление о условно оплаченном счёте %',
		'Создан запрос в службу поддержки %',
		'Задание % не может быть выполнено в автоматическом режиме',
		'Удалено сообщение %',
		'Найден %/% отсутствующий в биллинге',
		'Почтовый адрес (%) успешно подтверждён',
		'Зарегистрирован новый пользователь %',
		'Сформирован новый договор %',
		'Отключено автопродление для заказа %',
		'Контактный адрес (%) подтверждён через %',
		'Не удалость автоматически оплатить заказ %',
		'Автоматическое списание денег (%) у неактивного пользователя',
		'Автоматическое списание средств (%) у неактивного пользователя',
		'Соотрудником % удалён пользователь %',
		'Удалён пользователь (%) не заходивший в биллинг % дней',
		'Доменная зона "%" не обнаружена в базе данных WhoIs%',
		'%/%: цена % изменена %',
		'Ошибка опроса сервера %',
		'Не удалось сменить именные сервера заказу домена %',
		'Не удалось сменить тарифный план заказу хостинга %',
		'Уведомление о неоплаченном счёте %',
		'Автоматическая отмена счёта %',
		'Выписан счёт % по договору (%), платежная система %',
		'Отменённый счёт % автоматически удалён%',
		'Удалено почтовое сообщение с нецензурной лексикой %',
		'Промокод (%s) успешно активирован',
		'Запущена миграция виртуальной машины %',
		'Миграция виртуальной машины %',
		'Счет % успешно оплачен',
		'Именные сервера для заказа домена (%s) успешно изменены',
		'Заказ домена % не найден у регистратора %',
		'Оплачен счёт %, на сумму %, платежная система %',
		'Отмененный % автоматически удален',
		'Заказ на прокси-сервер %, тариф % удален'
		);
#--------------------------------------------------------------------------------
foreach($Garbages as $Garbage){
	#--------------------------------------------------------------------------------
	$IsQuery = DB_Query(SPrintF("DELETE FROM `Events` WHERE `Text` LIKE '%s' AND `CreateDate` < UNIX_TIMESTAMP() - 30*24*60*60 /* старше 30 дней */;",$Garbage));
	if(Is_Error($IsQuery))
		return ERROR | @Trigger_Error(500);
	#--------------------------------------------------------------------------------
	Sleep(1);
	#--------------------------------------------------------------------------------
}
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
return TRUE;
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------

?>
