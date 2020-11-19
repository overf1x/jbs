<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$IsKill 	=  (integer) @$Args['IsKill'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['Administrator']['Notes']['Tasks'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tmp = System_Element('tmp');
if(Is_Error($Tmp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$IsKill)
	return new gException('NOTHING_TO_DO',PrintF('Не задано что надо сделать с планировщиком'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// мягкий рестарт
if($IsKill != 9){
	#-------------------------------------------------------------------------------
	// а может маркер уже стоит?
	if(File_Exists(SPrintF('%s/ExitCron.txt',$Tmp)))
		return new gException('MARKER_FOR_CRON_RESTART_EXISTS',SPrintF('Файл перезапуска планировщика уже существует: %s',SPrintF('%s/ExitCron.txt',$Tmp)));
	#-------------------------------------------------------------------------------
	// маркер, что планировщик надо перезапустить
	if(!@File_Put_Contents(SPrintF('%s/ExitCron.txt',$Tmp), Time()))
		return new gException('CANNOT_CREATE_MARKER_FOR_CRON_RESTART',SPrintF('Не удалось создать маркер о необходимости перезапуска планировщика: %s',SPrintF('%s/ExitCron.txt',$Tmp)));
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверка что можно убивать
if(!$Settings['AllowKill'])
	return new gException('KILL_CRON_NOT_ALLOWED',SPrintF('Настройками запрещено убивать планировщик'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверка что настало время убивать
$Marker = SPrintF('%s/TaskLastExecute.txt',$Tmp);
#-------------------------------------------------------------------------------
if(File_Exists($Marker)){
	#-------------------------------------------------------------------------------
	$Data = IO_Read($Marker);
	if(Is_Error($Data))
		return ERROR | @Trigger_Error('[MARKER_READ_ERROR]: не удалось прочитать файл');
	#-------------------------------------------------------------------------------
	if(Time() - StrToTime($Data) < IntVal($Settings['KillTimeout']))
		return new gException('KILL_CRON_TIMEOUT_NOT_EXCESSED',SPrintF('Планировщик можно убивать лишь через %u секунд простоя, до этого времени осталось %u секунд',$Settings['KillTimeout'], StrToTime($Data) - Time() + IntVal($Settings['KillTimeout'])));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверка что есть файл с указанием имени интерпретатора
$BinaryName = SPrintF('%s/CronBinaryName.txt',$Tmp);
#-------------------------------------------------------------------------------
if(!File_Exists($BinaryName))
	return new gException('FILE_WITH_BINARY_NAME_NOT_FOUND',SPrintF('Не удалось определить имя исполняемого файла'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Data = IO_Read($BinaryName);
if(Is_Error($Data))
	return ERROR | @Trigger_Error('[MARKER_BINARY_FILE_ERROR]: не удалось прочитать файл с именем исполняемого файла');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# убить, человеков!
Exec(SPrintF('killall %s >/dev/null 2>&1',$Data));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
