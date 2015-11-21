<?php
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$Commit = (boolean) @$Args['Commit'];
$Force  = (boolean) @$Args['Force'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Header('Content-type: text/plain; charset=utf-8');
#-------------------------------------------------------------------------------
$__SYSLOG = &$GLOBALS['__SYSLOG'];
#-------------------------------------------------------------------------------
echo SPrintF("%s обновлений\n",$Commit?'Применение':'Проверка');
#-------------------------------------------------------------------------------
echo "Получение снимка файловой системы\n--\n";
#-------------------------------------------------------------------------------
$Snapshot = Array();
#-------------------------------------------------------------------------------
$Folders = Array('hosts','styles','db','scripts','others','patches');
#-------------------------------------------------------------------------------
$HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
#-------------------------------------------------------------------------------
Array_Shift($HostsIDs);
#-------------------------------------------------------------------------------
foreach($Folders as $Folder){
	#-------------------------------------------------------------------------------
	foreach($HostsIDs as $HostID){
		#-------------------------------------------------------------------------------
		$Path = SPrintF('%s/%s/%s',SYSTEM_PATH,$Folder,$HostID);
		#-------------------------------------------------------------------------------
		if(!File_Exists($Path))
			continue;
		#-------------------------------------------------------------------------------
		$Files = IO_Files($Path);
		if(Is_Error($Files))
			return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
		#-------------------------------------------------------------------------------
		foreach($Files as $File){
			#-------------------------------------------------------------------------------
			if(Preg_Match('/(tmp|LastPatchFiles)/',$File))
				continue;
			#-------------------------------------------------------------------------------
			$MD5 = MD5_File($File);
			if(!$MD5)
				return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
			#-------------------------------------------------------------------------------
			$File = SubStr($File,StrLen(SYSTEM_PATH)+1);
			#-------------------------------------------------------------------------------
			$Snapshot[SPrintF('MD5%s',MD5(SPrintF('%s-%s',$MD5,MD5($File))))] = $File;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Files = IO_Files(SPrintF('%s/core',SYSTEM_PATH));
if(Is_Error($Files))
	return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
#-------------------------------------------------------------------------------
foreach($Files as $File){
	#-------------------------------------------------------------------------------
	$MD5 = MD5_File($File);
	if(!$MD5)
		return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
	#-------------------------------------------------------------------------------
	$File = SubStr($File,StrLen(SYSTEM_PATH)+1);
	#-------------------------------------------------------------------------------
	$Snapshot[SPrintF('MD5%s',MD5(SPrintF('%s-%s',$MD5,MD5($File))))] = $File;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
echo "Запрос обновлений\n";
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Server = $Config['Update']['Server'];
#-------------------------------------------------------------------------------
$Answer = HTTP_Send('/GetUpdate',$Server,Array('HostsIDs'=>Implode(',',$HostsIDs)),Array('Snapshot'=>JSON_Encode($Snapshot)));
if(Is_Error($Answer))
	return "ERROR: не удалось выполнить запрос к серверу\n";
#-------------------------------------------------------------------------------
echo "Ответ получен\n--\n";
#-------------------------------------------------------------------------------
$Answer = @JSON_Decode($Answer['Body'],TRUE);
if(!Is_Array($Answer))
	return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
#-------------------------------------------------------------------------------
switch($Answer['Status']){
case 'Error':
	return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
case 'Exception':
	#-------------------------------------------------------------------------------
	$Exception = $Answer['Exception'];
	#-------------------------------------------------------------------------------
	return SPrintF("%s\n",$Exception['String']);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'Ok':
	#-------------------------------------------------------------------------------
	if(IsSet($Answer['Deleted'])){
		#-------------------------------------------------------------------------------
		foreach($Answer['Deleted'] as $Deleted){
			#-------------------------------------------------------------------------------
			echo SPrintF("Удаление файла (%s)\n",$Deleted);
			#-------------------------------------------------------------------------------
			$File = SPrintF('%s/%s',SYSTEM_PATH,$Deleted);
			#-------------------------------------------------------------------------------
			if(!Is_Writable($File))
				return SPrintF("ERROR: недостаточно прав на удаление файла (%s)\n",$File);
			#-------------------------------------------------------------------------------
			if($Commit)
				if(!@UnLink($File))
					return SPrintF("ERROR: не возможно удалить файл (%s)\n",$File);
			#-------------------------------------------------------------------------------
			$Dir = DirName($File);
			#-------------------------------------------------------------------------------
			$Files = IO_Scan($Dir);
			if(Is_Error($Files))
				return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
			#-------------------------------------------------------------------------------
			if(!Count($Files)){
				#-------------------------------------------------------------------------------
				echo SPrintF("Удаление директории (%s)\n",$Dir);
				#-------------------------------------------------------------------------------
				if(!@RmDir($Dir))
					return SPrintF("ERROR: не возможно удалить директорию (%s)\n",$Dir);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(IsSet($Answer['Added'])){
		#-------------------------------------------------------------------------------
		foreach($Answer['Added'] as $Added){
			#-------------------------------------------------------------------------------
			$File = $Added['File'];
			#-------------------------------------------------------------------------------
			echo SPrintF("Обновление файла (%s)\n",$File);
			#-------------------------------------------------------------------------------
			$Path = SPrintF('%s/%s',SYSTEM_PATH,$File);
			#-------------------------------------------------------------------------------
			if(File_Exists($Path)){
				#-------------------------------------------------------------------------------
				SPrintF('Проверка прав на запись файла (%s)',$Path);
				#-------------------------------------------------------------------------------
				if(!Is_Writable($Path))
					return SPrintF("ERROR: недостаточно прав на запись файла (%s)\n",$Path);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			if($Commit){
				#-------------------------------------------------------------------------------
				$IsWrite = IO_Write($Path,Base64_Decode($Added['Source']),TRUE);
				if(Is_Error($IsWrite))
					return SPrintF("ERROR: не возможно обновить файл (%s)\n",$Path);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Commit){
	#-------------------------------------------------------------------------------
	$Tmp = System_Element('tmp');
	if(Is_Error($Tmp))
		return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
	#-------------------------------------------------------------------------------
	$IsWrite = IO_Write(SPrintF('%s/LastUpdate.stamp',$Tmp),(string)Time(),TRUE);
	if(Is_Error($IsWrite))
		return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return '[OK]';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
