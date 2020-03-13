<?php


#-------------------------------------------------------------------------------
/** @author Лапшин С.М. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsCreate','Folder','StartDate','FinishDate','Details');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Artichow.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result = Array('Title'=>'Распределение заказов на IP адреса по времени');
#-------------------------------------------------------------------------------
$MonthsNames = Array('Декабрь','Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь');
#-------------------------------------------------------------------------------
if(!$IsCreate)
  return $Result;
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('P','Данный вид статистики содержит информацию о количестве заказов в указанный период времени.'));
#-------------------------------------------------------------------------------
$Where = SPrintF('`OrderDate` >= %u AND `OrderDate` <= %u',$StartDate,$FinishDate);
#-------------------------------------------------------------------------------
if(In_Array('ByDays',$Details)){
  #-----------------------------------------------------------------------------
  $ExtraIPOrders = DB_Select(Array('Orders','ExtraIPOrders'),Array('COUNT(*) as `Count`','OrderID','OrderDate',' GET_DAY_FROM_TIMESTAMP(`OrderDate`) as `Day`'),Array('Where'=>'`ExtraIPOrders`.`OrderID` = `Orders`.`ID` AND ' . $Where,'GroupBy'=>'Day','SortOn'=>'OrderDate'));
  #-----------------------------------------------------------------------------
  switch(ValueOf($ExtraIPOrders)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      # No more...
    break;
    case 'array':
      #-------------------------------------------------------------------------
      $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Дата'),new Tag('TD',Array('class'=>'Head'),'Кол-во')));
      #-------------------------------------------------------------------------
      $CurrentMonth = 0;
      #-------------------------------------------------------------------------
      foreach($ExtraIPOrders as $ExtraIPOrder){
        #-----------------------------------------------------------------------
        if(Date('n',$ExtraIPOrder['Day']*86400) != $CurrentMonth){
          #---------------------------------------------------------------------
          $CurrentMonth = Date('n',$ExtraIPOrder['Day']*86400);
          #---------------------------------------------------------------------
          $Table[] = SPrintF('%s %u г.',$MonthsNames[$CurrentMonth],Date('Y',$ExtraIPOrder['Day']*86400));
        }
        #-----------------------------------------------------------------------
        $Table[] = Array(Date('d',$ExtraIPOrder['Day']*86400),(integer)$ExtraIPOrder['Count']);
      }
      #-------------------------------------------------------------------------
      $NoBody->AddChild(new Tag('H2','Распределение заказов по дням'));
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Tables/Extended',$Table);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $NoBody->AddChild($Comp);
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
if(In_Array('ByMonth',$Details)){
  #-----------------------------------------------------------------------------
  $ExtraIPOrders = DB_Select(Array('Orders','ExtraIPOrders'),Array('OrderID','COUNT(*) as `Count`','MONTH(FROM_UNIXTIME(`OrderDate`)) as `Month`','OrderDate','YEAR(FROM_UNIXTIME(`OrderDate`)) as Year'),Array('Where'=>'`ExtraIPOrders`.`OrderID` = `Orders`.`ID` AND ' . $Where,'GroupBy'=>Array('Month','Year'),'SortOn'=>'OrderDate'));
  #-----------------------------------------------------------------------------
  switch(ValueOf($ExtraIPOrders)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      # No more...
    break;
    case 'array':
      #-------------------------------------------------------------------------
      $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Месяц'),new Tag('TD',Array('class'=>'Head'),'Кол-во')));
      #-------------------------------------------------------------------------
      $Order = Current($ExtraIPOrders);
      $sMonth = $Order['Month']+$Order['Year']*12;
      #-------------------------------------------------------------------------
      $Order = End($ExtraIPOrders);
      $eMonth = $Order['Month']+$Order['Year']*12;
      #-------------------------------------------------------------------------
      $Months = Array();
      #-------------------------------------------------------------------------
      foreach($ExtraIPOrders as $Order)
        $Months[$Order['Month']+$Order['Year']*12] = $Order;
      #-------------------------------------------------------------------------
      $Labels = $Line = Array();
      #-------------------------------------------------------------------------
      $CurrentYear = 0;
      #-------------------------------------------------------------------------
      for($Month=$sMonth;$Month<=$eMonth;$Month++){
        #-----------------------------------------------------------------------
        $Order = (IsSet($Months[$Month])?$Months[$Month]:Array('Month'=>$Month%12,'Year'=>(integer)($Month/12),'Count'=>0,'OrderID'=>'-'));
        #-----------------------------------------------------------------------
        $Labels[] = SPrintF("%s\n%u г.",$MonthsNames[$Order['Month']],$Order['Year']);
        #-----------------------------------------------------------------------
        $Line[] = $Order['Count'];
        #-----------------------------------------------------------------------
        if($Order['Year'] != $CurrentYear){
          #---------------------------------------------------------------------
          $CurrentYear = $Order['Year'];
          #---------------------------------------------------------------------
          $Table[] = SPrintF('%u г.',$CurrentYear);
        }
        #-----------------------------------------------------------------------
        $Table[] = Array($MonthsNames[$Order['Month']],(integer)$Order['Count']);
      }
      #-------------------------------------------------------------------------
      $NoBody->AddChild(new Tag('H2','Распределение заказов по месяцам'));
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Tables/Extended',$Table);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $NoBody->AddChild($Comp);
      #-------------------------------------------------------------------------
      if(Count($Line) > 1){
        #-----------------------------------------------------------------------
        $File = SPrintF('%s.jpg',Md5('ExtraIPOrders1'));
        #-----------------------------------------------------------------------
        Artichow_Line('Распределение заказов по месяцам',SPrintF('%s/%s',$Folder,$File),Array($Line),$Labels,Array(0x233454));
        #-----------------------------------------------------------------------
        $NoBody->AddChild(new Tag('BR'));
        #-----------------------------------------------------------------------
        $NoBody->AddChild(new Tag('IMG',Array('src'=>$File)));
      }
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
if(In_Array('ByQuarter',$Details)){
  #-----------------------------------------------------------------------------
  $ExtraIPOrders = DB_Select(Array('Orders','ExtraIPOrders'),Array('OrderID','COUNT(*) as `Count`','GET_QUARTER_FROM_TIMESTAMP(`OrderDate`) as `Quarter`','OrderDate','YEAR(FROM_UNIXTIME(`OrderDate`)) as Year'),Array('Where'=>'`ExtraIPOrders`.`OrderID` = `Orders`.`ID` AND ' . $Where,'GroupBy'=>Array('Quarter','Year'),'SortOn'=>'OrderDate'));
  #-----------------------------------------------------------------------------
  switch(ValueOf($ExtraIPOrders)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      # No more...
    break;
    case 'array':
     #--------------------------------------------------------------------------
      $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Квартал'),new Tag('TD',Array('class'=>'Head'),'Кол-во')));
      #-------------------------------------------------------------------------
      $Order = Current($ExtraIPOrders);
      $sQuarter = $Order['Quarter'] + $Order['Year']*4;
      #-------------------------------------------------------------------------
      $Order = End($ExtraIPOrders);
      $eQuarter = $Order['Quarter'] + $Order['Year']*4;
      #-------------------------------------------------------------------------
      $Quarters = Array();
      #-------------------------------------------------------------------------
      foreach($ExtraIPOrders as $Order)
        $Quarters[$Order['Quarter'] + $Order['Year']*4] = $Order;
      #-------------------------------------------------------------------------
      $Labels = $Line = Array();
      #-------------------------------------------------------------------------
      $CurrentYear = 0;
      #-------------------------------------------------------------------------
      for($Quarter = $sQuarter;$Quarter<=$eQuarter;$Quarter++){
        #-----------------------------------------------------------------------
        $Order = (IsSet($Quarters[$Quarter])?$Quarters[$Quarter]:Array('Quarter'=>($Quarter - ((integer)($Quarter/4))*4),'Year'=>(integer)($Quarter/4),'Count'=>0,'OrderID'=>'-'));
        #-----------------------------------------------------------------------
        $Labels[] = SPrintF('%u кв.(%u г.)',$Order['Quarter'],$Order['Year']);
        #-----------------------------------------------------------------------
        $Line[] = $Order['Count'];
        #-----------------------------------------------------------------------
        if($Order['Year'] != $CurrentYear){
          #---------------------------------------------------------------------
          $CurrentYear = $Order['Year'];
          $Table[] = SPrintF('%u г.',$Order['Year']);
        }
        #-----------------------------------------------------------------------
        $Table[] = Array(SPrintF('%u кв.',$Order['Quarter']),$Order['Count']);
      }
      #-------------------------------------------------------------------------
      $NoBody->AddChild(new Tag('H2','Распределение заказов по кварталам'));
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Tables/Extended',$Table);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $NoBody->AddChild($Comp);
      #-------------------------------------------------------------------------
      if(Count($Line) > 1){
        #-----------------------------------------------------------------------
        $File = SPrintF('%s.jpg',Md5('ExtraIPOrders2'));
        #-----------------------------------------------------------------------
        Artichow_Line('Распределение заказов по кварталам',SPrintF('%s/%s',$Folder,$File),Array($Line),$Labels,Array(0x233454));
        #-----------------------------------------------------------------------
        $NoBody->AddChild(new Tag('BR'));
        #-----------------------------------------------------------------------
        $NoBody->AddChild(new Tag('IMG',Array('src'=>$File)));
      }
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
if(Count($NoBody->Childs) < 2)
  return $Result;
#-------------------------------------------------------------------------------
$Result['DOM'] = $NoBody;
#-------------------------------------------------------------------------------
return $Result;

?>
