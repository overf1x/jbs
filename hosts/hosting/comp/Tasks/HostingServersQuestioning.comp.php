<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/HostingServer.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Servers = DB_Select('Servers',Array('*','(SELECT `Name` FROM `ServersGroups` WHERE `ServersGroups`.`ID` = `Servers`.`ServersGroupID`) AS `Name`'),Array('Where'=>'(SELECT `ServiceID` FROM `ServersGroups` WHERE `Servers`.`ServersGroupID` = `ServersGroups`.`ID`) = 10000','SortOn'=>'Address'));
#-------------------------------------------------------------------------------
switch(ValueOf($Servers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return 1800;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array();
#-------------------------------------------------------------------------------
foreach($Servers as $Server){
	#-------------------------------------------------------------------------------
	#if($Server['Address'] != 's06.host-food.ru')
	#	continue;
	#-------------------------------------------------------------------------------
	if(!$Server['IsActive'])
		continue;
	#-------------------------------------------------------------------------------
	if(Is_Null($Server['Name']))
		$Server['Name'] = 'NoGroup';
	#-------------------------------------------------------------------------------
	if(!IsSet($GLOBALS['TaskReturnInfo'][$Server['Name']]))
		$GLOBALS['TaskReturnInfo'][$Server['Name']] = Array();
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'][$Server['Name']][] = $Server['Address'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$ClassHostingServer = new HostingServer();
	#-------------------------------------------------------------------------------
	$IsSelected = $ClassHostingServer->Select((integer)$Server['ID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsSelected)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'true':
		#-------------------------------------------------------------------------------
		$Users = $ClassHostingServer->GetDomains();
		#-------------------------------------------------------------------------------
		switch(ValueOf($Users)){
		case 'error':
			# No more...
			break 2;
		case 'exception':
			# No more...
			break 2;
		case 'array':
			#-------------------------------------------------------------------------------
			if(Count($Users)){
				#-------------------------------------------------------------------------------
				$Array = Array();
				#-------------------------------------------------------------------------------
				foreach(Array_Keys($Users) as $UserID)
					$Array[] = SPrintF("'%s'",$UserID);
				#-------------------------------------------------------------------------------
				$Where = SPrintF('`ServerID` = %u AND `Login` IN (%s)',$Server['ID'],Implode(',',$Array));
				#-------------------------------------------------------------------------------
				$HostingOrders = DB_Select('HostingOrdersOwners',Array('ID','Login'),Array('Where'=>$Where));
				#-------------------------------------------------------------------------------
				switch(ValueOf($HostingOrders)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					# No more...
					break;
				case 'array':
					#-------------------------------------------------------------------------------
					foreach($HostingOrders as $HostingOrder){
						#-------------------------------------------------------------------------------
						$Parked = $Users[$HostingOrder['Login']];
						#-------------------------------------------------------------------------------
						$IsUpdate = DB_Update('HostingOrders',Array('Domain'=>(Count($Parked)?Current($Parked):'not-found'),'Parked'=>Implode(',',$Parked)),Array('ID'=>$HostingOrder['ID']));
						if(Is_Error($IsUpdate))
							return ERROR | @Trigger_Error(500);
						#-------------------------------------------------------------------------------
					}
					#-------------------------------------------------------------------------------
					break;
					#-------------------------------------------------------------------------------
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			break 2;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return 1800;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
