<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['CleanTablesSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# зачищаем таблицу задач
$Where = Array(
		SPrintF('`ExecuteDate` < UNIX_TIMESTAMP() - %u',$Settings['TableTasksStoryPeriod'] * 24 * 3600),
		'`UserID` != 1','`TypeID` != "Dispatch"'
		);
$IsDelete = DB_Delete('Tasks',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# костыль к рассыльщику SMS
$Where = Array(
		'`CreateDate` < UNIX_TIMESTAMP() - 24 * 3600',
		"`TypeID` = 'SMS'"
		);
$IsDelete = DB_Delete('Tasks',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);

#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# зачищаем таблицу ServersUpTime
$Where = SPrintF('`TestDate` < UNIX_TIMESTAMP() - %u',$Settings['TableServersUpTimeStoryPeriod'] * 24 * 3600);
$IsDelete = DB_Delete('ServersUpTime',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);

#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# зачищаем таблицу RequestLog
$Where = SPrintF('`CreateDate` < UNIX_TIMESTAMP() - %u',$Settings['TableRequestLogStoryPeriod'] * 24 * 3600);
$IsDelete = DB_Delete('RequestLog',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);

#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara, 2011-12-27 in 14:09 MSK, for JBS-232
# проставляем тикеты как оповещённые, если больше недели прошло
$IsUpdate = DB_Update('EdesksMessages',Array('IsNotify'=>'yes'),Array('Where'=>SPrintF('`CreateDate` < %u',(Time() - 7*24*3600))));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);

#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara 2012-09-28 in 13:54 MSK, for JBS-377
$Where = '(SELECT `ID` FROM `Users` WHERE `Events`.`UserID`=`Users`.`ID`) IS NULL';
$IsDelete = DB_Delete('Events',Array('Where'=>$Where));
if(Is_Error($IsDelete))
	return ERROR | @Trigger_Error(500);

#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# added by lissyara for JBS-783
$IsQuery = DB_Query('SELECT `ID`,`ModeID`,`RowID` FROM `StatusesHistory` WHERE DAYOFMONTH(FROM_UNIXTIME(`StatusDate`)) = DAYOFMONTH(FROM_UNIXTIME(UNIX_TIMESTAMP()))');
if(Is_Error($IsQuery))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
$Rows = MySQL::Result($IsQuery);
if(Is_Error($Rows))
	return ERROR | @Trigger_Error(500);
#--------------------------------------------------------------------------------
if(Count($Rows) >0){
	#--------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Tasks/GC/CleanTables]: Rows = %s',print_r($Rows,true)));
	#--------------------------------------------------------------------------------
	$Keys = Array();
	#--------------------------------------------------------------------------------
	foreach($Rows as $Row){
		#--------------------------------------------------------------------------------
		# перебираем только уникальные значения
		$Key = SPrintF('%s-%s',$Row['ModeID'],$Row['RowID']);
		#--------------------------------------------------------------------------------
		if(In_Array($Key,$Keys))
			continue;
		#--------------------------------------------------------------------------------
		#Debug(SPrintF('[comp/Tasks/GC/CleanTables]: ID = %s, ModeID = %s, RowID = %s',$Row['ID'],$Row['ModeID'],$Row['RowID']));
		#--------------------------------------------------------------------------------
		$Keys[] = $Key;
		#--------------------------------------------------------------------------------
		#--------------------------------------------------------------------------------
		$Row1 = DB_Select($Row['ModeID'],'ID',Array('UNIQ','Where'=>SPrintF("`ID` = %u",$Row['RowID'])));
		#--------------------------------------------------------------------------------
		switch(ValueOf($Row1)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			#--------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/GC/CleanTables]: orphaned row ID = %s, ModeID = %s, RowID = %s',$Row['ID'],$Row['ModeID'],$Row['RowID']));
			#--------------------------------------------------------------------------------
			$IsDelete = DB_Delete('StatusesHistory',Array('Where'=>SPrintF('`ModeID` = "%s" AND `RowID` = %u',$Row['ModeID'],$Row['RowID'])));
			if(Is_Error($IsDelete))
				return ERROR | @Trigger_Error(500);
			#--------------------------------------------------------------------------------
			break;
			#--------------------------------------------------------------------------------
		case 'array':
			#--------------------------------------------------------------------------------
			break;
			#--------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
	}
}
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
return TRUE;
#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------

?>
