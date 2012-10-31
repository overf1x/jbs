<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$BonusID        = (integer) @$Args['BonusID'];
$UserID         = (integer) @$Args['UserID'];
$ServiceID	= (integer) @$Args['ServiceID'];
$SchemeID       = (integer) @$Args['SchemeID'];
$ExpirationDate = (integer) @$Args['ExpirationDate'];
$DaysReserved   = (integer) @$Args['DaysReserved'];
$DaysRemainded  = (integer) @$Args['DaysRemainded'];
$Discont        = (integer) @$Args['Discont'];
$Comment        =  (string) @$Args['Comment'];
#-------------------------------------------------------------------------------
if($Discont < 5 || $Discont > 100)
  return new gException('WRONG_DISCOUNT','Скидка должна принимать значение от 5 до 100');
#-------------------------------------------------------------------------------
$IBonus = Array(
  #-----------------------------------------------------------------------------
  'UserID'        => $UserID,
  'ServiceID'     => ($ServiceID?$ServiceID:NULL),
  'SchemeID'      => ($SchemeID?$SchemeID:NULL),
  'ExpirationDate'=> $ExpirationDate,
  'DaysReserved'  => $DaysReserved,
  'DaysRemainded' => $DaysRemainded,
  'Discont'       => $Discont/100,
  'Comment'       => $Comment
);
#-------------------------------------------------------------------------------
if($BonusID){
  #-----------------------------------------------------------------------------
  $IsUpdate = DB_Update('Bonuses',$IBonus,Array('ID'=>$BonusID));
  if(Is_Error($IsUpdate))
    return ERROR | @Trigger_Error(500);
}else{
  #-----------------------------------------------------------------------------
  $IsInsert = DB_Insert('Bonuses',$IBonus);
  if(Is_Error($IsInsert))
    return ERROR | @Trigger_Error(500);
}
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------

?>
