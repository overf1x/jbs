<?php

#-------------------------------------------------------------------------------
/** @author Rootden for Lowhosting.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task', 'Mobile', 'Message', 'UserID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Debug(SPrintF('[comp/Tasks/SMS]: отправка SMS сообщения для (%u)', $Mobile));
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Mobile;
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['SMSGateway'];
#-------------------------------------------------------------------------------
$FreeSMS = IsSet($GLOBALS['FreeSMS'])?TRUE:FALSE;
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSProvider']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSKey']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSSender']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSExceptions']['SMSExceptionsPaidInvoices']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($Settings['SMSExceptions']['SMSExceptionsSchemeID']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$User = DB_Select('Users', Array('MobileConfirmed', 'GroupID'), Array('UNIQ', 'ID' => $UserID));
if (!Is_Array($User))
    return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// Если пользователь относится к группе 'Сотрудники' то плату не взымаем...
#-------------------------------------------------------------------------------
if($User['GroupID'] == '3000000'){
    $PaymentLock = true;
    $SMSCost = 0;
}
#-------------------------------------------------------------------------------
// Проверяем пользователя на исключения оплаты, сумма оплаченных счетов.
#-------------------------------------------------------------------------------
if($Settings['SMSExceptions']['SMSExceptionsPaidInvoices'] >= 0){
	#-------------------------------------------------------------------------------
	$IsSelect = DB_Select('InvoicesOwners','SUM(`Summ`) AS `Summ`',Array('UNIQ','Where'=>SPrintF('`UserID` = %u AND `IsPosted` = "yes"',$UserID)));
	switch(ValueOf($IsSelect)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		if($IsSelect['Summ'] >= $Settings['SMSExceptions']['SMSExceptionsPaidInvoices'])
			$FreeSMS = true;
			//Debug(SPrintF('[comp/Tasks/SMS]: Оплаченных счетов (%s)', $IsSelect['Summ']));
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// Проверяем пользователя на исключения оплаты, активные заказы хостинга.
#-------------------------------------------------------------------------------
if ($Settings['SMSExceptions']['SMSExceptionsSchemeID'] != 0) {
    $OrderHostings = DB_Select('HostingOrdersOwners', 'SchemeID', Array('Where' => SPrintF('`UserID` = %u AND `StatusID` = \'Active\'', $UserID)));
    if (Is_Error($OrderHostings))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
    $LimitSchemeID = Explode(',',$Settings['SMSExceptions']['SMSExceptionsSchemeID']);
    foreach ($OrderHostings as $OrderHosting) {
	if (In_Array((integer) $OrderHosting['SchemeID'], $LimitSchemeID)) {
	    $FreeSMS = true;
	    break;
	}
    }
    //Debug(print_r($LimitSchemeID, true));
}
#-------------------------------------------------------------------------------
$MessageLength = MB_StrLen($Message);
Debug(SPrintF('[comp/Tasks/SMS]: длинна: %s, сообщение (%s)',$MessageLength,$Message));
#-------------------------------------------------------------------------------
if (Is_Error(System_Load(SPrintF('classes/%s.class.php', $Settings['SMSProvider']))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/SMS]: SMS шлюз (%s)', $Settings['SMSProvider']));
Debug(SPrintF('[comp/Tasks/SMS]: API ключ (%s)', $Settings['SMSKey']));
Debug(SPrintF('[comp/Tasks/SMS]: Отправитель (%s)', $Settings['SMSSender']));
#-------------------------------------------------------------------------------
if (!IsSet($PaymentLock)) {
    $Regulars = Regulars();
    $MobileCountry = 'SMSPriceDefault';
    $RegCountrys = array('SMSPriceRu' => $Regulars['SMSPriceRu'], 'SMSPriceUa' => $Regulars['SMSPriceUa'], 'SMSPriceSng' => $Regulars['SMSPriceSng'], 'SMSPriceZone1' => $Regulars['SMSPriceZone1'], 'SMSPriceZone2' => $Regulars['SMSPriceZone2']);
    #-------------------------------------------------------------------------------
    foreach ($RegCountrys as $RegCountryKey => $RegCountry) {
	if (Preg_Match($RegCountry, $Mobile)) {
	    $MobileCountry = $RegCountryKey;
	}
    }
    Debug(SPrintF('[comp/Tasks/SMS]: Страна определена (%s)', $MobileCountry));
    #-------------------------------------------------------------------------------
    if (!IsSet($Settings['SMSPrice'][$MobileCountry]))
	return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------------
    if($MessageLength <= 70){
	$SMSCost = Str_Replace(',', '.', $Settings['SMSPrice'][$MobileCountry]);
	$SMSCount = 1;
    }else{
	$SMSCount = Ceil($MessageLength / 67);
	$SMSCost = $SMSCount * Str_Replace(',', '.', $Settings['SMSPrice'][$MobileCountry]);
    }
    #-------------------------------------------------------------------------------
    if($FreeSMS)
	$SMSCost = 0;
    #-------------------------------------------------------------------------------
    $Comp = Comp_Load('Formats/Currency',$SMSCost);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------------
    Debug(SPrintF('[comp/Tasks/SMS]: Стоимость сообщения (%s) всего частей (%s)', $Comp, $SMSCount));
    #-------------------------------------------------------------------------------
    if (!Is_Numeric($SMSCost))
	return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------
    if ($SMSCost > 0){
    	#-------------------------------------------------------------------------------
	$Where = Array(
			SPrintF('`UserID` = %u', $UserID),
			SPrintF('`Balance` >= %s', $SMSCost),
			'`TypeID` != "NaturalPartner"',
			);
	#-------------------------------------------------------------------------------
	$Contract = DB_Select('Contracts', Array('TypeID', 'ID', 'Balance'), Array('UNIQ','Where'=>$Where,'Limits'=>Array('Start'=>0,'Length'=>1)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Contract)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		$ContractID = $Contract['ID'];
		(integer) $After = $Contract['Balance'] - $SMSCost;
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!IsSet($ContractID) && !IsSet($After)){
	    Debug("[comp/Tasks/SMS]: Недостаточно денежных средств на любом договоре клиента");
	    if ($Config['Notifies']['Methods']['SMS']['IsEvent']){
	    	#-------------------------------------------------------------------------------
		$Event = Array('UserID' => $UserID, 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Mobile, 'недостаточно денежных средств.'));
		$Event = Comp_Load('Events/EventInsert', $Event);
		if (!$Event)
		    return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	    }
	    #-------------------------------------------------------------------------------
	    if (Is_Null($Task))
		return SPrintF('Недостаточно денежных средств на балансе. Стоимость: %s',$SMSCost);
	    #-------------------------------------------------------------------------------
	    return TRUE;
	}
    }
}
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$LinkID = Md5($Settings['SMSProvider']);
#-------------------------------------------------------------------------------
if (!IsSet($Links[$LinkID])) {
    #-----------------------------------------------------------------------------
    $Links[$LinkID] = NULL;
    #-----------------------------------------------------------------------------
    $SMS = &$Links[$LinkID];
    #-----------------------------------------------------------------------------
    $SMS = new $Settings['SMSProvider']($Login = FALSE, $Settings['SMSKey'], $Settings['SMSSender']);
    if (Is_Error($SMS))
	return ERROR | @Trigger_Error(500);
    #-----------------------------------------------------------------------------
    $IsAuth = $SMS->balance();
    switch (ValueOf($IsAuth)) {
	case 'false':
	    #-------------------------------------------------------------------------
	    Debug("[comp/Tasks/SMS]: Подключаемся и получаем баланс -> Error:'".$SMS->error."'");
	    if ($Config['Notifies']['Methods']['SMS']['IsEvent']) {
		$Event = Array('UserID' => $UserID, 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Mobile, 'шлюз временно недоступен.'));
		$Event = Comp_Load('Events/EventInsert', $Event);
		if (!$Event)
		    return ERROR | @Trigger_Error(500);
	    }
	    UnSet($Links[$LinkID]);
	    #-------------------------------------------------------------------------------
	    if(Is_Null($Task))
		return "Пожалуйста, попробуйте повторить попытку позже";
	    #-------------------------------------------------------------------------
	    return TRUE;
	#-------------------------------------------------------------------------
	case 'true':
	    #-------------------------------------------------------------------------
	    Debug("[comp/Tasks/SMS]: Подключаемся и получаем баланс -> Баланс: '".$SMS->balance."'");
	    break;
	#-------------------------------------------------------------------------
	default:
	    return ERROR | @Trigger_Error(101);
    }
    // Проверим баланс и отложим задачу в случае нехватки кредитов
    #-------------------------------------------------------------------------
    $SMSBalanse = (integer) $SMS->balance;
    if ($SMSBalanse == 0 || $SMSBalanse < $SMSCost) {
	if ($Config['Notifies']['Methods']['SMS']['IsEvent']) {
	    $Event = Array('UserID' => $UserID, 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Mobile, 'временно нет средств на шлюзе.'));
	    $Event = Comp_Load('Events/EventInsert', $Event);
	    if (!$Event)
		return ERROR | @Trigger_Error(500);
	}
	#-------------------------------------------------------------------------
	if (Is_Null($Task))
	    return "Пожалуйста, попробуйте повторить попытку позже";
	#-------------------------------------------------------------------------
	UnSet($Links[$LinkID]);
	return 3600;
    }
    #-------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$SMS = &$Links[$LinkID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsMessage = $SMS->send((integer) $Mobile, $Message, $Settings['SMSSender']);
switch (ValueOf($IsMessage)) {
    case 'false':
	#-------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/SMS]: Неудачно, ошибка: "%s"',$SMS->error));
	#-------------------------------------------------------------------------------
	if ($Config['Notifies']['Methods']['SMS']['IsEvent']) {
	    $Event = Array('UserID' => $UserID, 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Mobile, 'шлюз временно недоступен.'));
	    $Event = Comp_Load('Events/EventInsert', $Event);
	    if (!$Event)
		return ERROR | @Trigger_Error(500);
	}
	#-------------------------------------------------------------------------
	if(Is_Null($Task))
	    return "Пожалуйста, попробуйте повторить попытку позже";
	#-------------------------------------------------------------------------
	UnSet($Links[$LinkID]);
	return 300;
        #-------------------------------------------------------------------------
    case 'true':
	Debug("[comp/Tasks/SMS]: Удачно, ответ шлюза:'".$SMS->success."'");
	if (!IsSet($PaymentLock) && IsSet($After)) {
	    #------------------------------TRANSACTION--------------------------
	    if (Is_Error(DB_Transaction($TransactionID = UniqID('PostingSMS'))))
		return ERROR | @Trigger_Error(500);
	    #-------------------------------------------------------------------
	    $IsUpdated = DB_Update('Contracts', Array('Balance' => $After), Array('ID' => $ContractID));
	    if (Is_Error($IsUpdated))
		return ERROR | @Trigger_Error(500);
	    #-------------------------------------------------------------------
	    $IPosting = Array(
		#-----------------------------------------------------------------
		'ContractID' => $ContractID,
		'ServiceID' => '2000',
		'Comment' => "SMS уведомление ($SMSCount шт)",
		'Before' => $Contract['Balance'],
		'After' => $After
	    );
	    #-------------------------------------------------------------------
	    $PostingID = DB_Insert('Postings', $IPosting);
	    if (Is_Error($PostingID))
		return ERROR | @Trigger_Error(500);
	    #-------------------------------------------------------------------
	    if (Is_Error(DB_Commit($TransactionID)))
		return ERROR | @Trigger_Error(500);
	    #-------------------------END TRANSACTION---------------------------
	    #-------------------------------------------------------------------------------
            $Comp = Comp_Load('Formats/Currency',$Contract['Balance']);
            if(Is_Error($Comp))
               return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------------------
            $Comp1 = Comp_Load('Formats/Currency',$After);
            if(Is_Error($Comp1))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------------------
	    Debug(SPrintF('[comp/Tasks/SMS]: Договор (%s) баланс до оплаты (%s) после оплаты (%s)', $ContractID, $Comp, $Comp1));
	}
	break;
    default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
if (!$Config['Notifies']['Methods']['SMS']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array('UserID'=>$UserID,'Text'=>SPrintF('SMS сообщение для (%s) успешно отправлено', $Mobile));
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', $Event);
#-------------------------------------------------------------------------------
if (!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
?>
