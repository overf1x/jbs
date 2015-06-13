<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
if($Template['Source']['Count'] < 1)
  return FALSE;
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$AllowConditionally = $Config['Invoices']['AllowConditionally'];
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['NotifyConditionallyInvoiceSettings'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'onclick' => "AjaxCall('/API/InvoicesReject',FormGet(form),'Отмена счетов',\"GetURL(document.location);\");",
    'type'    => 'button',
    'value'   => 'Отменить'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем, можно ли юзеру условно проводить счета
$ShowButton = TRUE;
# смотрим, разрешено ли в принципе проведение условных счетов
if($AllowConditionally){
  #-------------------------------------------------------------------------------
  # проверяем нету ли у юзера условных счетов
  $Count = DB_Count('InvoicesOwners',Array('Where'=>SPrintF("`StatusID` = 'Conditionally' AND `UserID` = %u",$GLOBALS['__USER']['ID'])));
  if($Count)
    $ShowButton = FALSE;
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  # проверяем не отрицательный ли у него балланс, на каком-либо договоре
  $Count = DB_Count('ContractsOwners',Array('Where'=>SPrintF("`Balance` < 0 AND `UserID` = %u",$GLOBALS['__USER']['ID'])));
  if($Count)
    $ShowButton = FALSE;
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  # проверяем что он наоплачивал на ту сумму, начиная с которой можно проводить счета условно
  $PayedSumm = DB_Select('InvoicesOwners',Array('SUM(Summ) AS `Summ`'),Array('UNIQ','Where'=>SPrintF("`StatusID` = 'Payed' AND `UserID` = %u",$GLOBALS['__USER']['ID'])));
  #-------------------------------------------------------------------------------
  switch(ValueOf($PayedSumm)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    break;
  default:
    return ERROR | @Trigger_Error(101);
  }
  #-------------------------------------------------------------------------------
  if($PayedSumm['Summ'] < $GLOBALS['__USER']['LayPayThreshold'])
    $ShowButton = FALSE;
  #-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($ShowButton && $AllowConditionally){
  $Comp1 = Comp_Load(
    'Form/Input',
    Array(
      'onclick' => SPrintF("javascript:ShowConfirm('Вы действительно хотите воспользоваться услугами в долг? Данный счёт всё равно придётся оплачивать, иначе услуги будут заблокированы. В случае если счёт не будет оплачен в течении %u дней то ваш балланс станет отрицательным. В этом случае, вы не сможете оплатить другие услуги до того как он не станет нулевым или положительным','AjaxCall(\'/API/InvoiceSetConditionally\',FormGet(TableSuperForm),\'Проведение условной оплаты\',\'GetURL(document.location);\');');",$Settings['DaysBeforeRejectConditionallyInvoice']),
      'type'    => 'button',
      'value'   => 'Провести условно',
      'prompt'  => SPrintF('Счёт зачисляется "условно". В течение %u дней вы должны его оплатить. Услугами вы сможете пользоваться сейчас.',$Settings['DaysBeforeRejectConditionallyInvoice'])
    )
  );
  if(Is_Error($Comp1))
    return ERROR | @Trigger_Error(500);
 }else{
   $Comp1 = ' ';
 }
#-------------------------------------------------------------------------------
$Span = new Tag('SPAN',$Comp,$Comp1);
#-------------------------------------------------------------------------------
$Table = Array(Array('Выбранные счета',$Span));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Comp;
#-------------------------------------------------------------------------------

?>
