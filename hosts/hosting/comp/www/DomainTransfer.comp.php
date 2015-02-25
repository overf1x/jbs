<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$DomainName     =  (string) @$Args['DomainName'];
$DomainSchemeID = (integer) @$Args['DomainSchemeID'];
$ContractID     = (integer) @$Args['ContractID'];
$StepID         = (integer) @$Args['StepID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/WhoIs.php')))
	return ERROR | @Trigger_Error(500);
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
$DOM->AddText('Title','Перенос домена');
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/DomainTransfer.js}')));
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'DomainTransferForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['User']['Orders']['Domain'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($StepID){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'ContractID','type'=>'hidden','value'=>$ContractID));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$DomainName = Mb_StrToLower($DomainName,'UTF-8');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Regulars = Regulars();
	#-------------------------------------------------------------------------------
	if(!Preg_Match($Regulars[(($Settings['Transfer']['IsSelectRegistrator'])?'DomainName':'Domain')],$DomainName))
		return new gException('WRONG_DOMAIN_NAME','Неверное доменное имя');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!$Settings['Transfer']['IsSelectRegistrator']){
		#-------------------------------------------------------------------------------
		$DomainZone = SubStr($DomainName, StrPos($DomainName,'.') + 1, StrLen($DomainName));
		#-------------------------------------------------------------------------------
		$UniqID = UniqID('DomainSchemes');
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Services/Schemes','DomainSchemes',$__USER['ID'],Array('Name','ServerID'),$UniqID);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Columns = Array('ID','Name','ServerID','CostProlong','(SELECT `Params` FROM `Servers` WHERE `ServerID` = `Servers`.`ID`) as `Params`');
		#-------------------------------------------------------------------------------
		$DomainSchemes = DB_Select($UniqID,$Columns,Array('UNIQ','Limits'=>Array(0,1),'SortOn'=>Array('SortID'),'Where'=>Array("`IsTransfer` = 'yes'",SPrintF("`Name` = '%s'",$DomainZone))));
		#-------------------------------------------------------------------------------
		switch(ValueOf($DomainSchemes)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return new gException('DOMAINS_SCHEME_NOT_SUPPORTED','Доменная зона не поддерживается');
		case 'array':
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		$DomainSchemeID = $DomainSchemes['ID'];
		#-------------------------------------------------------------------------------
		$DomainName = SubStr($DomainName,0,StrPos($DomainName,'.'));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'DomainName','type'=>'hidden','value'=>$DomainName));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table = Array('Общая информация');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!$DomainSchemeID)
		return new gException('DOMAIN_SCHEME_NOT_DEFINED','Доменная зона не выбрана');
	#-------------------------------------------------------------------------------
	$Columns = Array('`DomainSchemes`.`ID`','`DomainSchemes`.`Name` as `Name`','`DomainSchemes`.`IsTransfer` AS `IsTransfer`','`Servers`.`Params` as `Params`','DaysAfterTransfer','DaysBeforeTransfer');
	#-------------------------------------------------------------------------------
	$DomainScheme = DB_Select(Array('DomainSchemes','Servers'),$Columns,Array('UNIQ','Where'=>SPrintF('`DomainSchemes`.`ServerID` = `Servers`.`ID` AND `DomainSchemes`.`ID` = %u',$DomainSchemeID)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DomainScheme)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('DOMAIN_SCHEME_NOT_FOUND','Тарифный план не найден');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!$DomainScheme['IsTransfer'])
		return new gException('SCHEME_NOT_ALLOW_TRANSFER','Выбранный тарифный план заказа домена не позволяет перенос');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$WhoIs = WhoIs_Check($DomainName,$DomainScheme['Name']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($WhoIs)){
	case 'exception':
		return new Tag('WHOIS_ERROR','Ошибка получения данных WhoIs',$WhoIs);
	case 'true':
		return new gException('DOMAIN_IS_FREE','Выбранный Вами домен свободен');
	case 'error':
		# No more...
	case 'false':
		# No more...
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/DomainTransfer]: WhoIs = %s',print_r($WhoIs,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(($WhoIs['ExpirationDate'] - Time()) / 86400 < $DomainScheme['DaysBeforeTransfer'])
		return new gException('DOMAIN_NEED_PROLONG',SPrintF('Перенос домена невозможен менее чем за %s дней до даты его продления. Для переноса, необходимо его продлить у текущего регистратора',$DomainScheme['DaysBeforeTransfer']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# реализация JBS-825
	if(!$Settings['Transfer']['IsSelectRegistrator']){
		#-------------------------------------------------------------------------------
		$Registrar = IsSet($WhoIs['Registrar'])?$WhoIs['Registrar']:'NOT_FOUND';
		#-------------------------------------------------------------------------------
		$UniqID = UniqID('DomainSchemes');
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Services/Schemes','DomainSchemes',$__USER['ID'],Array('Name','ServerID'),$UniqID);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Columns = Array('ID','Name','ServerID','CostProlong','(SELECT `Params` FROM `Servers` WHERE `ServerID` = `Servers`.`ID`) as `Params`');
		#-------------------------------------------------------------------------------
		$DomainSchemes = DB_Select($UniqID,$Columns,Array('SortOn'=>Array('SortID'),'Where'=>Array("`IsTransfer` = 'yes'",SPrintF("`Name` = '%s'",$DomainScheme['Name']))));
		#-------------------------------------------------------------------------------
		switch(ValueOf($DomainSchemes)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return new gException('DOMAINS_SCHEME_NOT_SUPPORTED','Доменная зона не поддерживается');
		case 'array':
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		foreach($DomainSchemes as $Scheme)
			if(Preg_Match(SPrintF('/%s/',$Scheme['Params']['PrefixNic']),$Registrar))
				$DomainScheme = $Scheme;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'DomainSchemeID','type'=>'hidden','value'=>$DomainScheme['ID']));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'DomainZone','type'=>'hidden','value'=>$DomainScheme['Name']));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = Array('Доменное имя',SPrintF('%s.%s | %s',$DomainName,$DomainScheme['Name'],$DomainScheme['Params']['Name']));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = new Tag('TD',Array('colspan'=>2,'width'=>300,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),'Для осуществления переноса необходима подготовка определённого пакета документов. В ближайшее время Вам будет выслана инструкция и необходимая для переноса информация.');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$IsSupportContracts = $DomainScheme['Params']['IsSupportContracts'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!In_Array($DomainScheme['Name'],Array('ru','su','рф'))){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'AuthInfo','prompt'=>'Пароль (authinfo) домена. Для переноса домена, его необходимо получить у прежнего регистратора.'));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array('Код переноса домена (AuthInfo)',$Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'PersonID'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Adding = new Tag('NOBODY',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY',new Tag('SPAN','Укажите Ваш договор с регистратором или оставьте поле пустым'));
	#-------------------------------------------------------------------------------
	if($DomainScheme['Params']['PersonID']){
		#-------------------------------------------------------------------------------
		$NoBody->AddChild(new Tag('BR'));
		#-------------------------------------------------------------------------------
		$NoBody->AddChild(new Tag('SPAN',Array('class'=>'Comment'),new Tag('SPAN',$DomainScheme['Params']['PersonID'])));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Table[] = Array($NoBody,$Adding);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>'DomainTransfer();','value'=>'Продолжить'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Standard',$Table);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Contracts = DB_Select('Contracts',Array('ID','TypeID','Customer'),Array('Where'=>SPrintF('`UserID` = %u',$GLOBALS['__USER']['ID'])));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Contracts)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('CONTRACTS_NOT_FOUND','Система не обнаружила у Вас ни одного договора. Пожалуйста, перейдите в раздел [Мой офис - Договоры] и сформируйте хотя бы один договор');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Options = Array();
	#-------------------------------------------------------------------------------
	foreach($Contracts as $Contract){
		#-------------------------------------------------------------------------------
		$Customer = $Contract['Customer'];
		#-------------------------------------------------------------------------------
		if(Mb_StrLen($Customer) > 20)
			$Customer = SPrintF('%s...',Mb_SubStr($Customer,0,20));
		#-------------------------------------------------------------------------------
		$Options[$Contract['ID']] = $Customer;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'ContractID'),$Options,$ContractID);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY',$Comp);
	#-------------------------------------------------------------------------------
	$Window = JSON_Encode(Array('Url'=>'/DomainTransfer','Args'=>Array()));
	#-------------------------------------------------------------------------------
	$A = new Tag('A',Array('href'=>SPrintF("javascript:ShowWindow('/ContractMake',{Window:'%s'});",Base64_Encode($Window))),'[новый]');
	#-------------------------------------------------------------------------------
	$NoBody->AddChild($A);
	#-------------------------------------------------------------------------------
	$Table = Array(Array('Базовый договор',$NoBody));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = new Tag('TD',Array('colspan'=>2,'width'=>300,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),'Домены в зонах ru/su/рф переносятся без дополнительных условий, во всех остальных зонах переносятся с оплатой продления на год. Ниже приведены цены на продление Вашего доменного имени.');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$UniqID = UniqID('DomainSchemes');
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Services/Schemes','DomainSchemes',$__USER['ID'],Array('Name','ServerID'),$UniqID);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Columns = Array('ID','Name','ServerID','CostProlong','(SELECT `Params` FROM `Servers` WHERE `ServerID` = `Servers`.`ID`) as `Params`','(SELECT `SortID` FROM `Servers` WHERE `ServerID` = `Servers`.`ID`) as `ServerSortID`');
	#-------------------------------------------------------------------------------
	$DomainSchemes = DB_Select($UniqID,$Columns,Array('SortOn'=>Array('ServerSortID','SortID'),'Where'=>"`IsTransfer` = 'yes'"));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DomainSchemes)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('DOMAINS_SCHEMES_NOT_FOUND','Тарифные планы на домены не определены');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Messages = Messages();
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'DomainName','type'=>'text','value'=>$DomainName,'prompt'=>$Messages['Prompts']['DomainName']));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if($Settings['Transfer']['IsSelectRegistrator'])
		$Comp->AddAttribs(Array('onblur'=>'TrimDomainName(this);'));
	#-------------------------------------------------------------------------------
	$Table[] = Array('Доменное имя',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Rows = Array();
	#-------------------------------------------------------------------------------
	$Tr = new Tag('TR');
	#-------------------------------------------------------------------------------
	$ServerName = UniqID();
	#-------------------------------------------------------------------------------
	foreach($DomainSchemes as $DomainScheme){
		#-------------------------------------------------------------------------------
		if($ServerName != $DomainScheme['Params']['Name']){
			#-------------------------------------------------------------------------------
			$ServerName = $DomainScheme['Params']['Name'];
			#-------------------------------------------------------------------------------
			if(Count($Tr->Childs)){
				#-------------------------------------------------------------------------------
				$Rows[] = $Tr;
				#-------------------------------------------------------------------------------
				$Tr = new Tag('TR');
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Formats/String',$DomainScheme['Params']['Comment'],25);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Rows[] = new Tag('TR',new Tag('TD',Array('colspan'=>6,'class'=>'Separator'),new Tag('SPAN',Array('style'=>'font-size:16px;'),SPrintF('%s |',$DomainScheme['Params']['Name'])),new Tag('SPAN',$Comp)));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Input',Array('name'=>'DomainSchemeID','type'=>'radio','value'=>$DomainScheme['ID']));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if($DomainScheme['ID'] == $DomainSchemeID)
			$Comp->AddAttribs(Array('checked'=>'true'));
		#-------------------------------------------------------------------------------
		if(!$Settings['Transfer']['IsSelectRegistrator'])
			$Comp->AddAttribs(Array('disabled'=>'true'));
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('width'=>20),$Comp));
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('class'=>'Comment'),$DomainScheme['Name']));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Currency',$DomainScheme['CostProlong']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('class'=>'Standard','align'=>'right'),$Comp));
		#-------------------------------------------------------------------------------
		if(Count($Tr->Childs)%6 == 0){
			#-------------------------------------------------------------------------------
			$Rows[] = $Tr;
			#-------------------------------------------------------------------------------
			$Tr = new Tag('TR');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(Count($Tr->Childs))
		$Rows[] = $Tr;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Extended',$Rows,Array('align'=>'center'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>"ShowWindow('/DomainTransfer',FormGet(form));",'value'=>'Продолжить'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Standard',$Table);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'hidden','name'=>'StepID','value'=>1));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
$Out = $DOM->Build(FALSE);
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
