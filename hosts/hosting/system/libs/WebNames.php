<?php
#-------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Http.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
function WebNames_Domain_Register($Settings,$DomainName,$DomainZone,$Years,$Ns1Name,$Ns1IP,$Ns2Name,$Ns2IP,$Ns3Name,$Ns3IP,$Ns4Name,$Ns4IP,$ContractID = '',$IsPrivateWhoIs,$PepsonID = 'Default',$Person = Array()){
  /****************************************************************************/
  $__args_types = Array('array','string','string','integer','string','string','string','string','string','string','string','string','boolean','string','string','array');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Http = Array(
    #---------------------------------------------------------------------------
    'Address'  => $Settings['Address'],
    'Port'     => $Settings['Port'],
    'Host'     => $Settings['Address'],
    'Protocol' => $Settings['Protocol'],
    'Charset'  => 'CP1251'
  );
  #-----------------------------------------------------------------------------
  $Domain = SPrintF('%s.%s',$DomainName,$DomainZone);
  #-----------------------------------------------------------------------------
  $Query = Array(
    #---------------------------------------------------------------------------
    'thisPage'           => 'pispRegistration',
    'username'           => $Settings['Login'],
    'password'           => $Settings['Password'],
    'domain_name'        => $Domain,
    'interface_revision' => 1,
    'interface_lang'     => 'en'
  );
  #-----------------------------------------------------------------------------
  $Query['period'] = $Years;
  #-----------------------------------------------------------------------------
  if(In_Array($DomainZone,Array('ru','su','рф'))){
    #---------------------------------------------------------------------------
    switch($PepsonID){
      case 'Natural':
        #-----------------------------------------------------------------------
        $Query['person']         = SPrintF('%s %s %s',Translit($Person['Name']),Mb_SubStr(Translit($Person['Lastname']),0,1),Translit($Person['Sourname']));
        $Query['private_person'] = ($IsPrivateWhoIs?'1':'0');
        $Query['person_r']       = SPrintF('%s %s %s',$Person['Sourname'],$Person['Name'],$Person['Lastname']);
        $Query['passport']       = SPrintF('%s %s выдан %s %s',$Person['PasportLine'],$Person['PasportNum'],$Person['PasportWhom'],$Person['PasportDate']);
        $Query['residence']      = SPrintF('%s, %s, %s, %s %s',$Person['pIndex'],$Person['pState'],$Person['pCity'],$Person['pType'],$Person['pAddress']);
        $Query['birth_date']     = $Person['BornDate'];
        $Query['country']        = IsSet($Person['PasportCountry'])?$Person['PasportCountry']:$Person['pCountry'];
        $Query['p_addr']         = SPrintF('%s, %s, %s, %s %s, %s',$Person['pIndex'],$Person['pState'],$Person['pCity'],$Person['pType'],$Person['pAddress'],$Person['pRecipient']);
        $Query['phone']          = $Person['Phone'];
	$Query['cell_phone']     = Preg_Replace('/\s+/', '', $Person['CellPhone']);
        $Query['fax']            = $Person['Fax'];
        $Query['e_mail']         = $Person['Email'];
      break;
      case 'Juridical':
        #-----------------------------------------------------------------------
        $Query['org']       = SPrintF('%s %s',Translit($Person['CompanyName']),Translit($Person['CompanyForm']));
        $Query['org_r']     = SPrintF('%s "%s"',$Person['CompanyForm'],$Person['CompanyName']);
        $Query['code']      = $Person['Inn'];
        $Query['kpp']       = $Person['Kpp'];
        $Query['country']   = $Person['jCountry'];
        $Query['address_r'] = SPrintF('%s, %s, %s, %s %s',$Person['jIndex'],$Person['pState'],$Person['jCity'],$Person['jType'],$Person['jAddress']);
        $Query['p_addr']    = SPrintF('%s, %s, %s, %s, %s %s, %s "%s"',$Person['pIndex'],$Person['pState'],$Person['pCountry'],$Person['pCity'],$Person['pType'],$Person['pAddress'],$Person['CompanyForm'],$Person['CompanyName']);
        $Query['phone']     = $Person['Phone'];
	$Query['cell_phone']= Preg_Replace('/\s+/', '', $Person['CellPhone']);
        $Query['fax']       = $Person['Fax'];
        $Query['e_mail']    = $Person['Email'];
	if(In_Array($DomainZone,Array('ru','su','рф')))
	  $Query['ogrn_org']    = $Person['Ogrn'];
      break;
      default:
        return new gException('WRONG_PROFILE_ID','Неверный идентификатор профиля');
    }
    #---------------------------------------------------------------------------
  }else{
    #---------------------------------------------------------------------------
    switch($PepsonID){
      case 'Natural':
        #-----------------------------------------------------------------------
        $Query['o_company'] = 'Private person';
        $Query['a_company'] = 'Private person';
        $Query['t_company'] = 'Private person';
        $Query['b_company'] = 'Private person';
        #-----------------------------------------------------------------------
        $Query['o_country_code'] = $Person['pCountry'];
        $Query['a_country_code'] = $Person['pCountry'];
        $Query['t_country_code'] = $Person['pCountry'];
        $Query['b_country_code'] = $Person['pCountry'];
        #-----------------------------------------------------------------------
        $Query['o_postcode'] = $Person['pIndex'];
        $Query['a_postcode'] = $Person['pIndex'];
        $Query['t_postcode'] = $Person['pIndex'];
        $Query['b_postcode'] = $Person['pIndex'];
        #-----------------------------------------------------------------------
        $Query['o_first_name'] = Translit($Person['Name']);
        $Query['a_first_name'] = Translit($Person['Name']);
        $Query['t_first_name'] = Translit($Person['Name']);
        $Query['b_first_name'] = Translit($Person['Name']);
        #-----------------------------------------------------------------------
        $Query['o_last_name'] = Translit($Person['Sourname']);
        $Query['a_last_name'] = Translit($Person['Sourname']);
        $Query['t_last_name'] = Translit($Person['Sourname']);
        $Query['b_last_name'] = Translit($Person['Sourname']);
        #-----------------------------------------------------------------------
        $Query['o_email'] = $Person['Email'];
        $Query['a_email'] = $Person['Email'];
        $Query['t_email'] = $Person['Email'];
        $Query['b_email'] = $Person['Email'];
        #-----------------------------------------------------------------------
        $Query['o_addr'] = Translit(SPrintF('%s %s',$Person['pType'],$Person['pAddress']));
        $Query['a_addr'] = Translit(SPrintF('%s %s',$Person['pType'],$Person['pAddress']));
        $Query['t_addr'] = Translit(SPrintF('%s %s',$Person['pType'],$Person['pAddress']));
        $Query['b_addr'] = Translit(SPrintF('%s %s',$Person['pType'],$Person['pAddress']));
        #-----------------------------------------------------------------------
        $Query['o_city'] = Translit($Person['pCity']);
        $Query['a_city'] = Translit($Person['pCity']);
        $Query['t_city'] = Translit($Person['pCity']);
        $Query['b_city'] = Translit($Person['pCity']);
        #-----------------------------------------------------------------------
        $Query['o_state'] = Translit($Person['pState']);
        $Query['a_state'] = Translit($Person['pState']);
        $Query['t_state'] = Translit($Person['pState']);
        $Query['b_state'] = Translit($Person['pState']);
      break;
      case 'Juridical':
        #-----------------------------------------------------------------------
        $CompanyEn = SPrintF('%s %s',Translit($Person['CompanyName']),Translit($Person['CompanyForm']));
        #-----------------------------------------------------------------------
        $Query['o_company'] = $CompanyEn;
        $Query['a_company'] = $CompanyEn;
        $Query['t_company'] = $CompanyEn;
        $Query['b_company'] = $CompanyEn;
        #-----------------------------------------------------------------------
        $Query['o_country_code'] = $Person['jCountry'];
        $Query['a_country_code'] = $Person['jCountry'];
        $Query['t_country_code'] = $Person['jCountry'];
        $Query['b_country_code'] = $Person['jCountry'];
        #-----------------------------------------------------------------------
        $Query['o_postcode'] = $Person['jIndex'];
        $Query['a_postcode'] = $Person['jIndex'];
        $Query['t_postcode'] = $Person['jIndex'];
        $Query['b_postcode'] = $Person['jIndex'];
        #-----------------------------------------------------------------------
        $Query['o_first_name'] = Translit($Person['dName']);
        $Query['a_first_name'] = Translit($Person['dName']);
        $Query['t_first_name'] = Translit($Person['dName']);
        $Query['b_first_name'] = Translit($Person['dName']);
        #-----------------------------------------------------------------------
        $Query['o_last_name'] = Translit($Person['dSourname']);
        $Query['a_last_name'] = Translit($Person['dSourname']);
        $Query['t_last_name'] = Translit($Person['dSourname']);
        $Query['b_last_name'] = Translit($Person['dSourname']);
        #-----------------------------------------------------------------------
        $Query['o_email'] = $Person['Email'];
        $Query['a_email'] = $Person['Email'];
        $Query['t_email'] = $Person['Email'];
        $Query['b_email'] = $Person['Email'];
        #-----------------------------------------------------------------------
        $Query['o_addr'] = Translit(SPrintF('%s %s',$Person['jType'],$Person['jAddress']));
        $Query['a_addr'] = Translit(SPrintF('%s %s',$Person['jType'],$Person['jAddress']));
        $Query['t_addr'] = Translit(SPrintF('%s %s',$Person['jType'],$Person['jAddress']));
        $Query['b_addr'] = Translit(SPrintF('%s %s',$Person['jType'],$Person['jAddress']));
        #-----------------------------------------------------------------------
        $Query['o_city'] = Translit($Person['jCity']);
        $Query['a_city'] = Translit($Person['jCity']);
        $Query['t_city'] = Translit($Person['jCity']);
        $Query['b_city'] = Translit($Person['jCity']);
        #-----------------------------------------------------------------------
        $Query['o_state'] = Translit($Person['jState']);
        $Query['a_state'] = Translit($Person['jState']);
        $Query['t_state'] = Translit($Person['jState']);
        $Query['b_state'] = Translit($Person['jState']);
      break;
      default:
        return new gException('WRONG_PERSON_TYPE_ID','Неверный идентификатор типа персоны');
    }
    #---------------------------------------------------------------------------
    if($DomainZone == 'kz'){
        $Query['street'] = 'Chizhevskogo, 17';
        $Query['city']   = 'Karaganda';
        $Query['sp']     = 'KAR';
    }
    #---------------------------------------------------------------------------
    $Phone = $Person['Phone'];
    #---------------------------------------------------------------------------
    if($Phone){
      #-------------------------------------------------------------------------
      $Phone = Preg_Split('/\s+/',$Phone);
      #-------------------------------------------------------------------------
      $Phone = SPrintF('%s.%s%s',Current($Phone),Next($Phone),Next($Phone));
      #-------------------------------------------------------------------------
      $Query['o_phone'] = $Phone;
      $Query['a_phone'] = $Phone;
      $Query['t_phone'] = $Phone;
      $Query['b_phone'] = $Phone;
    }else{
      #-------------------------------------------------------------------------
      $Query['o_phone'] = '';
      $Query['a_phone'] = '';
      $Query['t_phone'] = '';
      $Query['b_phone'] = '';
    }
    #---------------------------------------------------------------------------
    $Fax = $Person['Fax'];
    #---------------------------------------------------------------------------
    if($Fax){
      #-------------------------------------------------------------------------
      $Fax = Preg_Split('/\s+/',$Fax);
      #-------------------------------------------------------------------------
      $Fax = SPrintF('%s.%s%s',Current($Fax),Next($Fax),Next($Fax));
      #-------------------------------------------------------------------------
      $Query['o_fax'] = $Fax;
      $Query['a_fax'] = $Fax;
      $Query['t_fax'] = $Fax;
      $Query['b_fax'] = $Fax;
    }else{
      #-------------------------------------------------------------------------
      $Query['o_fax'] = '';
      $Query['a_fax'] = '';
      $Query['t_fax'] = '';
      $Query['b_fax'] = '';
    }
  };
  #-----------------------------------------------------------------------------
  $Query['ns0'] = $Ns1Name;
  $Query['ns1'] = $Ns2Name;
  #-----------------------------------------------------------------------------
  if($Ns3Name)
    $Query['ns3'] = $Ns3Name;
  #-----------------------------------------------------------------------------
  if($Ns4Name)
    $Query['ns4'] = $Ns4Name;
  #-----------------------------------------------------------------------------
  if($Ns1IP && $Ns2IP){
    #---------------------------------------------------------------------------
    $Query['ns0ip'] = $Ns1IP;
    $Query['ns1ip'] = $Ns2IP;
  }
  #-----------------------------------------------------------------------------
  if($Ns3IP)
    $Query['ns3ip'] = $Ns3IP;
  #-----------------------------------------------------------------------------
  if($Ns4IP)
    $Query['ns4ip'] = $Ns4IP;
  #-----------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames_Domain_Register]: не удалось выполнить запрос к серверу');
  #-----------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Success:/',$Result))
    return Array('TicketID'=>$Domain);
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  return new gException('WRONG_ANSWER',$Result);
}
#-------------------------------------------------------------------------------
function WebNames_Domain_Prolong($Settings,$DomainName,$DomainZone,$Years,$ContractID,$DomainID){
  /****************************************************************************/
  $__args_types = Array('array','string','string','integer','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Http = Array(
    #---------------------------------------------------------------------------
    'Address'  => $Settings['Address'],
    'Port'     => $Settings['Port'],
    'Host'     => $Settings['Address'],
    'Protocol' => $Settings['Protocol'],
    'Charset'  => 'CP1251'
  );
  #-----------------------------------------------------------------------------
  $Query = Array(
    #---------------------------------------------------------------------------
    'thisPage'           => 'pispRenewDomain',
    'username'           => $Settings['Login'],
    'password'           => $Settings['Password'],
    'domain_name'        => SPrintF('%s.%s',$DomainName,$DomainZone),
    'interface_revision' => 1,
    'interface_lang'     => 'en',
    'period'             => $Years
  );
  #-----------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames_Domain_Prolong]: не удалось выполнить запрос к серверу');
  #-----------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Success:/',$Result))
    return Array('TicketID'=>'NO');
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  return new gException('WRONG_ANSWER',$Result);
}
#-------------------------------------------------------------------------------
function WebNames_Domain_Ns_Change($Settings,$DomainName,$DomainZone,$ContractID,$DomainID,$Ns1Name,$Ns1IP,$Ns2Name,$Ns2IP,$Ns3Name,$Ns3IP,$Ns4Name,$Ns4IP){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Http = Array(
    #---------------------------------------------------------------------------
    'Address'  => $Settings['Address'],
    'Port'     => $Settings['Port'],
    'Host'     => $Settings['Address'],
    'Protocol' => $Settings['Protocol'],
    'Charset'  => 'CP1251'
  );
  #-----------------------------------------------------------------------------
  $Query = Array(
    #---------------------------------------------------------------------------
    'thisPage'           => 'pispRedelegation',
    'username'           => $Settings['Login'],
    'password'           => $Settings['Password'],
    'domain_name'        => SPrintF('%s.%s',$DomainName,$DomainZone),
    'interface_revision' => 1,
    'interface_lang'     => 'en'
  );
  #-----------------------------------------------------------------------------
  $Query['ns0'] = $Ns1Name;
  $Query['ns1'] = $Ns2Name;
  #-----------------------------------------------------------------------------
  if($Ns3Name)
    $Query['ns3'] = $Ns3Name;
  #-----------------------------------------------------------------------------
  if($Ns4Name)
    $Query['ns4'] = $Ns4Name;
  #-----------------------------------------------------------------------------
  if($Ns1IP && $Ns2IP){
    #---------------------------------------------------------------------------
    $Query['ns0ip'] = $Ns1IP;
    $Query['ns1ip'] = $Ns2IP;
  }
  #-----------------------------------------------------------------------------
  if($Ns3IP)
    $Query['ns2ip'] = $Ns3IP;
  #-----------------------------------------------------------------------------
  if($Ns4IP)
    $Query['ns3ip'] = $Ns4IP;
  #-----------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames__Domain_Ns_Change]: не удалось выполнить запрос к серверу');
  #-----------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Success:/',$Result))
    return Array('TicketID'=>'NO');
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  return new gException('WRONG_ANSWER',$Result);
}
#-------------------------------------------------------------------------------
function WebNames_Check_Task($Settings,$TicketID){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  if($TicketID == 'NO')
    return Array('DomainID'=>0);
  #-----------------------------------------------------------------------------
  $Http = Array(
    #---------------------------------------------------------------------------
    'Address'  => $Settings['Address'],
    'Port'     => $Settings['Port'],
    'Host'     => $Settings['Address'],
    'Protocol' => $Settings['Protocol'],
    'Charset'  => 'CP1251'
  );
  #-----------------------------------------------------------------------------
  $Query = Array(
    #---------------------------------------------------------------------------
    'thisPage'           => 'pispGetApprovalStatus',
    'username'           => $Settings['Login'],
    'password'           => $Settings['Password'],
    'domain_name'        => $TicketID,
    'interface_revision' => 1,
    'interface_lang'     => 'en'
  );
  #-----------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames_Check_Task]: не удалось выполнить запрос к серверу');
  #-----------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Success:\sDomain\sstatus\sis\s\'([A-Za-z\/]+)\'/',$Result,$Status)){
    #---------------------------------------------------------------------------
    $Status = Next($Status);
    #---------------------------------------------------------------------------
    switch($Status){
      case 'pending':
        return FALSE;
      case 'approved':
        return Array('DomainID'=>0);
      case 'errsent':
        return new gException('WRONG_CLIENT_DATA','В результате ручной проверки данных клиента регистратором были обнаружены ошибки');
      case 'N/A':
        return Array('DomainID'=>0);
      break;
      default:
        return new gException('WRONG_STATUS','Статус домена ошибочный');
    }
  }
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  return new gException('WRONG_ANSWER',$Result);
}
#-------------------------------------------------------------------------------
function WebNames_Get_Balance($Settings){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Http = Array(
    #---------------------------------------------------------------------------
    'Address'  => $Settings['Address'],
    'Port'     => $Settings['Port'],
    'Host'     => $Settings['Address'],
    'Protocol' => $Settings['Protocol'],
    'Charset'  => 'CP1251'
  );
  #-----------------------------------------------------------------------------
  $Query = Array(
    #---------------------------------------------------------------------------
    'thisPage'           => 'pispBalance',
    'username'           => $Settings['Login'],
    'password'           => $Settings['Password'],
    'interface_revision' => 1,
    'interface_lang'     => 'en'
  );
  #-----------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames_Check_Task]: не удалось выполнить запрос к серверу');
  #-----------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Success:\sbalance\s-\s/',$Result)){
        Preg_Match('/([0-9]|\.)+/',$Result,$Prepay);
    return Array('Prepay'=>$Prepay[0]);
  }
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  return new gException('WRONG_ANSWER',$Result);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function WebNames_Is_Available_Domain($Settings,$Domain){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  # ввиду того, что вебнеймс интерфейса нормального не предоставляет, а за частые
  # запросы банит, кэшируем полученный результат и юазем кэш
  $CacheID = Md5($Settings['Login'] . $Settings['Password'] . 'pispAllDomainsInfo');
  $Result = CacheManager::get($CacheID);
  # если результата нет - лезем в вебнеймс
  if(!$Result || SizeOf($Result) < 2){
    $Http = Array(
      #---------------------------------------------------------------------------
      'Address'  => $Settings['Address'],
      'Port'     => $Settings['Port'],
      'Host'     => $Settings['Address'],
      'Protocol' => $Settings['Protocol'],
      'Charset'  => 'CP1251'
      );
    #-----------------------------------------------------------------------------
    $Query = Array(
      #---------------------------------------------------------------------------
      'thisPage'           => 'pispAllDomainsInfo',	# see JBS-252
      'username'           => $Settings['Login'],
      'password'           => $Settings['Password'],
      'interface_revision' => 1,
      'interface_lang'     => 'en'
      );
    #-----------------------------------------------------------------------------
    $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
    if(Is_Error($Result))
      return ERROR | @Trigger_Error('[WebNames_Is_Available_Domain]: не удалось выполнить запрос к серверу');
    #-----------------------------------------------------------------------------
    $Result = Trim($Result['Body']);
    #-----------------------------------------------------------------------------
    if(Preg_Match('/Error:/',$Result))
      return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
    #-----------------------------------------------------------------------------
    if(!Preg_Match('/Success:/',$Result))
      return ERROR | @Trigger_Error('[WebNames_Is_Available_Domain]: неизвестный ответ');
    #-----------------------------------------------------------------------------
    # кэшируем полученный результат
	CacheManager::add($CacheID, $Result, 3600);
  }
  #Debug("[system/libs/WebNames][WebNames_Is_Available_Domain]: " . print_r($Result,true));
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  # разбираем строчки на массив
  $iDomains = Explode("\n", $Result);
  #-----------------------------------------------------------------------------
  # перебираем массив, ищщем нужный домен
  foreach($iDomains as $iDomain){
    # Domain f-box59.ru; Status N; CreationDate 2010-02-23; ExpirationDate 2012-02-23; FutureExpDate ;
    #Debug("[system/libs/WebNames][WebNames_Is_Available_Domain]: " . $iDomain);
    $DomainInfo = Explode(" ",$iDomain);
    #Debug("[system/libs/WebNames][WebNames_Is_Available_Domain]: " . print_r($DomainInfo,true));
    if(StrToLower(Trim($DomainInfo[1])) == StrToLower($Domain) . ';'){
      # домен есть на аккаунте
      return Array('Status'=>'true','ServiceID'=>'0');
    }
  }
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  return Array('Status'=>'false','ErrorText'=>'Domain not found');
  #-----------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------

# added by lissyara, for JBS-353, 2012-03-19 in 14:00 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function WebNames_Change_Contact_Detail($Settings,$Domain,$Person){
  /****************************************************************************/
  $__args_types = Array('array','string','array');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  // phone, e_mail, cell_phone
  #-------------------------------------------------------------------------------
  $Http = Array(
                #---------------------------------------------------------------------------
	        'Address'  => $Settings['Address'],
                'Port'     => $Settings['Port'],
                'Host'     => $Settings['Address'],
                'Protocol' => $Settings['Protocol'],
                'Charset'  => 'CP1251'
               );
  #-------------------------------------------------------------------------------
  $Query = Array(
                 'thisPage'           => 'pispContactDetails',
                 'username'           => $Settings['Login'],
                 'password'           => $Settings['Password'],
                 'interface_revision' => 1,
                 'interface_lang'     => 'en',
                 'domain_name'        => $Domain,
                );
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  if(IsSet($Person['Phone']))
    $Query['phone'] = $Person['Phone'];
  #-------------------------------------------------------------------------------
  if(IsSet($Person['CellPhone']))
    $Query['cell_phone'] = Str_Replace(' ','',$Person['CellPhone']);
  #-------------------------------------------------------------------------------
  if(IsSet($Person['Email']))
    $Query['e_mail'] = $Person['Email'];
  #-------------------------------------------------------------------------------
  if(IsSet($Person['PostalAddress']))
    $Query['p_addr'] = $Person['PostalAddress'];
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames_Change_Contact_Detail]: не удалось выполнить запрос к серверу');
  #-------------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  if(!Preg_Match('/Success:/',$Result))
    return ERROR | @Trigger_Error('[WebNames_Change_Contact_Detail]: неизвестный ответ');
  #-----------------------------------------------------------------------------
  if(Preg_Match('/Success:/',$Result))
      return Array('TicketID'=>'NO');
  #-------------------------------------------------------------------------------
}

# added by lissyara, for JBS-353, 2012-03-19 in 20:21 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function WebNames_Get_Contact_Detail($Settings,$Domain){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $CacheID = Md5(SPrintF('Get_Contact_Detail_%s',$Domain));
  #-------------------------------------------------------------------------------
  $Result = CacheManager::get($CacheID);
  if($Result)
    return $Result;
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  // phone, e_mail, cell_phone
  #-------------------------------------------------------------------------------
  $Http = Array(
                #---------------------------------------------------------------------------
	        'Address'  => $Settings['Address'],
                'Port'     => $Settings['Port'],
                'Host'     => $Settings['Address'],
                'Protocol' => $Settings['Protocol'],
                'Charset'  => 'CP1251'
               );
  #-------------------------------------------------------------------------------
  $Query = Array(
                 'thisPage'           => 'pispGetContactDetails',
                 'username'           => $Settings['Login'],
                 'password'           => $Settings['Password'],
                 'interface_revision' => 1,
                 'interface_lang'     => 'en',
                 'domain_name'        => $Domain,
                );
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames_Get_Contact_Detail]: не удалось выполнить запрос к серверу');
  #-------------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  if(!Preg_Match('/Success:/',$Result))
    return ERROR | @Trigger_Error('[WebNames_Get_Contact_Detail]: неизвестный ответ');
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  $ContactInfo = Array();
  $FullInfo    = Array();
  #-------------------------------------------------------------------------------
  $iContactData = Explode("\n", $Result);
  #-------------------------------------------------------------------------------
  foreach($iContactData as $Line){
   $ContactData = Explode(": ",$Line);
   #-------------------------------------------------------------------------------
   if(Trim($ContactData[0]) == 'e_mail')
     $ContactInfo['Email'] = SubStr($ContactData[1],0,-1);
   #-------------------------------------------------------------------------------
   if(Trim($ContactData[0]) == 'phone')
     $ContactInfo['Phone'] = SubStr($ContactData[1],0,-1);
   #-------------------------------------------------------------------------------
   if(Trim($ContactData[0]) == 'cell_phone')
     $ContactInfo['CellPhone'] = SubStr($ContactData[1],0,-1);
   #-------------------------------------------------------------------------------
   if(Trim($ContactData[0]) == 'p_addr')
     $ContactInfo['PostalAddress'] = SubStr($ContactData[1],0,-1);
   #-------------------------------------------------------------------------------
   # буржуйские домены
   if(Trim($ContactData[0]) == 'o_phone')
     $ContactInfo['Phone'] = SubStr($ContactData[1],0,-1);
   #-------------------------------------------------------------------------------
   if(Trim($ContactData[0]) == 'o_email')
     $ContactInfo['Email'] = SubStr($ContactData[1],0,-1);
   #-------------------------------------------------------------------------------
   # полная информация
   if(IsSet($ContactData[1]))
     $FullInfo[Trim($ContactData[0])] = SubStr($ContactData[1],0,-1);
  }
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  $Out = Array('ContactInfo'=>$ContactInfo,'FullInfo'=>$FullInfo);
  #-------------------------------------------------------------------------------
  CacheManager::add($CacheID,$Out,300);
  #-------------------------------------------------------------------------------
  return $Out;
}


# added by lissyara, for JBS-394, 2012-09-26 in 14:28 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function WebNames_Get_List_Domains($Settings){
  /****************************************************************************/
  $__args_types = Array('array','string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  # ввиду того, что вебнеймс интерфейса нормального не предоставляет, а за частые
  # запросы банит, кэшируем полученный результат и юазем кэш
  $CacheID = Md5($Settings['Login'] . $Settings['Password'] . 'pispAllDomainsInfo');
  $Result = CacheManager::get($CacheID);
  # если результата нет - лезем в вебнеймс
  if(!$Result || SizeOf($Result) < 2){
    $Http = Array(
      #---------------------------------------------------------------------------
      'Address'  => $Settings['Address'],
      'Port'     => $Settings['Port'],
      'Host'     => $Settings['Address'],
      'Protocol' => $Settings['Protocol'],
      'Charset'  => 'UTF-8'
      );
    #-----------------------------------------------------------------------------
    $Query = Array(
      #---------------------------------------------------------------------------
      'thisPage'           => 'pispAllDomainsInfo',	# see JBS-252
      'username'           => $Settings['Login'],
      'password'           => $Settings['Password'],
      'interface_revision' => 1,
      'interface_lang'     => 'en'
      );
    #-----------------------------------------------------------------------------
    $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
    if(Is_Error($Result))
      return ERROR | @Trigger_Error('[WebNames_Get_List_Domains]: не удалось выполнить запрос к серверу');
    #-----------------------------------------------------------------------------
    $Result = Trim($Result['Body']);
    #-----------------------------------------------------------------------------
    if(Preg_Match('/Error:/',$Result))
      return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
    #-----------------------------------------------------------------------------
    if(!Preg_Match('/Success:/',$Result))
      return ERROR | @Trigger_Error('[WebNames_Get_List_Domains]: неизвестный ответ');
    #-----------------------------------------------------------------------------
    # кэшируем полученный результат
    CacheManager::add($CacheID, $Result, 3600);
  }
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  # разбираем строчки на массив
  $Domains = Explode("\n", $Result);
  Debug('[WebNames_Get_List_Domains]: ' . print_r($Domains,true));
  #-----------------------------------------------------------------------------
  # перебираем массив, составляем массив на выхлоп функции
  $Out = Array();
  foreach($Domains as $Domain){
    # Domain f-box59.ru; Status N; CreationDate 2010-02-23; ExpirationDate 2012-02-23; FutureExpDate ;
    $DomainInfo = Explode(" ",$Domain);
    # добавляем домен в выхлоп, если он есть вообще
    if(StrLen(Trim($DomainInfo[1])) > 3){
      $Out[] = Str_Replace(';','',StrToLower(Trim($DomainInfo[1])));
    }
  }
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  if(SizeOf($Out) > 0){
  	return Array('Status'=>'true','Domains'=>$Out);
  }else{
	return Array('Status'=>'false','ErrorText'=>'No domains on account');
  }
  #-----------------------------------------------------------------------------
}

# added by lissyara, for JBS-122, 2013-02-06 in 17:22 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function WebNames_Domain_Transfer($Settings,$DomainName,$DomainZone,$Params){
  #-------------------------------------------------------------------------------
  if(In_Array($DomainZone,Array('ru','su','рф'))){
    # ну до того там мутно всё...
    # пеернос этих доменов по параметрам аналогичен регистрации.
    # только 'thisPage' другой
    return new gException('REGISTRATOR_ERROR',SPrintF("В текущей версии библиотеки перенос доменов в зоне '%s' не реализован.",$DomainZone));
  }
  #-------------------------------------------------------------------------------
  $Http = Array(
                #---------------------------------------------------------------------------
	        'Address'  => $Settings['Address'],
                'Port'     => $Settings['Port'],
                'Host'     => $Settings['Address'],
                'Protocol' => $Settings['Protocol'],
                'Charset'  => 'CP1251'
               );
  #-------------------------------------------------------------------------------
  $Query = Array(
                 'thisPage'           => 'pispInitiateTransfer',
                 'username'           => $Settings['Login'],
                 'password'           => $Settings['Password'],
                 'interface_revision' => 1,
                 'interface_lang'     => 'en',
                 'domain_name'        => SPrintF('%s.%s',$DomainName,$DomainZone),
		 'notpaid'            => 0,
		 'period'             => 1,
		 'authinfo'           => $Params['AuthInfo']
                );
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  $Result = Http_Send('/RegTimeSRS.pl',$Http,Array(),$Query);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[WebNames_Domain_Transfer]: не удалось выполнить запрос к серверу');
  #-------------------------------------------------------------------------------
  $Result = Trim($Result['Body']);
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  if(Preg_Match('/Error:/',$Result))
    return new gException('REGISTRATOR_ERROR','Регистратор вернул ошибку');
    #return new gException('REGISTRATOR_ERROR',IsSet($Result['error_text'])?$Result['error_text']:'Регистратор вернул ошибку');
  #-----------------------------------------------------------------------------
  if(!Preg_Match('/Success:/',$Result))
    return ERROR | @Trigger_Error('[WebNames_Domain_Transfer]: неизвестный ответ');
  #-------------------------------------------------------------------------------
  #-------------------------------------------------------------------------------
  return Array('DomainID'=>0);
}





?>
