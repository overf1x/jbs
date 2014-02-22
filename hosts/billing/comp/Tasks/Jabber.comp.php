<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task', 'JabberID', 'Message', 'ID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#if(!$Theme)
$Theme = 'message theme is empty';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/Jabber]: отправка Jabber сообщения для (%s)', $JabberID));
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $JabberID;
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/JabberClient.class.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Jabber');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: Jabber, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $Settings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links = &Links();
$LinkID = Md5('JabberClient');
#-------------------------------------------------------------------------------
if (!IsSet($Links[$LinkID])) {
    $Links[$LinkID] = NULL;
    $JabberClient = &$Links[$LinkID];

    $JabberClient = new JabberClient(
        $Settings['Address'],
        $Settings['Port'],
        $Settings['Login'],
        $Settings['Password'],
        ($Settings['Protocol'] == 'ssl')?TRUE:FALSE
    );

    // TODO тут надо переделать, ошибки из функций не вернутся
    Debug(SPrintF('[comp/Tasks/Jabber]: %s',$JabberClient->get_log()));
    if (Is_Error($JabberClient))
        return ERROR | @Trigger_Error(500);

    $IsConnect = $JabberClient->connect();
    Debug(SPrintF('[comp/Tasks/Jabber]: %s',$JabberClient->get_log()));
    if (Is_Error($IsConnect))
        return ERROR | @Trigger_Error(500);

    $IsLogin = $JabberClient->login();
    Debug(SPrintF('[comp/Tasks/Jabber]: %s',$JabberClient->get_log()));
    if (Is_Error($IsLogin))
        return ERROR | @Trigger_Error(500);
}
#-------------------------------------------------------------------------------
$JabberClient = &$Links[$LinkID];
#-------------------------------------------------------------------------------
$IsMessage = $JabberClient->send_message($JabberID, $Message, $Theme);
if(Is_Error($IsMessage)){
    UnSet($Links[$LinkID]);
    Debug(SPrintF('[comp/Tasks/Jabber]: error sending message, error is "%s"',$JabberClient->get_log()));
    return 3600;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Jabber']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array(
	'UserID'=> $ID,
	'Text'	=> SPrintF('Сообщение для (%s) через службу Jabber успешно отправлено', $JabberID)
);
$Event = Comp_Load('Events/EventInsert', $Event);
#-------------------------------------------------------------------------------
if (!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
