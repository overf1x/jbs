<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$DSOrderID = (integer) @$Args['DSOrderID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($DSOrderID){
  #-----------------------------------------------------------------------------
  $DSOrder = DB_Select('DSOrdersOwners',Array('UserID','ContractID','IP','ExtraIP','SchemeID'),Array('UNIQ','ID'=>$DSOrderID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($DSOrder)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    case 'array':
      # No more...
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}else{
  #-----------------------------------------------------------------------------
  $DSOrder = Array(
    #---------------------------------------------------------------------------
    'UserID' => 100,
    'ContractID' => 0,
    'ServerID'   => 1,
    'Domain'     => 'domain.com',
    'IP'         => '123.123.123.123',
    'ExtraIP'	 => '',
    'SchemeID'   => 1
  );
}
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
$Title = $DSOrderID?'Редактирование заказа на аренду сервера':'Добавление заказа на аренду сервера';
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
$Table = $Options = Array();
#-------------------------------------------------------------------------------
$Table[] = 'Общая информация';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Contracts/Select','ContractID',$DSOrder['ContractID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Договор клиента',$Comp);
#-------------------------------------------------------------------------------
$UniqID = UniqID('DSSchemes');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Services/Schemes','DSSchemes',$DSOrder['UserID'],Array('Name','ServersGroupID'),$UniqID);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DSSchemes = DB_Select($UniqID,Array('ID','Name','CostMonth',SPrintF('(SELECT `Name` FROM `DSServersGroups` WHERE `%s`.`ServersGroupID` = `DSServersGroups`.`ID`) as `ServersGroupName`',$UniqID)),Array('SortOn'=>'SortID'));
#-------------------------------------------------------------------------------
switch(ValueOf($DSSchemes)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('SERVERS_NOT_FOUND','Тарифы не определены');
  case 'array':
    # No more...
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
foreach($DSSchemes as $DSScheme){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Formats/Currency',$DSScheme['CostMonth']);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Options[$DSScheme['ID']] = SPrintF('%s, %s, %s',$DSScheme['Name'],$DSScheme['ServersGroupName'],$Comp);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'SchemeID'),$Options,$DSOrder['SchemeID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тарифный план',$Comp);
#-------------------------------------------------------------------------------
#$Servers = DB_Select('DSServers',Array('ID','Address'));
##-------------------------------------------------------------------------------
#switch(ValueOf($Servers)){
#  case 'error':
#    return ERROR | @Trigger_Error(500);
#  case 'exception':
#    return new gException('SERVERS_NOT_FOUND','Сервера не найдены');
#  case 'array':
#    # No more...
#  break;
#  default:
#    return ERROR | @Trigger_Error(101);
#}
##-------------------------------------------------------------------------------
#$Options = Array();
##-------------------------------------------------------------------------------
#foreach($Servers as $Server)
#  $Options[$Server['ID']] = $Server['Address'];
##-------------------------------------------------------------------------------
#$Comp = Comp_Load('Form/Select',Array('name'=>'ServerID'),$Options,$DSOrder['ServerID']);
#if(Is_Error($Comp))
#  return ERROR | @Trigger_Error(500);
##-------------------------------------------------------------------------------
#$Table[] = Array('Сервер размещения',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
	'Form/Input',
	Array(
		'type'	=> 'text',
		'name'	=> 'IP',
		'value'	=> $DSOrder['IP']
	)
);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
$Table[] = Array('IP адрес',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
	'Form/TextArea',
	Array(
		'name'  => 'ExtraIP',
		'style' => 'width:100%;',
		'rows'  => 5
	),
	$DSOrder['ExtraIP']
);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Дополнительные IP адреса'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'(вводить по одному адресу на строку)')),$Comp);
#-------------------------------------------------------------------------------
if(!$DSOrderID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type'  => 'text',
      'size'  => 5,
      'name'  => 'DaysReserved',
      'value' => 31
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array('Дней до окончания',$Comp);
  #-----------------------------------------------------------------------------
#  $Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsCreate','value'=>'yes'));
#  if(Is_Error($Comp))
#    return ERROR | @Trigger_Error(500);
#  #-----------------------------------------------------------------------------
#  $Table[] = Array('Добавить новый заказ на сервер',$Comp);
}
#-------------------------------------------------------------------------------
#$Table[] = 'Параметры доступа';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => SPrintF("FormEdit('/Administrator/API/DSOrderEdit','DSOrderEditForm','%s');",$Title),
    'value'   => ($DSOrderID?'Сохранить':'Добавить')
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
$Form = new Tag('FORM',Array('name'=>'DSOrderEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
if($DSOrderID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'DSOrderID',
      'type'  => 'hidden',
      'value' => $DSOrderID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
