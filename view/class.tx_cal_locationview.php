<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2005-2007 Mario Matzulla
 * (c) 2005-2007 Foundation for Evangelism
 * All rights reserved
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

require_once (t3lib_extMgm :: extPath('cal').'view/class.tx_cal_base_view.php');

/**
 * A concrete view for the calendar.
 * It is based on the phpicalendar project
 *
 * @author Mario Matzulla <mario(at)matzullas.de>
 */
class tx_cal_locationview extends tx_cal_base_view {

	function tx_cal_locationview(){
		$this->tx_cal_base_view();
	}
	
	/**
	 *  Draws a location.
	 *  @param		object		The location to be drawn.
	 *	@return		string		The HTML output.
	 */
	function drawLocation($location) {
		$array = array();
		$this->_init($array);
		$lastview = $this->controller->extendLastView();
		$uid = $this->conf['uid'];
		$type = $this->conf['type'];
		$page = $this->cObj->fileResource($this->conf['view.']['event.']['eventTemplate']);
		if ($page == '') {
			return '<h3>calendar: no template file found:</h3>'.$this->conf['view.']['event.']['eventTemplate'];
		}
		if(is_object($location)){
			$rems['###EVENT###'] = $location->renderLocation();
			if($this->conf['view.']['event.']['substitutePageTitle']==1){
				$GLOBALS['TSFE']->page['title'] = $location->getName();
			}
		}else{
			$rems['###EVENT###'] = $this->cObj->cObjGetSingle($this->conf['view.']['location.']['noLocationFound'],$this->conf['view.']['location.']['noLocationFound.']);
		}
		
		return $this->finish($page, $rems);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/view/class.tx_cal_locationview.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/view/class.tx_cal_locationview.php']);
}
?>