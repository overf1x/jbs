<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task', 'Mobile', 'Message', 'ID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Debug(SPrintF('[comp/Tasks/SMS]: отправка SMS сообщения для (%u)', $Mobile));
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Mobile;
#-------------------------------------------------------------------------------
$Config = Config();

if (!Isset($Config['Notifies']['Methods']['SMS']['Provider'])) {
    return ERROR | @Trigger_Error(500);
}

$SMSProvider = $Config['Notifies']['Methods']['SMS']['Provider'];

Debug(SPrintF('[comp/Tasks/SMS]: провайдер (%s)', $SMSProvider));

if (Is_Error(System_Load(SPrintF('classes/%s.class.php', $SMSProvider))))
    return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Settings = $Config[$SMSProvider];
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$LinkID = Md5($SMSProvider);
    Debug(print_r("111", true));
#-------------------------------------------------------------------------------
if (!IsSet($Links[$LinkID])) {
    #-----------------------------------------------------------------------------
    $Links[$LinkID] = NULL;
    #-----------------------------------------------------------------------------
    $SMS = &$Links[$LinkID];
    #-----------------------------------------------------------------------------
    $SMS = new $SMSProvider();
    if (Is_Error($SMS))
        return ERROR | @Trigger_Error(500);
}
#-------------------------------------------------------------------------------
$SMS = &$Links[$LinkID];
#-------------------------------------------------------------------------------
$Message = Mb_Convert_Encoding($Message, $Settings['Charset']);
#-------------------------------------------------------------------------------
$IsMessage = $SMS->sendSms($Mobile, $Message);
if (Is_Error($IsMessage)) {
    #-----------------------------------------------------------------------------
    UnSet($Links[$LinkID]);
    #-----------------------------------------------------------------------------
    Debug("[comp/Tasks/SMS]: error sending message, error is '" . $IsMessage->error . "'");
    #-----------------------------------------------------------------------------
    return 3600;
}
#-------------------------------------------------------------------------------
$Event = Array(
    'UserID' => $ID,
    'Text' => SPrintF('Сообщение для (%s) через службу %s успешно отправлено', $Mobile, $SMSProvider)
);

$Event = Comp_Load('Events/EventInsert', $Event);
if (!$Event)
    return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
