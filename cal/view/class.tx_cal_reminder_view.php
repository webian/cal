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


require_once(t3lib_extMgm::extPath('cal').'view/class.tx_cal_notification_view.php');


/**
 * 
 *
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 * @package TYPO3
 * @subpackage cal
 */
class tx_cal_reminder_view extends tx_cal_notification_view {
	
	function tx_cal_reminder_view(){
		$this->tx_cal_notification_view();
	}
	
	function remind(&$event){
		$this->startMailer();
		
		$tablearray=array('tx_cal_unknown_users','fe_users'); #FBO	
		#$select = 'fe_users.*';
		foreach($tablearray as $usertable){
			$select = $usertable.'.*';
			$table = $usertable.', tx_cal_fe_user_event_monitor_mm, tx_cal_event';
			$where = $usertable.'.uid = tx_cal_fe_user_event_monitor_mm.uid_foreign AND  tx_cal_fe_user_event_monitor_mm.tablenames like "'.$usertable.'" AND tx_cal_fe_user_event_monitor_mm.uid_local = tx_cal_event.uid AND tx_cal_event.deleted = 0 AND tx_cal_event.uid = '.$event->getUid();
	
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select,$table,$where);
			while ($user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if($user['email']!='' && t3lib_div::validEmail($user['email'])){
					$template = $this->conf[$user['uid'].'.']['template'];
					if(!$template){
						$template = $this->conf['view.']['event.']['remind.']['all.']['template'];
					}
					$titleText = $this->conf['view.']['event.']['remind.'][$user.'.']['emailTitle'];
					if(!$titleText){
						$titleText = $this->conf['view.']['event.']['remind.']['all.']['emailTitle'];
					}
					$this->sendNotification($event, $user['email'], $template, $titleText, '');
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
		}
	}
	
	
	/* @todo	Figure out where this should live */
	function scheduleReminder($calEventUID, $reminderTimestamp) {
		if (t3lib_extMgm::isLoaded('gabriel')) {
			$eventUID = 'tx_cal_event:'.$calEventUID;

			/* Check for existing gabriel events and remove them */
			$this->deleteReminder($calEventUID);

			/* Set up the gabriel event */
			$cron = t3lib_div::getUserObj('EXT:cal/cron/class.tx_cal_reminder_cron.php:tx_cal_reminder_cron');
			$cron->setUID($calEventUID);

			/* Schedule the gabriel event */ 
			$cron->registerSingleExecution($reminderTimestamp);
			$gabriel = t3lib_div::getUserObj('EXT:gabriel/class.tx_gabriel.php:&tx_gabriel');
			$gabriel->addEvent($cron,$eventUID);
		}
	}

	/* @todo	Figure out where this should live */
	function deleteReminder($calEventUID) {
		if (t3lib_extMgm::isLoaded('gabriel')) {
			$eventUID = 'tx_cal_event:'.$calEventUID;
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_gabriel',' crid="'.$eventUID.'"');
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/view/class.tx_cal_reminder_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/view/class.tx_cal_reminder_view.php']);
}
?>