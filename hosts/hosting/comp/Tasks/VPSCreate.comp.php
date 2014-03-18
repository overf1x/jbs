<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','VPSOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/VPSServer.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Columns = Array(
		'ID','OrderID','UserID','Login','IP','Domain','SchemeID','Password',
		'(SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `VPSOrdersOwners`.`OrderID`) AS `ServerID`',
		'(SELECT `Params` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `VPSOrdersOwners`.`OrderID`) AS `Params`',
		'(SELECT `ProfileID` FROM `Contracts` WHERE `Contracts`.`ID` = `VPSOrdersOwners`.`ContractID`) AS `ProfileID`'
		);
$VPSOrder = DB_Select('VPSOrdersOwners',$Columns,Array('UNIQ','ID'=>$VPSOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($VPSOrder)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	#-------------------------------------------------------------------------------
	$VPSServer = new VPSServer();
	#-------------------------------------------------------------------------------
	$IsSelected = $VPSServer->Select((integer)$VPSOrder['ServerID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsSelected)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'true':
		#-------------------------------------------------------------------------------
		$VPSScheme = DB_Select('VPSSchemes','*',Array('UNIQ','ID'=>$VPSOrder['SchemeID']));
		#-------------------------------------------------------------------------------
		switch(ValueOf($VPSScheme)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			#-------------------------------------------------------------------------------
			if(IsSet($VPSOrder['Params']['DiskTemplate'])){
				#-------------------------------------------------------------------------------
				foreach(Explode("\n",$VPSServer->Settings['Params']['DiskTemplate']) as $Line){
					#Debug(SPrintF('[comp/Tasks/VPSCreate]: Line = (%s)',print_r($Line,true)));
					#-------------------------------------------------------------------------------
					$Template = Explode('=',Trim($Line));
					#-------------------------------------------------------------------------------
					if($Template[0] == $VPSOrder['Params']['DiskTemplate'])
						$DiskTemplate = $Template[0];
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			if(!IsSet($DiskTemplate)){
				#-------------------------------------------------------------------------------
				$DiskTemplates = Explode("\n",$VPSServer->Settings['Params']['DiskTemplate']);
				#-------------------------------------------------------------------------------
				$DiskTemplate = Explode('=',$DiskTemplates[0]);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$VPSOrder['DiskTemplate'] = $DiskTemplate;
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			$IPsPool = Explode("\n",$VPSServer->Settings['Params']['IPsPool']);
			#-------------------------------------------------------------------------------
			$IP = $IPsPool[Rand(0,Count($IPsPool) - 1)];
			#-------------------------------------------------------------------------------
			$Args = Array($VPSOrder,$IP,$VPSScheme);
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			$IsCreate = Call_User_Func_Array(Array($VPSServer,'Create'),$Args);
			#-------------------------------------------------------------------------------
			switch(ValueOf($IsCreate)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return $IsCreate;
			case 'true':
				#-------------------------------------------------------------------------------
				# достаём собсно адрес из БД
				$VPS_IP = DB_Select('VPSOrdersOwners',Array('Login'),Array('UNIQ','ID'=>$VPSOrderID));
				#-------------------------------------------------------------------------------
				switch(ValueOf($VPS_IP)){
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
				$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'VPSOrders','StatusID'=>'Active','RowsIDs'=>$VPSOrder['ID'],'Comment'=>'Заказ успешно создан на сервере'));
				#-------------------------------------------------------------------------------
				switch(ValueOf($Comp)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					return ERROR | @Trigger_Error(400);
				case 'array':
					#-------------------------------------------------------------------------------
					$Event = Array(
							'UserID'	=> $VPSOrder['UserID'],
							'PriorityID'	=> 'Hosting',
							'Text'		=> SPrintF('Заказ VPS [%s] успешно создан на сервере (%s) с тарифным планом (%s), идентификатор пакета (%s)',$VPS_IP['Login'],$VPSServer->Settings['Address'],$VPSScheme['Name'],$VPSScheme['PackageID'])
							);
					$Event = Comp_Load('Events/EventInsert',$Event);
					if(!$Event)
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
					$GLOBALS['TaskReturnInfo'] = Array($VPSServer->Settings['Address'],$VPS_IP['Login'],$VPSScheme['Name']);
					#-------------------------------------------------------------------------------
					return TRUE;
					#-------------------------------------------------------------------------------
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
			default:
				return ERROR | @Trigger_Error(101);
			}
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
