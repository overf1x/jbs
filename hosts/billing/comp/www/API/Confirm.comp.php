<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$Method		= (string) @$Args['Method'];	// метод оповещения
$Value		= (string) @$Args['Value'];	// контактный адрес пользователя
$ContactID	= (integer)@$Args['ContactID'];	// идентификатор контакта в БД
$Code		= (string) @$Args['Code'];	//
$Confirm	= (string) @$Args['Confirm'];	//
$UserID		= (integer)@$Args['UserID'];	// идентифкатор юзера, для автоматической отсылки подтверждения при регистрации
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Server.php','modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
// инициализируюем юзера, если передан UserID и это происходит от системного юзера
if($GLOBALS['__USER']['ID'] == 100 && $UserID){
	#-------------------------------------------------------------------------------
	$Init = Comp_Load('Users/Init',$UserID);
	if(Is_Error($Init))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
if(!In_Array($Method,Array_Keys($Config['Notifies']['Methods'])))
	return new gException('WRONG_CONTACT_ADDRESS','Несуществующий способ оповещения');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods'][$Method]['IsActive'])
	return new gException('WRONG_CONTACT_ADDRESS','Данный способ оповещения отключен администратором');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars[$Method],$Value))
	return new gException('WRONG_CONTACT_ADDRESS','Неверно указан адрес');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// проверяем существование адрреса у этого юзера
foreach($__USER['Contacts'] as $iContact)
	if($iContact['ID'] == $ContactID)
		if($iContact['UserID'] == $__USER['ID'])
			if($iContact['MethodID'] == $Method)
				$Contact = $iContact;
#-------------------------------------------------------------------------------
if(!IsSet($Contact))
	return new gException('CONTACT_NOT_FOUND',SPrintF('Контакт с указанными параметрами не найден'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// делаем круглосуточную отправку для этого контакта - на случай подтверждения
$Contact['TimeBegin']	= 0;
$Contact['TimeEnd']	= 0;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// и не подтверждён ли он уже
if($Contact['Confirmed']){
	#-------------------------------------------------------------------------------
	return new gException('ALREADY_CONFIRMED','Уже подтверждено');
	#-------------------------------------------------------------------------------
	Header(SPrintF('Location: /Home'));
	#-------------------------------------------------------------------------------
	return NULL;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Server = SelectServerSettingsByTemplate($Method);
#-------------------------------------------------------------------------------
switch(ValueOf($Server)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	if($Method != 'Email')
		return $Server;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['User']['Notes'][$Method];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Cache = SPrintF('li-%s-%s-%s',$Method,$Value,$__USER['ID']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Confirm && !$Code){
	#-------------------------------------------------------------------------------
	# возможный вариант: контакта не было, или был другой - а юзер его ввёл и не сохраняя нажал "подтвердить"
	if($Contact['Address'] != $Value)
		return new gException('INFORMATION_NOT_SAVED', 'Для подтверждения, вначале сохраните настройки с введёнными данными');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// Защита от агрессивно настроенных, любителей долбить кнопку раз за разом
	$Result = CacheManager::get($Cache);
	#-------------------------------------------------------------------------------
	if($Result){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Date/Remainder',$Settings['ConfirmInterval']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		return new gException('INTERVAL_NOT_EXPIRED', SPrintF("Вы уже отправили сообщение с кодом подтверждения. Новое сообщение вы сможете отправить только через %s",$Comp));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// телеграмм, он особенный, с логикой наоборот...
	if(In_Array($Method,Array('Telegram','Viber','VKontakte'))){
		#-------------------------------------------------------------------------------
		// генерим 2 раза по 3 цифры, чтоб проще было клиенту...
		$C1 = Comp_Load('Passwords/Generator',3,TRUE);
		if(Is_Error($C1))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$C2 = Comp_Load('Passwords/Generator',3,TRUE);
		if(Is_Error($C2))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		// проверяем что в базе нет такого значения
		$Count = DB_Count('Contacts',Array('Where'=>SPrintF("`Confirmation` = %u%u",$C1,$C2)));
		if(Is_Error($Count))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if($Count)
			return new gException('CONFIRM_CODE_EXISTS','Что-то пошло не так, нажмите кнопку "Подтвердить" ещё раз ...');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		// пихаем значения в базу
		$IsUpdate = DB_Update('Contacts',Array('Confirmation'=>SPrintF('%s%s',$C1,$C2)),Array('ID'=>$ContactID));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		// Выводим юзеру инструкцию
		return new gException('SHOW_TELEGRAMM_INSTRUCTIONS',SPrintF($Server['Params']['ConfirmInstructions'],SPrintF('%s-%s',$C1,$C2),$Server['Params']['BotName']));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$ConfirmShort = Comp_Load('Passwords/Generator',4,TRUE);
	if(Is_Error($ConfirmShort))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$ConfirmLong = Comp_Load('Passwords/Generator');
	if(Is_Error($ConfirmLong))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# строка подтверждения будет содержать сразу оба подтверждения - и короткое и длинное
	$IsUpdate = DB_Update('Contacts',Array('Confirmation'=>SPrintF('%s/%s',$ConfirmShort,$ConfirmLong)),Array('ID'=>$ContactID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/API/Confirm]: ConfirmShort = %s; ConfirmLong = %s;',$ConfirmShort,$ConfirmLong));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Executor = DB_Select('Users',Array('Sign','Email','Name'),Array('UNIQ','ID'=>100));
	if(!Is_Array($Executor))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# сообщение для SMS и часть остальных вариантов оповещения
	$MessageSmall = SPrintF('Ваш проверочный код: %s',$ConfirmShort);
	#-------------------------------------------------------------------------------
	$MessageBig = "%s\r\n\r\nДля подтверждения вашего контактного адреса, вы можете пройти по этой ссылке:\r\n%s\r\nЕсли ссылка не открывается, то скопируйте и вставьте её в адресную строку браузера";
	#-------------------------------------------------------------------------------
	$Url = SPrintF('http://%s/API/Confirm?Method=%s&ContactID=%u&Value=%s&Code=%s/%s',HOST_ID,$Method,$ContactID,$Value,$ConfirmShort,$ConfirmLong);
	#-------------------------------------------------------------------------------
	$MessageBig = SPrintF($MessageBig,$MessageSmall,$Url);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Theme = SPrintF('Подтверждение %s адреса',$Config['Notifies']['Methods'][$Method]['Name']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(SPrintF('Tasks/%s',$Method),NULL,$Value,($Config['Notifies']['Methods'][$Method]['MessageTemplate'] == 'Small')?$MessageSmall:$MessageBig,Array('From'=>$Executor,'UserName'=>$__USER['Name'],'UserID'=>$__USER['ID'],'Theme'=>$Theme,'Contact'=>$Contact,'ChargeFree'=>TRUE));
	if(Is_Error($Comp))
		return new gException('ERROR_MESSAGE_SEND','Не удалось отправить сообщение');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// обнуляем что адерс подтверждён, раз запрошено подтверждение
	$IsUpdate = DB_Update('Contacts',Array('Confirmed'=>0),Array('ID'=>$ContactID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	CacheManager::add($Cache,Time(),IntVal($Settings['ConfirmInterval']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return Array('Status' => 'Ok');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	if(Empty($Confirm) && Empty($Code))
		return new gException('ERROR_CODE_EMPTY', 'Введите полученный код подтверждения, или пройдите по ссылке из сообщения');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// нашёлся-таки альтернативно одарённый, закрыл окно с кодом, и ввёл его в эту дырку...
	// метод подтверждён, а chat_id так и остался неизвестен =)
	if(In_Array($Method,Array('Telegram','Viber','VKontakte')))
		return new gException('BAD_MESSENGER_CONFIRMATION_METHOD',SPrintF('Код подтверждения необходимо отправить боту "%s"',$Server['Params']['BotName']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!$Contact['Confirmation']){
		#-------------------------------------------------------------------------------
		return new gException('NO_CONFIRM_CODE','В базе отсутствует код подтверждения');
		#-------------------------------------------------------------------------------
		Header(SPrintF('Location: /Home'));
		#-------------------------------------------------------------------------------
		return NULL;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# достаём короткий код подвтерждения
	$Array = Explode("/", $Contact['Confirmation']);
	#-------------------------------------------------------------------------------
	if($Confirm)
		if($Confirm != $Array[0])
			return new gException('BAD_CONFIRM_CODE', 'Введён неверный код, попробуйте подтвердить ещё раз');
	#-------------------------------------------------------------------------------
	if($Code){
		#-------------------------------------------------------------------------------
		# если подтверждение через код в ссылке
		$DOM = new DOM();
		#-------------------------------------------------------------------------------
		$Links = &Links();
		# Коллекция ссылок
		$Links['DOM'] = &$DOM;
		#-------------------------------------------------------------------------------
		if(Is_Error($DOM->Load('Base')))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$DOM->AddText('Title','Подтверждение контактного адреса');
		#-------------------------------------------------------------------------------
		$NoBody = new Tag('NOBODY');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if($Code != $Contact['Confirmation']){
			#-------------------------------------------------------------------------------
			$DOM->AddAttribs('Body',Array('onload'=>"ShowAlert('Ссылка устарела, попробуйте подтвердить ещё раз','Warning');location.href = '/Home';"));
			#-------------------------------------------------------------------------------
			$DOM->AddChild('Into',$NoBody);
			#-------------------------------------------------------------------------------
			$Out = $DOM->Build();
			#-------------------------------------------------------------------------------
			if(Is_Error($Out))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			return $Out;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Contacts',Array('Confirmed'=>Time(),'Confirmation'=>'','IsActive'=>TRUE),Array('ID'=>$ContactID));
	if (Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# TODO исправляем юзера - проверить что это реально надо, и надо тут
	$Comp = Comp_Load('Tasks/RecoveryUsers',NULL,$__USER['ID']);
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Settings['SettingsReset']){
		#-------------------------------------------------------------------------------
		// Отключаем все уведомления в настройках
		$Notifies = $Config['Notifies'];
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Notifies['Types']) as $TypeID){
			#-------------------------------------------------------------------------------
			$Count = DB_Count('Notifies',Array('Where'=>SPrintF("`ContactID` = %u",$ContactID)));
			if(Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if(!$Count){
				#-------------------------------------------------------------------------------
				$INotify = Array('ContactID'=>$ContactID,'TypeID'=>$TypeID);
				#-------------------------------------------------------------------------------
				$IsInsert = DB_Insert('Notifies', $INotify);
				if(Is_Error($IsInsert))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Event = Array('UserID'=>$__USER['ID'],'PriorityID'=>'Billing','Text'=>SPrintF('Контактный адрес (%s) подтверждён через "%s"',$Contact['Address'],$Config['Notifies']['Methods'][$Method]['Name']));
	#-------------------------------------------------------------------------------
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# если подтверждали цифирками через интерфейс
	if($Confirm)
		return Array('Status' => 'Ok');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# если не вариант выше - значит подтверждение через код в ссылке
	$DOM->AddAttribs('Body',Array('onload'=>"ShowAlert('Контактный адрес подтверждён'); location.href = '/Home';"));
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Into',$NoBody);
	#-------------------------------------------------------------------------------
	$Out = $DOM->Build();
	#-------------------------------------------------------------------------------
	if(Is_Error($Out))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Out;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status' => 'Error');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
