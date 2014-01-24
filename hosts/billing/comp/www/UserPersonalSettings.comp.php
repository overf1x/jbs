<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Upload.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Персональные настройки');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Settings = $__USER['Params'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array('Настройки интерфейса, "Система поддержки пользователей" (Тикеты)');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'EdesksDisplay','prompt'=>'Как отображать в системе поддержки аватары и текст'),Array('Right'=>'Правое','Left'=>'Левое'),IsSet($_COOKIE['EdesksDisplay'])?$_COOKIE['EdesksDisplay']:'Left');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Положение текста в тикете',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'EdeskNoPreview','prompt'=>'Не отображать эскизы картинок в окне просмотра тикета'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(IsSet($_COOKIE['EdeskNoPreview']))
	$Comp->AddAttribs(Array('checked'=>'true'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'EdeskNoPreview\'); return false;'),'Не отображать миниатюры изображений'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'NotSendEdeskFilesToEmail','prompt'=>'Не присылать прикрепленные к тикету файлы на почту'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(IsSet($Settings['NotSendEdeskFilesToEmail']) && $Settings['NotSendEdeskFilesToEmail'])
	$Comp->AddAttribs(Array('checked'=>'true'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'NotSendEdeskFilesToEmail\'); return false;'),'Не присылать вложения к тикетам на почту'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($__USER['IsAdmin']){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'EdeskOnlyMyButtons','prompt'=>'Не показывать кнопки относящиеся ко всем сотрудникам, показывать только свои'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if(IsSet($_COOKIE['EdeskOnlyMyButtons']))
		$Comp->AddAttribs(Array('checked'=>'true'));
	#-------------------------------------------------------------------------------
	$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'EdeskOnlyMyButtons\'); return false;'),'Показывать только персональные кнопки'),$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Настройки автоматической выписки счетов';
#-------------------------------------------------------------------------------
# достаём список платтёжных систем
$Contracts = DB_Select('Contracts',Array('TypeID'),Array('Where'=>SPrintF('`UserID` = %u',$GLOBALS['__USER']['ID'])));
switch(ValueOf($Contracts)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	$Array = Array('Natural');
	break;
case 'array':
	$Array = Array();
	foreach($Contracts as $Contract)
		$Array[] = $Contract['TypeID'];
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$ContractsTypes = $Config['Contracts']['Types'];
#-------------------------------------------------------------------------------
$Rows = Array();
#-------------------------------------------------------------------------------
$Js1 = Array();
$Js2 = Array();
#-------------------------------------------------------------------------------
foreach(Array_Keys($ContractsTypes) as $Type){
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/UserPersonalSettings]: Contracts = %s',print_r($Contracts,true)));
	#-------------------------------------------------------------------------------
	if(!$ContractsTypes[$Type]['IsActive'] || $Type == 'NaturalPartner' || !In_Array($Type,$Array))
		continue;
	#-------------------------------------------------------------------------------
	$PaymentSystems = $Config['Invoices']['PaymentSystems'];
	#-------------------------------------------------------------------------------
	$Options = Array();
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($PaymentSystems) as $PaymentSystemID){
		#-------------------------------------------------------------------------------
		$PaymentSystem = $PaymentSystems[$PaymentSystemID];
		#-------------------------------------------------------------------------------
		if(!$PaymentSystem['IsActive'])
			continue;
		#-------------------------------------------------------------------------------
		$Options[$PaymentSystemID] = $PaymentSystem['Name'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(Count($Options)){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Select',Array('name'=>SPrintF('CreateInvoicesAutomatically[%s]',$Type),'id'=>$Type,'size'=>1),$Options,IsSet($Settings['CreateInvoicesAutomatically'][$Type])?$Settings['CreateInvoicesAutomatically'][$Type]:$Type);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if(IsSet($Settings['NotCreateInvoicesAutomatically']) && $Settings['NotCreateInvoicesAutomatically'])
			$Comp->AddAttribs(Array('disabled'=>'true'));
		#-------------------------------------------------------------------------------
		# добавим после чекбокса, так логичней
		$Rows[] = Array(SPrintF('Выписывать счета для "%s" через',$ContractsTypes[$Type]['Name']),$Comp);
		# куски JS
		$Js1[] = SPrintF("form.%s.disabled = checked",$Type);
		$Js2[] = SPrintF("document.getElementById('%s').disabled = document.getElementById('NotCreateInvoicesAutomatically').checked?true:false;",$Type);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'NotCreateInvoicesAutomatically','id'=>'NotCreateInvoicesAutomatically','prompt'=>'Не выписывать счета на продление услуг в автоматическом режиме. Также, регулируется настройками автопродления - счета выписываются только при включенном автопродлении услуги','onclick'=>Implode(';',$Js1)));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(IsSet($Settings['NotCreateInvoicesAutomatically']) && $Settings['NotCreateInvoicesAutomatically'])
	$Comp->AddAttribs(Array('checked'=>'true'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>SPrintF("ChangeCheckBox('NotCreateInvoicesAutomatically'); %s; return false;",Implode(';',$Js2))),'Не выписывать счета автоматически'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($Rows as $Row)
	$Table[] = $Row;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Настройки SMS рассылок';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$SMSTime = Array();
for($i = 0; $i <= 23; $i++)
	$SMSTime[$i] = SPrintF('%02d:00',$i);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'SMSBeginTime','prompt'=>SPrintF('Время (%s) начала рассылки SMS сообщений',Date('T'))),$SMSTime,(IsSet($Settings['SMSTime']['SMSBeginTime'])?$Settings['SMSTime']['SMSBeginTime']:0));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Время начала рассылки',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'SMSEndTime','prompt'=>SPrintF('Время (%s) окончания рассылки SMS сообщений',Date('T'))),$SMSTime,(IsSet($Settings['SMSTime']['SMSEndTime'])?$Settings['SMSTime']['SMSEndTime']:0));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Время окончания рассылки',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => "FormEdit('/API/UserPersonalSettings','UserPersonalSettingsForm','Сохранение ваших персональных настроек');",
    'value'   => 'Сохранить'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','User/Settings',new Tag('FORM',Array('name'=>'UserPersonalSettingsForm','onsubmit'=>'return false;'),$Comp));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
