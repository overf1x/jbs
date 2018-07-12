<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Joonte Software
 *
 */
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
class NotificationManager {
	#-------------------------------------------------------------------------------
	public static function sendMsg(Msg $msg, $Methods = Array(), $IsForceDelivery = FALSE) {
		#-------------------------------------------------------------------------------
		$Executor = Comp_Load('www/Administrator/API/ProfileCompile', Array('ProfileID' => 100));
		#-------------------------------------------------------------------------------
		switch (ValueOf($Executor)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			break;
		case 'array':
			#-------------------------------------------------------------------------------
			$msg->setParam('Executor', $Executor['Attribs']);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$User = DB_Select('Users',Array('ID','Name','Sign','Email','UniqID','IsNotifies','Params'),Array('UNIQ','ID'=>$msg->getTo()));
		#-------------------------------------------------------------------------------
		switch(ValueOf($User)){
		case 'error':
			return ERROR | @Trigger_Error('[Email_Send]: не удалось выбрать получателя');
		case 'exception':
			return new gException('EMAIL_RECIPIENT_NOT_FOUND','Получатель письма не найден');
		case 'array':
			#-------------------------------------------------------------------------------
			$TypeID = $msg->getTemplate();
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[system/classes/NotificationManager]: TypeID = %s',$TypeID));
			#-------------------------------------------------------------------------------
			if($TypeID != 'UserPasswordRestore')
				if(!$User['IsNotifies'])
					return new gException('NOTIFIES_RECIPIENT_DISABLED','Уведомления для получателя отключены');
			#-------------------------------------------------------------------------------
			$msg->setParam('User', $User);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$From = DB_Select('Users',Array('ID','Name','Sign','Email','UniqID','Params'),Array('UNIQ','ID'=>$msg->getFrom()));
		#-------------------------------------------------------------------------------
		switch(ValueOf($From)){
		case 'error':
			return ERROR | @Trigger_Error('[Email_Send]: не удалось выбрать отправителя');
		case 'exception':
			return new gException('EMAIL_SENDER_NOT_FOUND','Отправитель не найден');
		case 'array':
			#-------------------------------------------------------------------------------
			$msg->setParam('From', $From);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Config = Config();
		#-------------------------------------------------------------------------------
		$Notifies = $Config['Notifies'];
		#-------------------------------------------------------------------------------
		# вариант когда методы не заданы - значит все доступные
		if(SizeOf($Methods) == 0){
			#-------------------------------------------------------------------------------
			$Array = Array();
			#-------------------------------------------------------------------------------
			foreach (Array_Keys($Notifies['Methods']) as $MethodID)
				$Array[] = $MethodID;
			#-------------------------------------------------------------------------------
			$Methods = $Array;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$sentMsgCnt = 0;
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Notifies['Methods']) as $MethodID){
			#-------------------------------------------------------------------------------
			if(!$Notifies['Methods'][$MethodID]['IsActive'] || !In_Array($MethodID,$Methods))
				continue;
			#-------------------------------------------------------------------------------
			# проверяем контакт, если не мыло - должен быть подтверждён
			if($MethodID != 'Email')
				if(!$User['Params']['NotificationMethods'][$MethodID]['Confirmed'])
					continue;
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			# кусок от JBS-879
			if(!IsSet($Notifies['Types'][$TypeID])){
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[system/classes/NotificationManager]: TypeID = %s not found',$TypeID));
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				# такие оповещения вообще могут быть отключены (пока, не настраиваемо, т.к. не нужно)
				if(!$Notifies['Types'][$TypeID]['IsActive'])
					continue;
				#-------------------------------------------------------------------------------
				# проверяем, не отключены ли такие оповещения глобально
				$UseName = SPrintF('Use%s',$MethodID);
				#-------------------------------------------------------------------------------
				if(IsSet($Notifies['Types'][$TypeID][$UseName]) && !$Notifies['Types'][$TypeID][$UseName])
					continue;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			# проверяем, не отключены ли такие оповещения в настройках юзера
			$Count = DB_Count('Notifies', Array('Where' => SPrintF("`UserID` = %u AND `MethodID` = '%s' AND `TypeID` = '%s'",$msg->getTo(),$MethodID,$TypeID)));
			if (Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if($Count && !$IsForceDelivery){
				#-------------------------------------------------------------------------------
				# отключено, принудительная доставка не задана
				continue;
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				if($IsForceDelivery)
					Debug(SPrintF('[system/classes/NotificationManager]: задана принудительная доставка сообщений',$TypeID));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			# JBS-1126: save $MethodID settings
			$msg->setParam('MethodSettings', $Notifies['Methods'][$MethodID]);
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			if(!class_exists($MethodID))
				return new gException('DISPATCHER_NOT_FOUND', 'Dispatcher not found: '.$MethodID);
            		#-------------------------------------------------------------------------------
			#$dispatcher = $MethodID::get();
			$dispatcher = call_user_func($MethodID.'::get', true);
			#-------------------------------------------------------------------------------
			try {
				#-------------------------------------------------------------------------------
				$dispatcher->send($msg);
				#-------------------------------------------------------------------------------
				$sentMsgCnt++;
				#-------------------------------------------------------------------------------
			}catch(jException $e){
				#-------------------------------------------------------------------------------
				Debug(SPrintF("[system/classes/NotificationManager]: Error while sending message [userId=%s, message=%s]", $User['ID'], $e->getMessage()));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if($sentMsgCnt < 1){
			#-------------------------------------------------------------------------------
			Debug(SPrintF("[system/classes/NotificationManager]: Couldn't send notify by any methods to user #%s",$User['ID']));
			#-------------------------------------------------------------------------------
			return new gException('USER_NOT_NOTIFIED','Не удалось оповестить пользователя ни одним из методов');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
		#------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
