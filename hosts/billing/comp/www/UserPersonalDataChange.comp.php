<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Upload.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Персональные данные');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/UserPersonalDataChange.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Messages = Messages();
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#$Table = Array(new Tag('TD',Array('class'=>'Separator'),'Общая информация'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
			'Form/Input',
			Array(
				'name'	=> 'Name',
				'type'	=> 'text',
				'prompt'=> $Messages['Prompts']['User']['Name'],
				'value'	=> $__USER['Name'],
				'style'	=> 'width: 100%'
				)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>2),'Ваше имя'),new Tag('TD',Array('colspan'=>4),$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',Array('name'=>'Sign','rows'=>3,'prompt'=>($__USER['ID'] == 100)?'Подпись добавляется к сообщениям пользователя, а также к сообщениям в Telegram, Viber, Jabber и т.п. К почтовым отправлениям добавлятся подпись из настрок "Внешнего вида", или эта, если там ничего не задано':'bbcode: link, img, color, b, p, bg',),$__USER['Sign']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>2),'Подпись'),new Tag('TD',Array('colspan'=>4),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>7,'class'=>'Separator'),'Ваши контактные данные'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# TODO исправляем юзера - проверить что это реально надо, и надо тут
$Comp = Comp_Load('Tasks/RecoveryUsers',NULL,$__USER['ID']);
#-------------------------------------------------------------------------------
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# шапка контактов
$Tr = new Tag('TR');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'*'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/String','Тип контакта. Цветом выделены подтверждённые адреса',13);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'Контакт'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/String','Логин для входа в биллинг',5);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/String','Уведомления для адреса - если включены, то на адрес будут приходить сообщения. Для настройки индивидуальных уведомлений, кликните по значению в строке',5);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'*'));
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'*'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = $Tr;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# перебираем контакты юзера, выводим их
#-------------------------------------------------------------------------------
$Methods = $Config['Notifies']['Methods'];
#-------------------------------------------------------------------------------
foreach($__USER['Contacts'] as $Contact){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('UserNotice','Contacts',$Contact['ID'],$Contact['UserNotice'],FALSE,'/UserPersonalDataChange');
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Contact['IsPrimary']){
		#-------------------------------------------------------------------------------
		$IsPrimary = "ShowAlert('Этот адрес удалить нельзя, т.к. он используется для входа в биллинг. Для удаления, вначале назначьте другой адрес в качестве логина','Warning')";
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$IsPrimary = SPrintF("ShowConfirm('Вы подтверждаете удаление контакта?','ContactDelete(%s);');",$Contact['ID']);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if($Contact['IsActive']){
		#-------------------------------------------------------------------------------
		$IsActive = SPrintF("javascript:ShowWindow('/UserNotifiesSet?ContactID=%u');",$Contact['ID']);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$IsActive = "ShowAlert('Уведомления для этого адреса отключены. Для включения уведомлений подтвердите адрес и поставьте галочку \"Использовать для уведомлений\"','Warning')";
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp1 = Comp_Load('Formats/Logic',$Contact['IsPrimary']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Comp2 = Comp_Load('Formats/Logic',$Contact['IsActive']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array(
			#-------------------------------------------------------------------------------
			// примечание
			new Tag('TD',$Comp),
			#-------------------------------------------------------------------------------
			// тип контакта
			new Tag('TD',Array('class'=>'Head','style'=>SPrintF('background:%s;',($Contact['Confirmed'])?'#D5F66C':'WhiteSmoke')),$Methods[$Contact['MethodID']]['Name']),
			#-------------------------------------------------------------------------------
			// адрес
			new Tag('TD',Array('class'=>'Head'),new Tag('A',Array('href'=>SPrintF("javascript:ShowWindow('/ContactEdit?ContactID=%u');",$Contact['ID']),'title'=>'Кликните для изменения настроек и подтверждения контактного адреса'),$Contact['Address'])),
			#-------------------------------------------------------------------------------
			// это логин?
			new Tag('TD',Array('class'=>'Head','style'=>SPrintF('background:%s;',($Contact['IsPrimary'])?'#D5F66C':'WhiteSmoke')),$Comp1),
			#-------------------------------------------------------------------------------
			// уведомления разрешены
			new Tag('TD',Array('class'=>'Head','style'=>SPrintF('background:%s;',($Contact['IsActive'])?'#D5F66C':'WhiteSmoke')),$Comp2),
			#-------------------------------------------------------------------------------
			// редактирование уведомлений
			new Tag('TD',Array('class'=>'Head'),new Tag('IMG',Array('class'=>'Button','onclick'=>$IsActive,'onmouseover'=>"PromptShow(event,'Изменение настроек уведомлений',this);",'src'=>SPrintF('SRC:{Images/Icons/Notice%s.gif}',($Contact['Confirmed'])?'':'Off'),'width'=>16))),
			#-------------------------------------------------------------------------------
			// удаление
			new Tag('TD',Array('class'=>'Head'),new Tag('IMG',Array('class'=>'Button','onclick'=>$IsPrimary,'onmouseover'=>"PromptShow(event,'Удалить контактный адрес',this);",'src'=>SPrintF('SRC:{Images/Icons/Flush%s.gif}',($Contact['IsPrimary'])?'Off':1))))
			#-------------------------------------------------------------------------------
			);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'javascript:ShowWindow(\'/ContactEdit\');','value'=>'Добавить адрес'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>7,'align'=>'right'),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>7,'class'=>'Separator'),'Данные для системы техподдержки'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','UserFoto','-');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>2),'Аватар (90x110)'),new Tag('TD',Array('colspan'=>5),$Comp));
#-------------------------------------------------------------------------------
// проверяем, есть ли автарка - если есть - показываем её
$Files = GetUploadedFilesInfo('Users',$__USER['ID']);
if(SizeOf($Files)){
	#-------------------------------------------------------------------------------
	// файл есть, используем последний элемент массива
	$File = End($Files);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsClear','id'=>'IsClear','value'=>'yes')  );
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array(new Tag('TD',Array('colspan'=>2),new Tag('LABEL',Array('for'=>'IsClear'),'Удалить фотографию')),new Tag('TD',Array('colspan'=>6),$Comp));
	#-------------------------------------------------------------------------------
	$Table[] = Array(new Tag('TD',Array('colspan'=>7,'align'=>'right'),new Tag('IMG',Array('src'=>SPrintF('/UserFoto?UserID=%u',$__USER['ID'])))));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'UserPersonalDataChange();','value'=>'Сохранить'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Extended',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','User/Settings',new Tag('FORM',Array('name'=>'UserPersonalDataChangeForm','onsubmit'=>'return false;'),$Comp));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
