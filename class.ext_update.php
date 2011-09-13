<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2005-2007 Mario Matzulla
 * (c) 2005-2007 Foundation for Evangelism
 * All rights reserved
 *
 * Adapted from original tt_news code by Ruper Germann 
 * (c) 2004-2005 Rupert Germann <rupi@gmx.li>
 *
 * This file is part of the Web-Empowered Church (WEC)
 * (http://webempoweredchurch.org) ministry of the Foundation for Evangelism
 * (http://evangelize.org). The WEC is developing TYPO3-based
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

/**
 * Class for updating the storage format of calendar events.
 *
 * @author  Mario Matzulla <mario(at)matzullas.de>
 * @package TYPO3
 * @subpackage cal
 */
class ext_update {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main() {
		$count = 0;
		
		/* Get all the events with old timestamps */
		$res_events = $GLOBALS['TYPO3_DB']->exec_SELECTquery ('*', 'tx_cal_event', 'start_date > 20400101 OR end_date > 20400101');
		$res_exceptions = $GLOBALS['TYPO3_DB']->exec_SELECTquery ('*', 'tx_cal_exception_event', 'start_date > 20400101 OR end_date > 20400101');
		
		/* Get the total number of events that need to be updated */
		if ($res_events && $GLOBALS['TYPO3_DB']->sql_num_rows($res_events)) {
			$count += $GLOBALS['TYPO3_DB']->sql_num_rows($res_events);
		}
		if ($res_exceptions && $GLOBALS['TYPO3_DB']->sql_num_rows($res_exceptions)) {
			$count += $GLOBALS['TYPO3_DB']->sql_num_rows($res_exceptions);
		}
		
		/* If the update button hasn't been clicked */
		if (!t3lib_div::_GP('do_update')) {
			$onClick = "document.location='".t3lib_div::linkThisScript(array('do_update' => 1))."'; return false;";
			
			/* If we have events, show the count and a button to update */
			if ($count) {
				if($count == 1) {
					$returnthis = '<p style="padding-bottom: 5px;">There is '.$count.' Calendar Base Event which should be updated with the new date storage format.</p>';
				} else {
					$returnthis = '<p style="padding-bottom: 5px;">There are '.$count.' Calendar Base Events which should be updated.</p>';
				}
				
				$returnthis .= '<form action=""><input type="submit" value="Update Events" onclick="'.htmlspecialchars($onClick).'"></form>';
			} else {
				$returnthis = '<p style="padding-bottom: 5px;">There are no Calendar Base Events that require an update.</p>';
			}
			
			return $returnthis;
		}else{
			/* If there are events, do the update */
			if ($count) {
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_events)) {
					/* Upgrade event start date */
					$values = array('start_date' => date('Ymd',$row['start_date']));
					
					/* Upgrade event end date */
					if($row['end_date']>0){
						$values['end_date'] = date('Ymd',$row['end_date']);
					}else{
						$values['end_date'] = date('Ymd',$row['start_date']);
					}
					
					/* Upgrade recurring event until date */
					if($row['until']>0){
						$values['until'] = date('Ymd',$row['until']);
					}
					
					/* Do the update */
					$eventUpdateRes = $GLOBALS['TYPO3_DB']->exec_UPDATEquery ('tx_cal_event', 'uid='.$row['uid'],$values);
				}
				
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_exceptions)) {
					/* Upgrade exception start date */
					$values = array('start_date' => date('Ymd',$row['start_date']));
					
					/* Upgrade exception end date */
					if($row['end_date']>0){
						$values['end_date'] = date('Ymd',$row['end_date']);
					}else{
						$values['end_date'] = date('Ymd',$row['start_date']);
					}
					
					/* Upgrade recurring exception until date */
					if($row['until']>0){
						$values['until'] = date('Ymd',$row['until']);
					}
					
					/* Do the update */
					$exceptionUpdateRes = $GLOBALS['TYPO3_DB']->exec_UPDATEquery ('tx_cal_exception_event', 'uid='.$row['uid'],$values);
				}
				
				if($count == 1) {
					$returndoupdate = $count.' Calendar Base Event was updated.<br><br>';
				} else {
					$returndoupdate = $count.' Calendar Base Events were updated.<br><br>';
				}				
			}
			return $returndoupdate;
		}
		
		return "Done. Please check you records";
	}

	/**
	 * Checks how many rows are found and returns true if there are any
	 * (this function is called from the extension manager)
	 *
	 * @param	string		$what: what should be updated
	 * @return	boolean
	 */
	function access($what = 'all') {
		if ($what = 'all') {
			$count = 0;
			
			if(is_object($GLOBALS['TYPO3_DB'])) {
				if (in_array('tx_cal_event', $GLOBALS['TYPO3_DB']->admin_get_tables ())) {
					$res_events = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_cal_event', 'start_date > 20400101 OR end_date > 20400101');
					if ($res_events) {
						$count += $GLOBALS['TYPO3_DB']->sql_num_rows($res_events);
					}else{
						$count = 0;
					}
					
					$res_exceptions = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_cal_exception_event', 'start_date > 20400101 OR end_date > 20400101');
					if ($res_exceptions) {
						$count += $GLOBALS['TYPO3_DB']->sql_num_rows($res_exceptions);
					}else{
						$count = 0;
					}
					
				}
			}
			
			if($count > 0) {
				return true;
			} else {
				return false;
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/class.ext_update.php']);
}
?>