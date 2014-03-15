<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
#-------------------------------------------------------------------------------
class ExtraIPServer{
# Тип системы сервера
public $SystemID = 'Default';
# Параметры связи с сервером
public $Settings = Array();
#-------------------------------------------------------------------------------
public function FindSystem($ExtraIPOrderID,$OrderType,$DependOrderID){
	/****************************************************************************/
	$__args_types = Array('integer','string','integer');
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/****************************************************************************/
	/* find server */
	Debug(SPrintF('[system/classes/ExtraIPServer]: OrderType = %s',$OrderType));
	if($OrderType == 'Hosting'){
		#-------------------------------------------------------------------------------
		$Columns = Array('ServerID','Login');
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Columns = Array('(SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `VPSOrdersOwners`.`OrderID`) AS `ServerID`','Login');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$OrderServer = DB_Select($OrderType . 'OrdersOwners',$Columns,Array('UNIQ','ID'=>$DependOrderID));
	switch(ValueOf($OrderServer)){
	case 'error':
		return ERROR | @Trigger_Error('[Server->Select]: ошибка поиска зависимого заказа');
	case 'exception':
		return new gException('DEPEND_ORDER_NOT_FOUND','Не найден заказ к которому необходимо добавить/удалить IP адрес');
	case 'array':
		$Table = ($OrderType == 'VPS')?'':$OrderType;
		$SysInfo = DB_Select($Table . 'Servers','*',Array('UNIQ','ID'=>$OrderServer['ServerID']));
		switch(ValueOf($SysInfo)){
		case 'error':
			return ERROR | @Trigger_Error('[Server->Select]: не удалось выбрать сервер');
		case 'exception':
			return new gException('SERVER_NOT_FOUND','Указаный сервер не найден');
		case 'array':
			/* find server info */
			#-------------------------------------------------------------------------
			# add User Login on server
			$SysInfo['UserLogin'] = $OrderServer['Login'];
			Debug("[system/classes/ExtraIPServer]: found ICS: " . ($OrderType == 'VPS')?$SysInfo['Params']['SystemID']:$SysInfo['SystemID']);
			$this->SystemID = ($OrderType == 'VPS')?$SysInfo['Params']['SystemID']:$SysInfo['SystemID'];
			$this->Settings = $SysInfo;
			if(Is_Error(System_Load(SPrintF('libs/%s.php',$this->SystemID))))
				@Trigger_Error('[Server->Select]: не удалось загрузить целевую библиотеку');
			// + надо загрузить собсно либу для работы с IP адресами
			#-------------------------------------------------------------------------
			return TRUE;
		default:
			return ERROR | @Trigger_Error(101);
		}
	default:
		return ERROR | @Trigger_Error(101);
	}
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function AddIP(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_AddIP',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->AddIP]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
public function DeleteIP(){
  /****************************************************************************/
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  Array_UnShift($__args__,$this->Settings);
  #-----------------------------------------------------------------------------
  $Function = SPrintF('%s_DeleteIP',$this->SystemID);
  #-----------------------------------------------------------------------------
  if(!Function_Exists($Function))
    return new gException('FUNCTION_NOT_SUPPORTED','Функция не поддерживается API модулем');
  #-----------------------------------------------------------------------------
  $Result = Call_User_Func_Array($Function,$__args__);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[Server->DeleteIP]: не удалось вызвать целевую функцию');
  #-----------------------------------------------------------------------------
  return $Result;
}


}
#-------------------------------------------------------------------------------
?>
