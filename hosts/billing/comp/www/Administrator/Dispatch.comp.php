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
$UsersIDs = (array) @$Args['UsersIDs'];
#-------------------------------------------------------------------------------
# выбираем дефолтовую логику, в зваисимоти от того - задан ли конкретный юзер для рассылки
if(Count($UsersIDs) < 1){
  $LogicDefault = "AND";
}else{
  $LogicDefault = "OR";
}
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/AddIns'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Дополнения → Рассылка');
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/Dispatch.js}')));
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'DispatchForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Main = new Tag('TABLE');
#-------------------------------------------------------------------------------
if(Count($UsersIDs)){
  #-----------------------------------------------------------------------------
  $Array = Array();
  #-----------------------------------------------------------------------------
  foreach($UsersIDs as $UserID)
    $Array[] = (integer)$UserID;
  #-----------------------------------------------------------------------------
  $Users = DB_Select('Users',Array('ID','Name','Email'),Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$Array))));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Users)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      # No more...
    break;
    case 'array':
      #-------------------------------------------------------------------------
      $Ul = new Tag('UL',Array('class'=>'Standard'));
      #-------------------------------------------------------------------------
      $Td = new Tag('TD',Array('class'=>'Standard','colspan'=>2),new Tag('H1','Получатели:'));
      #-------------------------------------------------------------------------
      foreach($Users as $User){
        #-----------------------------------------------------------------------
        $Ul->AddChild(new Tag('LI',SPrintF('%s [%s]',$User['Name'],$User['Email'])));
        #-----------------------------------------------------------------------
        $Comp = Comp_Load(
          'Form/Input',
          Array(
            'name'  => 'UsersIDs[]',
            'type'  => 'hidden',
            'value' => $User['ID']
          )
        );
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $Form->AddChild($Comp);
      }
      #-------------------------------------------------------------------------
      $Td->AddChild($Ul);
      #-------------------------------------------------------------------------
      $Main->AddChild(new Tag('TR',$Td));
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
$Table = Array('Методы рассылки');
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Methods = $Config['Notifies']['Methods'];
#-------------------------------------------------------------------------------
$Nobody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
foreach(Array_Keys($Methods) as $MethodID){
  #-----------------------------------------------------------------------------
  $Method = $Methods[$MethodID];
  #-----------------------------------------------------------------------------
  if(!$Method['IsActive'])
    continue;
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'MethodsIDs[]',
      'id'    => $MethodID,
      'type'  => 'checkbox',
      'value' =>  $MethodID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Nobody->AddChild(new Tag('DIV',$Comp,new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>SPrintF('ChangeCheckBox(\'%s\'); return false;',$MethodID)),$Method['Name'])));
}
#-------------------------------------------------------------------------------
$Table[] = $Nobody;
#-------------------------------------------------------------------------------
$Table[] = 'Поисковые фильтры';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'Logic'),Array('AND'=>'и','OR'=>'или'),$LogicDefault);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Объединение',$Comp);
#-------------------------------------------------------------------------------
$Filters = Array();
#-------------------------------------------------------------------------------
$HostsIDs = Array_Reverse($GLOBALS['HOST_CONF']['HostsIDs']);
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Folder = SPrintF('%s/hosts/%s/comp/Dispatch',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(!File_Exists($Folder))
    continue;
  #-----------------------------------------------------------------------------
  $Files = IO_Scan($Folder);
  if(Is_Error($Files))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  foreach($Files as $File){
    #---------------------------------------------------------------------------
    $FileID = SubStr($File,0,StriPos($File,'.'));
    #---------------------------------------------------------------------------
    $Adding = Comp_Load(SPrintF('Dispatch/%s',$FileID));
    if(Is_Error($Adding))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if($Adding){
      #-------------------------------------------------------------------------
      foreach(Array_Keys($Adding) as $FilterID){
        #-----------------------------------------------------------------------
        $Filter = &$Adding[$FilterID];
        #-----------------------------------------------------------------------
        if(Is_Array($Filter))
          $Filter['Dispatch'] = $FileID;
        #-----------------------------------------------------------------------
        $Filters[Is_Array($Filter)?$FilterID:UniqID('ID')] = $Filter;
      }
    }
  }
}
#-------------------------------------------------------------------------------
$Div = new Tag('DIV',Array('style'=>'overflow:scroll;overflow-x:auto;height:400px;padding-right:5px;'));
#-------------------------------------------------------------------------------
if(Count($Filters)){
Debug(print_r($Filters,true));
  #-----------------------------------------------------------------------------
  foreach(Array_Keys($Filters) as $FilterID){
    #---------------------------------------------------------------------------
    $Filter = $Filters[$FilterID];
    #---------------------------------------------------------------------------
    if(Is_String($Filter)){
      #-------------------------------------------------------------------------
      $Div->AddChild(new Tag('DIV',Array('class'=>'Standard'),$Filter));
      #-------------------------------------------------------------------------
      continue;
    }
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        'name'  => 'FiltersIDs[]',
	'id'    => $FilterID,
        'type'  => 'checkbox',
        'value' =>  SPrintF('%s|%s',$Filter['Dispatch'],$FilterID)
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Name = Comp_Load('Formats/String',$Filter['Name'],25);
    if(Is_Error($Name))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Div->AddChild(new Tag('DIV',$Comp,new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>SPrintF('ChangeCheckBox(\'%s\'); return false;',$FilterID)),$Name)));
  }
}else
  $Div->AddChild(new Tag('SPAN','Фильтры не найдены'));
#-------------------------------------------------------------------------------
$Table[] = $Div;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr = new Tag('TR',new Tag('TD',Array('valign'=>'top'),$Comp));
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Users = DB_Select('Users',Array('ID','Name','Email'),Array('Where'=>"(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = `Users`.`GroupID`) = 'yes' OR `ID` = 100"));
#-------------------------------------------------------------------------------
switch(ValueOf($Users)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $Options = Array();
    #---------------------------------------------------------------------------
    foreach($Users as $User)
      $Options[$User['ID']] = SPrintF('%s (%s)',$User['Name'],$User['Email']);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Form/Select',Array('name'=>'FromID'),$Options,100);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('От кого',$Comp);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        'name'  => 'Theme',
        'size'  => 50,
        'type'  => 'text'
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('Тема сообщения',$Comp);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/TextArea',
      Array(
        'name'  => 'Message',
        'style' => 'width:100%',
        'rows'  => 10
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------------
    $Table[] = 'Сообщение';
    #-------------------------------------------------------------------------------
    $Table[] = $Comp;
    #-------------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        'onclick' => 'Dispatch();',
        'type'    => 'button',
        'value'   => 'Отправить'
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = $Comp;
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Tables/Standard',$Table);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Tr->AddChild(new Tag('TD',Array('valign'=>'top'),$Comp));
    #---------------------------------------------------------------------------
    $Main->AddChild($Tr);
    #---------------------------------------------------------------------------
    $Form->AddChild($Main);
    #---------------------------------------------------------------------------
    $DOM->AddChild('Into',$Form);
    #---------------------------------------------------------------------------
    $Out = $DOM->Build();
    #---------------------------------------------------------------------------
    if(Is_Error($Out))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    return $Out;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
