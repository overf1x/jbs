<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Vitaly Velikodnyy
 *
 */
class DSOrdersDeletedMsg extends Message {
	#-------------------------------------------------------------------------------
	public function __construct(array $params, $toUser) {
		#-------------------------------------------------------------------------------
		parent::__construct('DSOrdersDeleted', $toUser, $params);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	public function getParams() {
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('params = %s',print_r($this->params,true)));
		#-------------------------------------------------------------------------------
		$DSScheme = DB_Select('DSSchemes', Array('*'), Array('UNIQ', 'Where' => SPrintF('`ID` = %u',$this->params['SchemeID'])));
		#-------------------------------------------------------------------------------
		if (!Is_Array($DSScheme))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$this->params['DSScheme'] = $DSScheme;
		#-------------------------------------------------------------------------------
		return $this->params;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
