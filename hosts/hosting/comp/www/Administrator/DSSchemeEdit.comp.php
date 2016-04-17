<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$DSSchemeID = (integer) @$Args['DSSchemeID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($DSSchemeID){
	#-------------------------------------------------------------------------------
	$DSScheme = DB_Select('DSSchemes','*',Array('UNIQ','ID'=>$DSSchemeID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DSScheme)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		# No more...
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$DSScheme = Array(
			'GroupID'			=> 1,
			'UserID'			=> 1,
			'Name'				=> 'DedicOne',
			'PackageID'			=> 'd1',
			'CostDay'			=> 100,
			'CostMonth'			=> 3000,
			'CostInstall'			=> 300,
			'ServerID'			=> 1,
			'IsActive'			=> TRUE,
			'IsBroken'			=> FALSE,
			'IsProlong'			=> TRUE,
			'MinDaysPay'			=> 31,
			'MinDaysProlong'		=> 14,
			'MaxDaysPay'			=> 1460,
			'MaxOrders'			=> 0,
			'MinOrdersPeriod'		=> 0,
			'SortID'			=> 10,
			'CPU'				=> '2x Opteron 2GHz, 2 ядра',
			'ram'				=> 2048,
			'raid'				=> '3Ware 9650SE-4LPML, 256Mb cache',
			'disks'				=> 'SATA 500Gb + SATA 500Gb',
			'chrate'			=> 8,
			'trafflimit'			=> 1000,
			'traffcorrelation'		=> '1:4',
			'OS'				=> 'FreeBSD 10.1',
			'UserNotice'			=> 'Идеальный сервер для высоконагруженного проекта ...',
			'AdminNotice'			=> 'второй диск скоро посыпется, надо заменить',
			);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Messages = Messages();
#-------------------------------------------------------------------------------
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
$Title = ($DSSchemeID?SPrintF('Редактирование тарифа "%s"',$DSScheme['Name']):'Добавление нового выделенного сервера');
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Owner','Владелец тарифа',$DSScheme['GroupID'],$DSScheme['UserID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'Name','value'=>$DSScheme['Name']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Название тарифного плана',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'PackageID','value'=>$DSScheme['PackageID']),'Точное имя пакета в панели управления');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Идентификатор пакета в панели',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',Array('name'=>'CostDay','value'=>SPrintF('%01.2f',$DSScheme['CostDay'])));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Стоимость дня'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Используется в расчетах стоимости')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',Array('name'=>'CostMonth','value'=>SPrintF('%01.2f',$DSScheme['CostMonth'])));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Стоимость месяца'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Используется для отображения')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',Array('name'=>'CostInstall','value'=>SPrintF('%01.2f',$DSScheme['CostInstall'])));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Стоимость установки/подключения',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Servers = DB_Select('Servers',Array('ID','Address'),Array('Where'=>'(SELECT `ServiceID` FROM `ServersGroups` WHERE `ServersGroups`.`ID` = `Servers`.`ServersGroupID`) = 40000','SortOn'=>'Address'));
#-------------------------------------------------------------------------------
switch(ValueOf($Servers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('SERVERS_NOT_FOUND','Группа управляющих серверов не найдена. Добавьте группу серверов для сервиса "Выделенный сервер" и хотя бы один управляющий сервер в неё');
case 'array':
	# No more...
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
foreach($Servers as $Server)
	$Options[$Server['ID']] = $Server['Address'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ServerID','style'=>'width: 240px;','prompt'=>'Сервер управляющий выделенными серверами'),$Options,$DSScheme['ServerID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Управляющий сервер',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsActive','value'=>'yes'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($DSScheme['IsActive'])
	$Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsActive\'); return false;'),'Тариф активен'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsBroken','value'=>'yes'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($DSScheme['IsBroken'])
	$Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsBroken\'); return false;'),'Сервер сломан'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsProlong','value'=>'yes'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($DSScheme['IsProlong'])
	$Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsProlong\'); return false;'),'Возможность продления'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'MinDaysPay','value'=>$DSScheme['MinDaysPay']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Минимальное кол-во дней оплаты',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'MinDaysProlong','value'=>$DSScheme['MinDaysProlong'],'prompt'=>'Минимальное число дней, на которое можно продлевать заказ'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Минимальное кол-во дней продления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'MaxDaysPay','value'=>$DSScheme['MaxDaysPay']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальное кол-во дней оплаты',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'MaxOrders','value' => $DSScheme['MaxOrders'],'prompt'=>$Messages['Prompts']['MaxOrders']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальное кол-во заказов',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'MinOrdersPeriod','value'=>$DSScheme['MinOrdersPeriod'],'prompt'=>$Messages['Prompts']['MinOrdersPeriod']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Минимальный период между заказами',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'SortID','value'=>$DSScheme['SortID']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порядок сортировки',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Технические характеристики сервера';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',	Array('type'=>'text','name'=>'CPU','value'=>$DSScheme['CPU']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Процессоры',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
	'Form/Input',
	Array(
		'type'  => 'text',
		'name'  => 'ram',
		'value' => $DSScheme['ram']
	)
);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);

$Table[] = Array('Объём оперативной памяти, Mb',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
	'Form/Input',
	Array(
		'type'  => 'text',
		'name'  => 'raid',
		'value' => $DSScheme['raid']
	)
);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);

$Table[] = Array('Тип RAID контроллера',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'disks','value'=>$DSScheme['disks']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Характеристики жёстких дисков',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Прочая информация';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input', Array('type'=>'text','name'=>'chrate','value'=>$DSScheme['chrate']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Скорость канала, мегабит',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'trafflimit','value'=>$DSScheme['trafflimit']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Месячный трафик, Gb',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name' =>'traffcorrelation','value'=>$DSScheme['traffcorrelation']));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Соотношения трафика, входящий/исходящий',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input', Array('type'=>'text','name'=>'OS','value'=>$DSScheme['OS'],'prompt'=>'Предустановленная, по умолчнию, операционная система'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Предустановленная ОС',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input', Array('type'=>'text','name'=>'Switch','value'=>$DSScheme['Switch'],'prompt'=>'Порт коммутатора к которому подключен сервер. Используется только при ручном управлении через собственные скрипты, поэтому вводить можно в каком угодно виде, хоть номер порта, хоть с указанием свича - обрабатываться введённое значение будет вашими скриптами. Например: "24" или "ex2200 ge-0/0/24"'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порт коммутатора',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Описание сервера, для пользователя';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',Array('name'=>'UserNotice','style'=>'width:100%;','rows'=>3),$DSScheme['UserNotice']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Заметка по серверу, для администратора';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',Array('name'=>'AdminNotice','style'=>'width:100%;','rows'=>3),$DSScheme['AdminNotice']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Div = new Tag('DIV',Array('align'=>'right'));
#-------------------------------------------------------------------------------
if($DSSchemeID){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','onclick'=>'form.DSSchemeID.value = (checked?0:value);','value'=>$DSSchemeID));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Div->AddChild(new Tag('SPAN',Array('class'=>'Comment'),'создать новый тариф'));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>SPrintF("FormEdit('/Administrator/API/DSSchemeEdit','DSSchemeEditForm','%s');",$Title),'value'=>($DSSchemeID?'Сохранить':'Добавить')));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Div->AddChild($Comp);
#-------------------------------------------------------------------------------
$Table[] = $Div;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'DSSchemeEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('name'=>'DSSchemeID','type'=>'hidden','value'=>$DSSchemeID));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
