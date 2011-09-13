<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2005-2008 Mario Matzulla
 * (c) 2005-2008 Christian Technology Ministries International Inc.
 * All rights reserved
 *
 * This file is part of the Web-Empowered Church (WEC)
 * (http://WebEmpoweredChurch.org) ministry of Christian Technology Ministries 
 * International (http://CTMIinc.org). The WEC is developing TYPO3-based
 * (http://typo3.org) free software for churches around the world. Our desire
 * is to use the Internet to help offer new life through Jesus Christ. Please
 * see http://WebEmpoweredChurch.org/Jesus.
 *
 * You can redistribute this file and/or modify it under the terms of the 
 * GNU General Public License as published by the Free Software Foundation;
 * either version 2 of the License, or (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This file is distributed in the hope that it will be useful for ministry,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the file!
 ***************************************************************/

require_once(t3lib_extMgm::extPath('cal').'service/class.tx_cal_base_service.php');
require_once (t3lib_extMgm :: extPath('cal') . 'model/class.tx_cal_attendee_model.php');

/**
 * Base model for the category.  Provides basic model functionality that other
 * models can use or override by extending the class.  
 *
 * @author Mario Matzulla <mario@matzullas.de>
 * @package TYPO3
 * @subpackage cal
 */
class tx_cal_attendee_service extends tx_cal_base_service {
	
	function tx_cal_attendee_service(){
		$this->tx_cal_base_service();
	}
		
	/**
	 * Looks for an attendee with a given uid on a certain pid-list
	 * @param	integer		$uid		The uid to search for
	 * @param	string		$pidList	The pid-list to search in
	 * @return	array		An array ($row)
	 */
	function find($uid, $pidList){
		$foundAttendees = Array();
		$select = 'tx_cal_attendee.*';
		$table = 'tx_cal_attendee, tx_cal_event_attendee_mm';
		$where = 'tx_cal_attendee.deleted=0 AND tx_cal_attendee.hidden=0 AND tx_cal_attendee.uid = '.$uid;
		if($pidList){
			$where .= ' AND tx_cal_attendee.pid IN ('.$pidList.')';
		}
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select,$table,$where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$foundAttendees[] = $this->createAttendee($row);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		if($foundAttendees[0]){
			return $foundAttendees[0];
		}
		return 'none';
	}
	
	
	/**
	 * Looks for all attendees on a certain pid-list
	 * @param	string		$pidList	The pid-list to search in
	 * @return	array	An array of array (array of $rows)
	 */
	function findAll($pidList){
		$foundAttendees = Array();
		$select = 'tx_cal_attendee.*';
		$table = 'tx_cal_attendee, tx_cal_event_attendee_mm';
		$where = 'tx_cal_attendee.deleted=0 AND tx_cal_attendee.hidden=0';
		if($pidList){
			$where .= ' AND tx_cal_attendee.pid IN ('.$pidList.')';
		}
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select,$table,$where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$foundAttendees[$row['uid']] = $this->createAttendee($row);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		return $foundAttendees;
	}
	
	function updateAttendee($uid){

		$insertFields = array('tstamp' => time());
		//TODO: Check if all values are correct
		$this->searchForAdditionalFieldsToAddFromPostData($insertFields,'attendee',false);
		$this->retrievePostData($insertFields);
		
		$this->_updateAttendee($uid, $insertFields);
	}
	
	function _updateAttendee($uid, &$insertFields){

		// Updating DB records
		$table = 'tx_cal_attendee';
		$where = 'uid = '.$uid;	

		$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,$where,$insertFields);
		
		$this->unsetPiVars();
	}
	
	function removeAttendee($uid){
		if($this->rightsObj->isAllowedToDeleteCategory()){
			// 'delete' the attendee object
			$updateFields = array('tstamp' => time(), 'deleted' => 1);
			$table = 'tx_cal_attendee';
			$where = 'uid = '.$uid;	
			$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,$where,$updateFields);
			
			$this->unsetPiVars();
		}
	}
	
	function retrievePostData(&$insertFields){
		$hidden = 0;
		if($this->controller->piVars['hidden']=='true' && 
				($this->rightsObj->isAllowedTo('edit','attendee','hidden') || $this->rightsObj->isAllowedTo('create','attendee','hidden'))) {
			$hidden = 1;
		}
		$insertFields['hidden'] = $hidden;
		
		if($this->rightsObj->isAllowedTo('edit','attendee','fe_user_id') || $this->rightsObj->isAllowedTo('create','attendee','fe_user_id')){
			$insertFields['fe_user_id'] = strip_tags($this->controller->piVars['fe_user_id']);
		}
		
		if($this->rightsObj->isAllowedTo('edit','attendee','email') || $this->rightsObj->isAllowedTo('create','attendee','email')){
			$insertFields['email'] = intval($this->controller->piVars['email']);
		}
		
		if($this->rightsObj->isAllowedTo('edit','attendee','attendance') || $this->rightsObj->isAllowedTo('create','attendee','attendance')){
			$insertFields['attendance'] = intval($this->controller->piVars['attendance']);
		}
		
		if($this->rightsObj->isAllowedTo('edit','attendee','status') || $this->rightsObj->isAllowedTo('create','attendee','status')){
			$insertFields['status'] = strip_tags($this->controller->piVars['status']);
		}
	}
	
	function saveAttendee($pid){
		$crdate = time();
		$insertFields = array('pid' => $this->conf['rights.']['create.']['attendee.']['saveAttendeeToPid']?$this->conf['rights.']['create.']['attendee.']['saveAttendeeToPid']:$pid, 'tstamp' => $crdate, 'crdate' => $crdate);
		$this->searchForAdditionalFieldsToAddFromPostData($insertFields,'attendee');
		$this->retrievePostData($insertFields);

		// Creating DB records
		$insertFields['cruser_id'] = $this->rightsObj->getUserId();
		$this->_saveAttendee($insertFields);
		$this->unsetPiVars();
	}
	
	function _saveAttendee(&$insertFields){
		$table = 'tx_cal_attendee';
		$result = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$insertFields);
	}
	
	function getAttendeeEventSearchString($eventUid){
		return ' AND tx_cal_event_attendee_mm.uid_local = '.$eventUid.' AND tx_cal_event_attendee_mm.uid_foreign = tx_cal_attendee.uid';
	}
	
	
	function createAttendee($row){
		$attendee = &tx_cal_functions::makeInstance('tx_cal_attendee_model',$row, $this->getServiceKey());	
		return $attendee;	
	}
	
	function findEventAttendees($eventUid){
		$foundAttendees = Array();
		//selecting attendees NOT attached to a fe_user
		$select = 'tx_cal_attendee.*, tx_cal_attendee.email AS the_email';
		$table = 'tx_cal_attendee, tx_cal_event_attendee_mm';
		$where = 'tx_cal_attendee.deleted=0 AND tx_cal_attendee.fe_user_id = 0 AND tx_cal_attendee.hidden=0'.$this->getAttendeeEventSearchString($eventUid);
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select,$table,$where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$row['email'] = $row['the_email'];
			$foundAttendees[$row['uid']] = $this->createAttendee($row);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);

		//selecting attendees attached to a fe_user
		$select = 'tx_cal_attendee.*, fe_users.email AS the_email, fe_users.name AS name';
		$table = 'fe_users, tx_cal_attendee, tx_cal_event_attendee_mm';
		$where = 'fe_users.uid = tx_cal_attendee.fe_user_id'.$this->getAttendeeEventSearchString($eventUid).' AND tx_cal_attendee.deleted=0 AND tx_cal_attendee.hidden=0 AND fe_users.disable=0 AND fe_users.deleted=0';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select,$table,$where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$row['email'] = $row['the_email'];
			$foundAttendees[$row['uid']] = $this->createAttendee($row);
		}
		$GLOBALS['TYPO3_DB']->sql_Free_result($result);

		return $foundAttendees;
	}
	
	function unsetPiVars(){
		unset($this->controller->piVars['hidden']);
		unset($this->controller->piVars['uid']);
		unset($this->controller->piVars['type']);
		unset($this->controller->piVars['email']);
		unset($this->controller->piVars['fe_user_id']);
		unset($this->controller->piVars['attendance']);
		unset($this->controller->piVars['status']);
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/service/class.tx_cal_attendee_service.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/service/class.tx_cal_attendee_service.php']);
}
?>
