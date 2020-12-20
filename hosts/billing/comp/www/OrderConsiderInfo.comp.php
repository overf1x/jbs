<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$ServiceOrderID	= (integer) @$Args['ServiceOrderID'];
$OrderID	= (integer) @$Args['OrderID'];
$ServiceID	= (integer) @$Args['ServiceID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём сервис
$Service = DB_Select('ServicesOwners',Array('ID','Code','NameShort','Name'),Array('UNIQ','ID'=>$ServiceID));
#-------------------------------------------------------------------------------
switch(ValueOf($Service)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('SERVICE_NOT_FOUND','Указанный сервис не найден');
case 'array':
	# No more...
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// достаём данные заказа
$Where = ($ServiceOrderID?SPrintF('`ID` = %u',$ServiceOrderID):SPrintF('`OrderID` = %u',$OrderID));
#-------------------------------------------------------------------------------
$Order = DB_Select(SPrintF('%sOrdersOwners',$Service['Code']),Array('*'),Array('UNIQ','Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Order)){
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
#-------------------------------------------------------------------------------
$IsPermission = Permission_Check('HostingOrdersRead',(integer)$GLOBALS['__USER']['ID'],(integer)$Order['UserID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsPermission)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	return ERROR | @Trigger_Error(700);
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsConsiderManage = Permission_Check(SPrintF('%sOrdersConsider',$Service['Code']),(integer)$GLOBALS['__USER']['ID'],(integer)$Order['UserID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsConsiderManage)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	# No more...
	break;
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$OrdersConsider = DB_Select('OrdersConsider','*',Array('Where'=>SPrintF('`OrderID` = %u',$Order['OrderID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($OrdersConsider)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('NO_CONSIDER','Учёт отсутствует, вероятно, заказ ещё неоплачен');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
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
$DOM->AddText('Title',SPrintF('Данные учёта "%s", "%s"',(!In_Array($ServiceID,Array(40000,51000)))?$Order['Login']:SprintF('#%u/%s',$Order['OrderID'],$Order['IP']),$Service['Name']));
#-------------------------------------------------------------------------------
$Table = Array(SPrintF('Способ учета, "%s", %s',(!In_Array($ServiceID,Array(40000,51000)))?$Order['Login']:SprintF('#%u/%s',$Order['OrderID'],$Order['IP']),$Service['NameShort']));
#-------------------------------------------------------------------------------
$Row = Array();
#-------------------------------------------------------------------------------
foreach(Array('Дн. зарез.','Дн. ост.','Дн. не учт.','Цена','Скидка') as $Text)
	$Row[] = new Tag('TD',Array('class'=>'Head'),$Text);
#-------------------------------------------------------------------------------
$Rows = Array($Row);
#-------------------------------------------------------------------------------
$RemainderSumm = 0.00;
#-------------------------------------------------------------------------------
for($i=0;$i<Count($OrdersConsider);$i++){
	#-------------------------------------------------------------------------------
	$ConsiderItem = $OrdersConsider[$i];
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Percent',$ConsiderItem['Discont']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Row = Array();
	#-------------------------------------------------------------------------------
	if($IsConsiderManage){
		#-------------------------------------------------------------------------------
		foreach(Array('DaysReserved','DaysRemainded','DaysConsidered','Cost','Discont') as $ParamID){
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load(
					'Form/Input',
					Array(
						'type'  => 'text',
						'name'  => SPrintF('OrdersConsider[%u][]',$i),
						'style' => 'width: 80px',
						'value' => $ConsiderItem[$ParamID]
						)
					);
			#-------------------------------------------------------------------------------
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Row[] = new Tag('TD',$Comp);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Row[] = (integer)$ConsiderItem['DaysReserved'];
		$Row[] = (integer)$ConsiderItem['DaysRemainded'];
		$Row[] = (integer)$ConsiderItem['DaysConsidered'];
		$Row[] = (float)$ConsiderItem['Cost'];
		$Row[] = $Comp;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$RemainderSumm += (float)$ConsiderItem['Cost']*(integer)$ConsiderItem['DaysRemainded']*(1 - (float)$ConsiderItem['Discont']);
	#-------------------------------------------------------------------------------
	$Rows[] = $Row;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Extended',$Rows);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = new Tag('DIV',Array('align'=>'center'),$Comp);
#-------------------------------------------------------------------------------
if($IsConsiderManage){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'type'    => 'button',
				'onclick' => "AjaxCall('/Administrator/API/OrderConsider',FormGet(form),'Сохранение способа учета','GetURL(document.location);');",
				'value'   => 'Сохранить'
				)
			);
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div = new Tag('DIV',Array('align'=>'right'),$Comp);
	#-------------------------------------------------------------------------------
	if($RemainderSumm){
		#-------------------------------------------------------------------------------
		# дефолтовый текст возврата денег
		$Text = 'Вы действительно хотите осуществить возврат средств?';
		#-------------------------------------------------------------------------------
		# проверяем наличие условных счетов - если их нет - текст не трогаем
		# Conditionally
		$Where = SPrintF("`StatusID` = 'Conditionally' AND `UserID` = %u",$Order['UserID']);
		$Count = DB_Count('InvoicesOwners',Array('Where'=>$Where));
		#-------------------------------------------------------------------------------
		if(Is_Error($Count))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if($Count)
			$Text = SPrintF('Обратите внимание, что производится возврат на балланс для пользователя у которого есть условно оплаченные счета. %s',$Text);
		#-------------------------------------------------------------------------------
		# TODO - а надо ли? второй условный не проведёшь, как и при отрицательном баллансе
		# проверяем балланс договора на который идёт возврат - если отрицательный - текст не трогаем

		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Currency',$RemainderSumm);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'type'    => 'button',
					'onclick' => SPrintF("javascript:ShowConfirm('%s','AjaxCall(\'/Administrator/API/OrderRestore\',FormGet(OrderConsiderInfoForm),\'Возврат денег\',\'GetURL(document.location);\');');",$Text),
					'value'   => SPrintF('Вернуть %s',$Comp)
					)
				);
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Div->AddChild($Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Table[] = $Div;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('method'=>'POST','name'=>'OrderConsiderInfoForm'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'  => 'hidden',
			'name'  => 'OrderID',
			'value' => $Order['OrderID']
			)
		);
#-------------------------------------------------------------------------------
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
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
