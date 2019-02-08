<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Rows');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Table = new Tag('TABLE',Array('class'=>'Standard'));
#-------------------------------------------------------------------------------
for($i=1;$i<Count($__args__);$i++){
  #-----------------------------------------------------------------------------
  $__arg__ = $__args__[$i];
  #-----------------------------------------------------------------------------
  if(Is_Array($__arg__)){
    if(Array_Key_Exists('cellspacing',$__arg__))
      $CellSpacing = 1;
    #-----------------------------------------------------------------------------
    $Table->AddAttribs($__arg__);
  }else{
    $Table->AddChild(new Tag('CAPTION',$__arg__));
  }
}
#-------------------------------------------------------------------------------
if(!IsSet($CellSpacing))
  $Table->AddAttribs(Array('cellspacing'=>5));
#-------------------------------------------------------------------------------
$Max = 1;
#-------------------------------------------------------------------------------
foreach($Rows as $Row){
  #-----------------------------------------------------------------------------
  $Count = Is_Array($Row)?Count($Row):0;
  #-----------------------------------------------------------------------------
  if($Count > $Max)
    $Max = $Count;
}
#-------------------------------------------------------------------------------
foreach($Rows as $Row){
  #-----------------------------------------------------------------------------
  $Tr = new Tag('TR');
  #-----------------------------------------------------------------------------
  switch(ValueOf($Row)){
    case 'array':
      #-------------------------------------------------------------------------
      foreach($Row as $Column){
        #-----------------------------------------------------------------------
        if(Is_Scalar($Column)){
          #---------------------------------------------------------------------
          $Attribs = Array('class'=>'Standard');
          #---------------------------------------------------------------------
          if(!Is_String($Column))
            $Attribs['align'] = 'right';
          #---------------------------------------------------------------------
          $Column = new Tag('TD',$Attribs,$Column);
        }
        #-----------------------------------------------------------------------
        $Tr->AddChild($Column);
      }
    break;
    case 'object':
      #-------------------------------------------------------------------------
      if($Row->Name != 'TR')
        $Tr->AddChild(new Tag('TD',Array('colspan'=>$Max,'align'=>'right'),$Row));
      else
        $Tr = $Row;
    break;
    default:
      #-------------------------------------------------------------------------
      $Tr->AddChild(new Tag('TD',Array('colspan'=>$Max,'class'=>'Separator'),$Row));
  }
  #-----------------------------------------------------------------------------
  $Table->AddChild($Tr);
}
#-------------------------------------------------------------------------------
return $Table;
#-------------------------------------------------------------------------------

?>
