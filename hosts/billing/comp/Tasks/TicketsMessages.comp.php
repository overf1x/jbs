<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Tree.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Config = Config();
$Settings = $Config['Tasks']['Types']['TicketsMessages'];
#-------------------------------------------------------------------------------
$ExecuteTime = Comp_Load('Formats/Task/ExecuteTime',Array('ExecutePeriod'=>$Settings['ExecutePeriod']));
if(Is_Error($ExecuteTime))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$MessagesCount = 0;
#-------------------------------------------------------------------------------
$Columns = Array(
		'ID','UserID','EdeskID','SUBSTR(`Content`,1,4096) AS `Content`',
		'(SELECT `Theme` FROM `Edesks` WHERE `Edesks`.`ID` = `EdeskID`) as `Theme`',
		'(SELECT `TargetGroupID` FROM `Edesks` WHERE `Edesks`.`ID` = `EdeskID`) as `TargetGroupID`',
		'(SELECT `TargetUserID` FROM `Edesks` WHERE `Edesks`.`ID` = `EdeskID`) as `TargetUserID`',
		'(SELECT `UserID` FROM `Edesks` WHERE `Edesks`.`ID` = `EdeskID`) as `OwnerID`',
		'(SELECT `StatusID` FROM `Edesks` WHERE `Edesks`.`ID` = `EdeskID`) as `StatusID`',
		'(SELECT `NotifyEmail` FROM `Edesks` WHERE `Edesks`.`ID` = `EdeskID`) as `NotifyEmail`'
		);
#-------------------------------------------------------------------------------
$Where = Array(
		"`IsNotify` = 'no'",
		"`IsVisible` = 'yes'"
		);
#-------------------------------------------------------------------------------
$Messages = DB_Select('EdesksMessages',$Columns,Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Messages)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    foreach($Messages as $Message){
      #-------------------------------------------------------------------------
      $TargetUserID = (integer)$Message['TargetUserID'];
      $TargetGroupID = (integer)$Message['TargetGroupID'];
      #-------------------------------------------------------------------------
      if($TargetGroupID != 1){
        #-----------------------------------------------------------------------
        $IsOwner = ($Message['UserID'] == ($OwnerID = $Message['OwnerID']));
        #-----------------------------------------------------------------------
        if($IsOwner){
          #---------------------------------------------------------------------
          if($TargetUserID != 100){
            #-------------------------------------------------------------------
            $msgParams = Array(
                'TicketID'	=> $Message['EdeskID'],
                'Theme'		=> $Message['Theme'],
                'Message'	=> $Message['Content'],
		'Message-ID'	=> SPrintF('<%s@%s>',$Message['ID'],HOST_ID)
            );
	    #-------------------------------------------------------------------
            $msg = new Message('ToTicketsMessages', $TargetUserID, $msgParams);
            $IsSend = NotificationManager::sendMsg($msg);
            #-------------------------------------------------------------------
            switch(ValueOf($IsSend)){
              case 'error':
                return ERROR | @Trigger_Error(500);
              case 'exception':
                # No more...
              case 'true':
	        #---------------------------------------------------------------
		$MessagesCount++;
                # Update -> `IsNotify`='yes'
		$IsUpdate = DB_Update('EdesksMessages',Array('IsNotify'=>'yes'),Array('ID'=>$Message['ID']));
		if(Is_Error($IsUpdate))
		  return ERROR | @Trigger_Error(500);
		#---------------------------------------------------------------
		break;
              default:
                return ERROR | @Trigger_Error(101);
            }
          }else{
            #-------------------------------------------------------------------
            $Entrance = Tree_Entrance('Groups',$TargetGroupID);
            #-------------------------------------------------------------------
            switch(ValueOf($Entrance)){
              case 'error':
                return ERROR | @Trigger_Error(500);
              case 'exception':
                return ERROR | @Trigger_Error(400);
              case 'array':
                #---------------------------------------------------------------
                $String = Implode(',',$Entrance);
                #---------------------------------------------------------------
                $Employers = DB_Select('Users','ID',Array('Where'=>SPrintF('`GroupID` IN (%s)',$String)));
                #---------------------------------------------------------------
                switch(ValueOf($Employers)){
                  case 'error':
                    return ERROR | @Trigger_Error(500);
                  case 'exception':
                    # No more...
                  continue 3;
                  case 'array':
                    #-----------------------------------------------------------
                    foreach($Employers as $Employer){
                      #---------------------------------------------------------
                      $msgParams = Array(
                          'TicketID'	=> $Message['EdeskID'],
                          'Theme'	=> $Message['Theme'],
                          'Message'	=> $Message['Content'],
                          'Message-ID'	=> SPrintF('<%s@%s>',$Message['ID'],HOST_ID)
                      );
		      #-------------------------------------------------------------------
                      $msg = new Message('ToTicketsMessages',(integer)$Employer['ID'], $msgParams);
                      $IsSend = NotificationManager::sendMsg($msg);
                      #---------------------------------------------------------
                      switch(ValueOf($IsSend)){
                        case 'error':
                          return ERROR | @Trigger_Error(500);
                        case 'exception':
                          # No more...
                        case 'true':
			  #---------------------------------------------------------------
			  $MessagesCount++;
                          # Update -> `IsNotify`='yes'
			  $IsUpdate = DB_Update('EdesksMessages',Array('IsNotify'=>'yes'),Array('ID'=>$Message['ID']));
			  if(Is_Error($IsUpdate))
			    return ERROR | @Trigger_Error(500);
			  #---------------------------------------------------------------
                        break;
                        default:
                          return ERROR | @Trigger_Error(101);
                      }
                    }
                  break 2;
                  default:
                    return ERROR | @Trigger_Error(101);
                }
              default:
                return ERROR | @Trigger_Error(101);
            }
          }
        }else{
          #---------------------------------------------------------------------
          $String = $Message['Content'];
          #---------------------------------------------------------------------
          switch($Message['StatusID']){
            case 'Closed':
              $String = SPrintF("%s\n\nЕсли потребуется какая-либо помощь, пожалуйста, откройте новый запрос.\nСпасибо.",$String);
            break;
            case 'Working':
              $String = SPrintF("%s\n\nНа данный момент мы решаем Ваш вопрос.\nСпасибо.",$String);
            break;
            default:
              # No more...
          }
          #---------------------------------------------------------------------
          $Message['Content'] = $String;
          #---------------------------------------------------------------------
          $msgParams = Array(
              'TicketID'	=> $Message['EdeskID'],
              'Theme'		=> $Message['Theme'],
              'Message'		=> $Message['Content'],
              'Message-ID'	=> SPrintF('<%s@%s>',$Message['ID'],HOST_ID)
          );
          #-------------------------------------------------------------------
          if(StrLen($Message['NotifyEmail']) > 5)
            $msgParams['Recipient'] = $Message['NotifyEmail'];
	  #-------------------------------------------------------------------
	  #Debug(SPrintF('[comp/Tasks/TicketsMessages]: NotifyEmail = %s',$Message['NotifyEmail']));
	  #Debug(SPrintF('[comp/Tasks/TicketsMessages]: msgParams = %s',print_r($msgParams,true)));
          #-------------------------------------------------------------------
          $msg = new FromTicketsMessagesMsg($msgParams, (integer)$OwnerID);
          $IsSend = NotificationManager::sendMsg($msg);
          #---------------------------------------------------------------------
          switch(ValueOf($IsSend)){
            case 'error':
              return ERROR | @Trigger_Error(500);
            case 'exception':
              # No more...
            case 'true':
	      #---------------------------------------------------------------
	      $MessagesCount++;
              # Update -> `IsNotify`='yes'
              $IsUpdate = DB_Update('EdesksMessages',Array('IsNotify'=>'yes'),Array('ID'=>$Message['ID']));
              if(Is_Error($IsUpdate))
                 return ERROR | @Trigger_Error(500);
              #---------------------------------------------------------------
            break;
            default:
              return ERROR | @Trigger_Error(101);
          }
        }
      }else{
        #-----------------------------------------------------------------------
        # No more...
        continue;
      }
    }
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($MessagesCount > 0 && !IsSet($GLOBALS['TaskReturnInfo'])){
	$GLOBALS['TaskReturnInfo'] = SPrintF('%u new messages',$MessagesCount);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $ExecuteTime;
#-------------------------------------------------------------------------------

?>
