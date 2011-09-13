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

require_once (t3lib_extMgm :: extPath('cal').'view/class.tx_cal_fe_editing_base_view.php');
require_once(t3lib_extMgm::extPath('cal').'controller/class.tx_cal_calendar.php');
    

/**
 * A service which renders a form to create / edit a phpicalendar event.
 *
 * @author Mario Matzulla <mario(at)matzullas.de>
 */
class tx_cal_create_event_view extends tx_cal_fe_editing_base_view {
	
	/* RTE vars */
	var $RTEObj;
    var $strEntryField;
    var $docLarge = 0;
    var $RTEcounter = 0;
    var $formName;
    var $additionalJS_initial = '';		// Initial JavaScript to be printed before the form (should be in head, but cannot due to IE6 timing bug)
	var $additionalJS_pre = array();	// Additional JavaScript to be printed before the form
	var $additionalJS_post = array();	// Additional JavaScript to be printed after the form
	var $additionalJS_submit = array();	// Additional JavaScript to be executed on submit
    var $PA = array(
            'itemFormElName' =>  '',
            'itemFormElValue' => '',
            );
    var $specConf = array();
    var $thisConfig = array();
    var $RTEtypeVal = 'text';
    var $thePidValue;
    
    var $validation = '';
    var $useDateSelector = false;
    var $dateSelectorConf = '';
    
    var $dateFormatArray = array();
    
    var $cal_notifyUserIds = array();
    var $cal_notifyGroupIds = array();
	
	var $eventType = 'tx_cal_phpicalendar';
	
	var $confArr = array();

	function tx_cal_create_event_view(){
		$this->tx_cal_fe_editing_base_view();
	}
	
	/**
	 *  Draws a create event form.
	 *  @param      $getdate	int         A date to create the event for. Format: yyyymmdd
	 *  @param		$pidList	string		Comma separated list of pids.
	 *  @param      $object		object      A phpicalendar object to be updated
	 *	@return		string		The HTML output.
	 */
	function drawCreateEvent($getdate, $pidList, $object=''){	

		$this->objectString = 'event';	
		if(is_object($object)){
			$this->conf['view'] = 'edit_'.$this->objectString;
		}else{
			$this->conf['view'] = 'create_'.$this->objectString;
			unset($this->controller->piVars['uid']);
		}
		$lastPiVars = $this->controller->piVars;

		$requiredFieldSims = Array();
		$allRequiredFieldsAreFilled = $this->checkRequiredFields($requiredFieldsSims);
		
		$sims = array();
		$rems = array();
		$wrapped = array();
		
		// If an event has been passed on the form is a edit form
		if(is_object($object) && $object->isUserAllowedToEdit($this->rightsObj->getUserId())){
			$this->isEditMode = true;
			$this->object = $object;
			$this->prepareUserArray();
			$sims['###UID###'] = $this->object->getUid();
			$sims['###TYPE###'] = $this->object->getType();
			$sims['###L_EDIT_EVENT###'] = $this->controller->pi_getLL('l_edit_event');
			$this->object->updateWithPIVars($this->controller->piVars);
		}else{
			$sims['###UID###'] = '';
			$sims['###TYPE###'] = $this->eventType;
			$sims['###L_EDIT_EVENT###'] = $this->controller->pi_getLL('l_create_event');
			$this->object = $this->modelObj->createEvent('tx_cal_phpicalendar');
			$this->controller->piVars['mygetdate'] = $this->conf['getdate'];
			$allValues = array_merge($this->getDefaultValues(),$this->controller->piVars);
			$this->object->updateWithPIVars($allValues);
		}
		
		$constrainFieldSims = Array();
		$noComplains = $this->checkContrains($constrainFieldSims);
		
		if($allRequiredFieldsAreFilled && $noComplains){
			
			$this->conf['lastview'] = $this->controller->extendLastView();
			$this->conf['view'] = 'confirm_'.$this->objectString;
			$this->controller->piVars = $lastPiVars;
			if($this->conf['view.']['dontShowConfirmView']==1){
				return $this->controller->saveEvent();
			}
			return $this->controller->confirmEvent();
		}

		$this->initTemplate();
		$sims['###VIEW###'] = $this->conf['view'];
		
		//Needed for translation options:
		$this->serviceName = 'cal_event_model';
		$this->table = 'tx_cal_event';

		$page = '';
		if($this->conf['view.']['enableAjax'] && $this->controller->piVars['pid']){
			$page = $this->cObj->fileResource($this->conf['view.']['create_event.']['ajaxTemplate']);
		}else{
			$page = $this->cObj->fileResource($this->conf['view.']['create_event.']['template']);
		}
		
		if ($page=='') {
			return '<h3>calendar: no create event template file found:</h3>'.$this->conf['view.']['create_event.']['template'];
		}
		
		if(is_object($object) && !$object->isUserAllowedToEdit()){
			return $this->controller->pi_getLL('l_not_allowed_edit').$this->objectString;
		}else if(!is_object($object) && !$this->rightsObj->isAllowedTo('create',$this->objectString,'')){
			return $this->controller->pi_getLL('l_not_allowed_create').$this->objectString;
		}
		
		$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cal']);

		$this->validation = '';
		
		$this->dateFormatArray = array();
		$this->dateFormatArray[$this->conf['dateConfig.']['dayPosition']] = 'dd';
		$this->dateFormatArray[$this->conf['dateConfig.']['monthPosition']] = 'mm';
		$this->dateFormatArray[$this->conf['dateConfig.']['yearPosition']] = 'yyyy';

				
		
		$this->getTemplateSubpartMarker($page, $sims, $rems, $wrapped, $this->conf['view']);
		$page = $this->cObj->substituteMarkerArrayCached($page, array(), $rems, $wrapped);
		$page = $this->cObj->substituteMarkerArrayCached($page, $sims, array(), array ());
                
		$sims = array();
		$rems = array();
	
		$this->getTemplateSingleMarker($page, $sims, $rems, $this->conf['view']);
		$this->addAdditionalMarker($page, $sims, $rems);

		$page = $this->cObj->substituteMarkerArrayCached($page, array(), $rems, array ());
		$page = $this->cObj->substituteMarkerArrayCached($page, $sims, array(), array ());
	
		$sims = array_merge($requiredFieldsSims,$constrainFieldSims);
		$sims['###STARTDATE_SELECTOR###'] = '';
		$sims['###ENDDATE_SELECTOR###'] = ''; 
		$sims['###UNTIL_SELECTOR###'] = '';
		if($this->useDateSelector){
			$sims['###STARTDATE_SELECTOR###'] = $this->useDateSelector ? tx_rlmpdateselectlib::getInputButton ('startdate',$this->dateSelectorConf) : '';
			$sims['###ENDDATE_SELECTOR###'] = $this->useDateSelector ? tx_rlmpdateselectlib::getInputButton ('enddate',$this->dateSelectorConf) : '';
			$sims['###UNTIL_SELECTOR###'] = $this->useDateSelector ? tx_rlmpdateselectlib::getInputButton ('until_value',$this->dateSelectorConf) : '';
		}
		return $this->cObj->substituteMarkerArrayCached($page, $sims, array(), array ());
	}
	
	function initTemplate(){
		if (t3lib_extMgm::isLoaded('rlmp_dateselectlib')){
				require_once(t3lib_extMgm::extPath('rlmp_dateselectlib').'class.tx_rlmpdateselectlib.php');
				tx_rlmpdateselectlib::includeLib();
				
				/* Only read date selector option if rlmp_dateselectlib is installed */
				$this->useDateSelector = $this->conf['view.']['event.']['useDateSelector'];
		}
		
		$dateFormatArray = array();
		$dateFormatArray[$this->conf['dateConfig.']['dayPosition']] = '%d';
		$dateFormatArray[$this->conf['dateConfig.']['monthPosition']] = '%m';
		$dateFormatArray[$this->conf['dateConfig.']['yearPosition']] = '%Y';
		$dateFormatString = $dateFormatArray[0].$this->conf['dateConfig.']['splitSymbol'].$dateFormatArray[1].$this->conf['dateConfig.']['splitSymbol'].$dateFormatArray[2];

		$this->dateSelectorConf = array('calConf.' => array (
                           'dateTimeFormat' => $dateFormatString,
                           'inputFieldDateTimeFormat' => $dateFormatString,
                           'toolTipDateTimeFormat' => $dateFormatString,
                           //'showMethod' => 'absolute',
                           //'showPositionAbsolute' => '100,150',
                           //'stylesheet' => 'fileadmin/mystyle.css'
              )
    	);
	}
	
	function getEventCalendarMarker(& $template, & $sims, & $rems){
		$sims['###EVENT_CALENDAR###'] = $this->object->getCalendarUid();
	}
	
	function getEventCategoryMarker(& $template, & $sims, & $rems){
		$sims['###EVENT_CATEGORY###'] = 'new Array(';
		if($this->isAllowed('category')) {
			$cats = Array();
			$categories = $this->object->getCategories();
			if(is_array($categories)) {
				foreach($categories as $category){
					$cats[] = '{"uid":'.$category->getUid().'}';
				}
			}
			$sims['###EVENT_CATEGORY###'] .= implode(',',$cats).')';
		}else{
			$sims['###EVENT_CATEGORY###'] .= ')';
		}
	}
	
	function getCategoryArrayMarker(& $template, & $sims, & $rems){
		$sims['###CATEGORY_ARRAY###'] = 'new Array(';
		if($this->isAllowed('category')) {
			$tempCalendarConf = $this->conf['calendar'];
			$tempCategoryConf = $this->conf['category'];
			$this->conf['calendar'] = $this->conf['rights.']['create.']['event.']['fields.']['calendar.']['default'];
			if($this->rightsObj->isAllowedToCreateEventCalendar()){
				$this->conf['calendar'] = $this->conf['switch_calendar'];
			}
			$this->conf['calendar'] .= ',0';
			$this->conf['category'] = '0';
	
			if($this->conf['calendar']){
				$this->conf['view.']['create_event.']['tree.']['calendar'] = $this->conf['calendar'];
				$this->conf['view.']['create_event.']['tree.']['category'] = $this->conf['category'];
	
				$globalCategoryArrays = $this->modelObj->findAllCategories('','tx_cal_category',$this->conf['pidList']);
				$serviceKeyArray = array();
				foreach($globalCategoryArrays as $serviceKey => $serviceCategoryArrays){
					$elements = array();
					foreach($serviceCategoryArrays[0][0] as $category){
						$elements[] = '{"uid":'.$category->getUid().',"parentuid":'.intval($category->getParentUid()).',"calendaruid":'.intval($category->getCalendarUid()).',"title":"'.$category->getTitle().'","headerstyle":"'.$category->getHeaderStyle().'","bodystyle":"'.$category->getBodyStyle().'"}';
					}
					$serviceKeyArray[] = '{"'.$serviceKey.'": new Array('.implode(',',$elements).')}';
				}
			}
			$this->conf['calendar'] = $tempCalendarConf;
			if(!$this->conf['category']=='0'){
				$this->conf['category'] = $tempCategoryConf;
			}
			$sims['###CATEGORY_ARRAY###'] .= implode(',',$serviceKeyArray);
		}
		$sims['###CATEGORY_ARRAY###'] .= ')';
	}
	
	function getCalendarArrayMarker(& $template, & $sims, & $rems){
		$calendarArray = $this->modelObj->findAllCalendar('tx_cal_calendar',$this->conf['pidList']);
		$sims['###CALENDAR_ARRAY###'] .= 'new Array(';
		$elements = array();
		foreach($calendarArray['tx_cal_calendar'] as $calendar){
			$elements[] = '{"uid":'.$calendar->getUid().',"title":"'.$calendar->getTitle().'"}';
		}
		$sims['###CALENDAR_ARRAY###'] .= implode(',',$elements).')';
	}
	
	function getCategoryMarker(& $template, & $sims, & $rems){
		$sims['###CATEGORY###'] = '';
		if($this->isAllowed('category')) {
			
			$calendarUID = $this->object->getCalendarUid();
			$categories = $this->object->getCategories();
			$selectedCalendars = $this->object->getCalendarUid().',0';
			if(!$calendarUID && count($categories) == 0){
				$selectedCategories = '0';
			} else {
				$ids = array(0);
				foreach((Array)$categories as $category) {
					if(is_object($category)) {
						$ids[] = $category->getUid();
					}
				}
				$selectedCategories = implode(',',$ids);
 			}
			/* What does this do? */
			$this->conf['view.']['edit_event.']['tree.']['calendar'] = $selectedCalendars;
			$this->conf['view.']['edit_event.']['tree.']['category'] = $selectedCategories;

			$categoryArray = $this->modelObj->findAllCategories('','tx_cal_category',$this->conf['pidList']);

			$tree = $this->getCategorySelectionTree($this->conf['view.']['edit_event.']['tree.'], $categoryArray, true);
			$sims['###CATEGORY###'] = $this->applyStdWrap($tree, 'category_stdWrap');
		}	
	}
	
	
	function getAlldayMarker(& $template, & $sims, & $rems){
		$sims['###ALLDAY###'] = '';
		if($this->isAllowed('allday')){
			if($this->object->isAllday()) {
				$allDayValue = 'checked="checked"';
			} else {
				$allDayValue = '';
			}
			$sims['###ALLDAY###'] = $this->applyStdWrap($allDayValue, 'allday_stdWrap');
		}
	}
	
	function getStartdateMarker(& $template, & $sims, & $rems){
		$sims['###STARTDATE###'] = '';
		if($this->isAllowed('startdate')){
			$eventStart = $this->object->getStart();
			$startDateValue = $eventStart->format(getFormatStringFromConf($this->conf));

			$sims['###STARTDATE###'] = $this->applyStdWrap($startDateValue, 'startdate_stdWrap');
		}
	}
	
	function getEnddateMarker(& $template, & $sims, & $rems) {
		$sims['###ENDDATE###'] = '';
		if($this->isAllowed('enddate')) {
			if($this->object->getEnd() == 0) {
				$eventEnd = $this->object->getStart();
			} else {
				$eventEnd = $this->object->getEnd();
			}
			
			$endDateValue = $eventEnd->format(getFormatStringFromConf($this->conf));
			$sims['###ENDDATE###'] = $this->applyStdWrap($endDateValue, 'enddate_stdWrap');
		}
	}
	
	function getTimeSelector($start, $finish, $default, $stepping=1) {
		$selector = '';
		for ($i=$start;$i<$finish;$i+=$stepping) {
			$value = str_pad($i, 2, '0', STR_PAD_LEFT);
			$selector .= '<option value="'.$value.'"'.($default == $value ? ' selected="selected"' : '').'>'.$value.'</option>';
		}
		
		return $selector;
	}
	
	function getStarttimeMarker(& $template, & $sims, & $rems) {
		$sims['###STARTTIME###'] = '';
		if($this->isAllowed('starttime')) {
			$eventStart = $this->object->getStart();
			$start_time_minute = $eventStart->getMinute();
			$start_time_hour = $eventStart->getHour();
			
			$start_hours = $this->getTimeSelector(0, 24, $start_time_hour);
			$start_minutes = $this->getTimeSelector(0, 60, $start_time_minute,$this->conf['view.'][$this->conf['view'].'.']['startminutes.']['stepping']);
			
			$sims['###STARTTIME###'] = $this->applyStdWrap($start_hours, 'starttime_stdWrap').$this->applyStdWrap($start_minutes, 'startminutes_stdWrap');
		}
	}
	
	function getEndtimeMarker(& $template, & $sims, & $rems){
		$sims['###ENDTIME###'] = '';
		if($this->isAllowed('endtime')){
			$eventEnd = $this->object->getEnd();
			$end_time_minute = $eventEnd->getMinute();
			$end_time_hour = $eventEnd->getHour();
			$end_hours = $this->getTimeSelector(0, 24, $end_time_hour);
			$end_minutes = $this->getTimeSelector(0, 60, $end_time_minute, $this->conf['view.'][$this->conf['view'].'.']['endminutes.']['stepping']);

			$sims['###ENDTIME###'] = $this->applyStdWrap($end_hours, 'endtime_stdWrap').$this->applyStdWrap($end_minutes, 'endminutes_stdWrap');
		}
	}
	
	function prepareUserArray(){
		if($this->isEditMode){
			// selection uids of available notify/monitor users & -groups
			$cal_notify_user = '';
			$this->cal_notifyUserIds = array();
			$where = ' AND tx_cal_event.uid='.$this->object->getUid().' AND fe_users.deleted = 0 AND fe_users.disable = 0'.$this->cObj->enableFields('tx_cal_event');
			//TODO add this when groups are allowed: AND tx_cal_fe_user_event_monitor_mm.tablenames="fe_users" 
			$orderBy = '';
			$groupBy = '';
			$limit = '';
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('fe_users.*','tx_cal_event','tx_cal_fe_user_event_monitor_mm','fe_users',$where,$groupBy ,$orderBy,$limit);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$this->cal_notifyUserIds[] = $row['uid'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);

			$this->cal_notifyGroupIds = array();
			$where = ' AND tx_cal_event.uid='.$this->object->getUid().' AND tx_cal_fe_user_event_monitor_mm.tablenames="fe_groups" '.$this->cObj->enableFields('tx_cal_event');
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('fe_groups.*','tx_cal_event','tx_cal_fe_user_event_monitor_mm','fe_groups',$where,$groupBy ,$orderBy,$limit);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				array_push($this->cal_notifyGroupIds,$row['uid']);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
		}
	}
	
	function getOrganizerMarker(& $template, & $sims, & $rems) {
		$sims['###ORGANIZER###'] = '';
		if(!$this->confArr['hideOrganizerTextfield'] && $this->isAllowed('organizer')) {
			$sims['###ORGANIZER###'] = $this->applyStdWrap($this->object->getOrganizer(), 'organizer_stdWrap');
		}
	}
	
	function getCalOrganizerMarker(& $template, & $sims, & $rems){
		$sims['###CAL_ORGANIZER###'] = '';
		if($this->isAllowed('cal_organizer')){
			$uidList = array(explode(',',$this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['organizer.']['allowedUids']));
			$default = $this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['organizer.']['default'];
			// creating options for organizer
			if($this->object->getOrganizerId()){
				$default = $this->object->getOrganizerId();
			}
			$cal_organizer = '<option value="">'.$this->controller->pi_getLL('l_select').'</option>';
			$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cal']);
			$useOrganizerStructure = ($this->confArr['useOrganizerStructure']?$this->confArr['useOrganizerStructure']:'tx_cal_organizer');		
			$organizers = $this->modelObj->findAllOrganizer($useOrganizerStructure,$this->conf['pidList']);
			if($this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['organizer.']['allowedUids']) {
				if(!$this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['organizer.']['default']) {
					$cal_organizer = '<option value="">'.$this->controller->pi_getLL('l_select').'</option>';
				}
				foreach($organizers as $organizer){
					if(in_array($organizer->getUid(),$uidList)){
						$cal_organizer .= '<option value="'.$organizer->getUid().'"';
						if($organizer->getUid() == $default) {
							$cal_organizer .= ' selected="selected"';
						}
						$cal_organizer .= '>'.$organizer->getName().'</option>';
					}
				}
			}
			// if no default values found
			else {
				// creating options for location by standard fe plugin entry point
				foreach((Array)$organizers as $organizer){
					$cal_organizer .= '<option value="'.$organizer->getUid().'"';
					if($organizer->getUid() == $default) {
						$cal_organizer .= ' selected="selected"';
					}
					$cal_organizer .= '>'.$organizer->getName().'</option>';
				}
			}
			$sims['###CAL_ORGANIZER###'] = $this->applyStdWrap($cal_organizer, 'cal_organizer_stdWrap');
		}
	}
	
	function getLocationMarker(& $template, & $sims, & $rems){
		$sims['###LOCATION###'] = '';
		if(!$this->confArr['hideLocationTextfield'] && $this->isAllowed('location')){
			$sims['###LOCATION###'] = $this->applyStdWrap($this->object->getLocation(), 'location_stdWrap');
		}
	}
	
	function getCalLocationMarker(& $template, & $sims, & $rems){
		$sims['###CAL_LOCATION###'] = '';
		if($this->isAllowed('cal_location')){		
			$uidList = array(explode(',',$this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['location.']['allowedUids']));
			$default = $this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['location.']['default'];
			if($this->object->getLocationId()){
				$default = $this->object->getLocationId();
			}
			// creating options for location
			$cal_location = '<option value="">'.$this->controller->pi_getLL('l_select').'</option>';
			$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cal']);
			$useLocationStructure = ($this->confArr['useLocationStructure']?$this->confArr['useLocationStructure']:'tx_cal_location');		
			$locations = $this->modelObj->findAllLocations($useLocationStructure,$this->conf['pidList']);
			if($this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['location.']['allowedUids']) {
				if(!$this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['fields.']['location.']['default']) {
					$cal_location = '<option value="">'.$this->controller->pi_getLL('l_select').'</option>';
				}
				foreach($locations as $location){
					if(in_array($location->getUid(),$uidList)){
						$cal_location .= '<option value="'.$location->getUid().'"';
						if($location->getUid() == $default) {
							$cal_location .= ' selected="selected"';
						}
						$cal_location .= '>'.$location->getName().'</option>';
					}
				}
			}
			// if no default values found
			else {
				// creating options for location by standard fe plugin entry point
				foreach($locations as $location){
					$cal_location .= '<option value="'.$location->getUid().'"';
					if($location->getUid() == $default) {
						$cal_location .= ' selected="selected"';
					}
					$cal_location .= '>'.$location->getName().'</option>';
				}
			}
			$sims['###CAL_LOCATION###'] = $this->applyStdWrap($cal_location, 'cal_location_stdWrap');
		}
	}
	
	function getDescriptionMarker(& $template, & $sims, & $rems){
		$sims['###ADDITIONALJS_PRE###'] = '';
		$sims['###ADDITIONALJS_POST###'] = '';
		$sims['###ADDITIONALJS_SUBMIT###'] = '';
		$sims['###DESCRIPTION###'] = '';
		if($this->isAllowed('description')){
			$sims['###DESCRIPTION###'] = $this->cObj->stdWrap('<textarea name="tx_cal_controller[description]" id="cal_event_description">'.$this->object->getDescription().'</textarea>', $this->conf['view.'][$this->conf['view'].'.']['description_stdWrap.']);
			
			/* Start setting the RTE markers */
			if (t3lib_extMgm::isLoaded('rtehtmlarea'))   require_once(t3lib_extMgm::extPath('rtehtmlarea').'pi2/class.tx_rtehtmlarea_pi2.php'); //RTE 
			if(!$this->RTEObj && t3lib_extMgm::isLoaded('rtehtmlarea'))  $this->RTEObj = t3lib_div::makeInstance('tx_rtehtmlarea_pi2');
			if(is_object($this->RTEObj) && $this->RTEObj->isAvailable() && $this->conf['rights.'][$this->isEditMode?'edit.':'create.']['event.']['enableRTE']) {
				$this->RTEcounter++;
				$this->formName = 'tx_cal_controller';
				$this->strEntryField = 'description';
				$this->PA['itemFormElName'] = 'tx_cal_controller[description]';
				$this->PA['itemFormElValue'] = $this->object->getDescription();
				$this->thePidValue = $GLOBALS['TSFE']->id;
                if($this->conf['view.'][$this->conf['view'].'.']['rte.']['width']>0 && $this->conf['view.'][$this->conf['view'].'.']['rte.']['height']>0)
                    $this->RTEObj->RTEdivStyle = 'height:'.$this->conf['view.'][$this->conf['view'].'.']['rte.']['height'].'px; width:'.$this->conf['view.'][$this->conf['view'].'.']['rte.']['width'].'px;';     
                    
				$RTEItem = $this->RTEObj->drawRTE($this,'tx_cal_event',$this->strEntryField,$row=array(), $this->PA, $this->specConf, $this->thisConfig, $this->RTEtypeVal, '', $this->thePidValue);
				$sims['###ADDITIONALJS_PRE###'] = $this->additionalJS_initial.'
					<script type="text/javascript">'. implode(chr(10), $this->additionalJS_pre).'
					</script>';
				$sims['###ADDITIONALJS_POST###'] = '
					<script type="text/javascript">'. implode(chr(10), $this->additionalJS_post).'
					</script>';
				$sims['###ADDITIONALJS_SUBMIT###'] = implode(';', $this->additionalJS_submit);
				$sims['###DESCRIPTION###'] = $this->applyStdWrap($RTEItem, 'description_stdWrap');

			}
		}
	}
	
	function getAdditionaljsPostMarker(& $template, & $sims, & $rems){
		// do nothing, to ensure that the preset marker doesn't get overwritten
	}
	
	function getAdditionaljsPreMarker(& $template, & $sims, & $rems){
		// do nothing, to ensure that the preset marker doesn't get overwritten
	}
	
	function getTeaserMarker(& $template, & $sims, & $rems){
		$sims['###TEASER###'] = '';
		if($this->isAllowed('teaser')) {
			$sims['###TEASER###'] = $this->applyStdWrap($this->object->getTeaser(), 'teaser_stdWrap');
		}
	}
	
	function getFrequencyMarker(& $template, & $sims, & $rems){
		$sims['###FREQUENCY###'] = '';
		$frequency_values = array('none', 'day', 'week', 'month', 'year');
		$frequency = '';
		
		if($this->isAllowed('recurring')) {
			foreach ($frequency_values as $freq) {
				$frequencyValue = $this->object->getFreq();
				if($freq == $frequencyValue) {
					$selectedFrequency = 'selected="selected"';
				} else {
					$selectedFrequency = '';
				}
				
				$frequency .= '<option value="'.$freq.'"'.$selectedFrequency.'>'.$this->controller->pi_getLL('l_'.$freq).'</option>';
			}
			$sims['###FREQUENCY###'] = $this->applyStdWrap($frequency, 'frequency_stdWrap');
		}
	}
	
	function getByDayMarker(& $template, & $sims, & $rems) {
		$sims['###BY_DAY###'] = '';
		if($this->isAllowed('recurring')) {
			$by_day = array('MO','TU','WE','TH','FR','SA','SU');
			$dayName = strtotime('next monday');
			$temp_sims = array();
			foreach ($by_day as $day) {
				if (in_array($day, $this->object->getByDay())){
					$temp_sims['###BY_DAY_CHECKED_'.$day.'###'] = 'checked />'.strftime('%a',$dayName);
				}
				else {
					$temp_sims['###BY_DAY_CHECKED_'.$day.'###'] = '/>'.strftime('%a',$dayName);
				}
				$dayName+=86400;
			}
			$sims['###BY_DAY###'] = $this->applyStdWrap(implode('###SPLITTER###',$temp_sims), 'byDay_stdWrap');
		} 
	}
	
	function getByMonthDayMarker(& $template, & $sims, & $rems){
		$sims['###BY_MONTHDAY###'] = '';
		if($this->isAllowed('recurring')) {
			$sims['###BY_MONTHDAY###'] = $this->applyStdWrap(implode(',',$this->object->getByMonthDay()), 'byMonthday_stdWrap');
		} 
	}
	
	function getByMonthMarker(& $template, & $sims, & $rems){
		$sims['###BY_MONTH###'] = '';
		if($this->isAllowed('recurring')) {
			$sims['###BY_MONTH###'] = $this->applyStdWrap(implode(',',$this->object->getByMonth()), 'byMonth_stdWrap');
		}
	}
	
	function getUntilMarker(& $template, & $sims, & $rems){
		$sims['###UNTIL###'] = '';
		if($this->isAllowed('recurring')) {
			$until = $this->object->getUntil();
			if(is_object($until) && $until->getYear()!=0) {
				$untilValue = $until->format(getFormatStringFromConf($this->conf));
				$sims['###UNTIL###'] = $this->applyStdWrap($untilValue, 'until_stdWrap');
			}else{
				$sims['###UNTIL###'] = $this->applyStdWrap('', 'until_stdWrap');
			}
		}
	}
	
	function getCountMarker(& $template, & $sims, & $rems){
		$sims['###COUNT###'] = '';
		if($this->isAllowed('recurring')) {
			$sims['###COUNT###'] = $this->applyStdWrap($this->object->getCount(), 'count_stdWrap');
		}
	}
	
	function getIntervalMarker(& $template, & $sims, & $rems){
		$sims['###INTERVAL###'] = '';
		if($this->isAllowed('recurring')) {
			$sims['###INTERVAL###'] = $this->applyStdWrap($this->object->getInterval(), 'interval_stdWrap');
		}
	}
	
	function getNotifyMarker(& $template, & $sims, & $rems){
		$sims['###NOTIFY###'] = '';
		if($this->isAllowed('notify')){
			$cal_notify_user = '';
			$allowedUsers = t3lib_div::trimExplode(',',$this->conf['rights.']['allowedUsers'],1);
			$selectedUsers = $this->object->getNotifyUserIds();
			if (empty($selectedUsers) && !$this->isEditMode) {
				$selectedUsers = t3lib_div::trimExplode(',',$this->conf['rights.']['create.']['event.']['fields.']['notify.']['defaultUser'],1);
			}
			$selectedUsersList = implode(',', $selectedUsers);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','fe_users','pid in ('.$this->conf['pidList'].')' . $this->cObj->enableFields('fe_users'));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if(!empty($allowedUsers) && t3lib_div::inList($this->conf['rights.']['allowedUsers'], $row['uid'])) {
					if (t3lib_div::inList($selectedUsersList,$row['uid'])) {
						$cal_notify_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'" checked="checked" name="tx_cal_controller[notify][]" />'.$row['username'].'<br />';
					}else{
						$cal_notify_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'"  name="tx_cal_controller[notify][]"/>'.$row['username'].'<br />';
					}
				}else if (empty($allowedUsers)){
					if (t3lib_div::inList($selectedUsersList,$row['uid'])) {
						$cal_notify_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'" checked="checked" name="tx_cal_controller[notify][]" />'.$row['username'].'<br />';
					}else{
						$cal_notify_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'"  name="tx_cal_controller[notify][]"/>'.$row['username'].'<br />';
					}
				}
				
			}
			/*$GLOBALS['TYPO3_DB']->sql_free_result($result);
			$allowedGroups = t3lib_div::trimExplode(',',$this->conf['rights.']['allowedGroups'],1);
			$selectedGroups = $this->object->getNotifyGroupIds();
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','fe_groups','pid in ('.$this->conf['pidList'].')' . $this->cObj->enableFields('fe_groups'));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if(!empty($allowedGroups) && array_search($row['uid'],$allowedGroups)){
					if(array_search($row['uid'],$selectedGroups)!==false){
						$cal_notify_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'" checked="checked" name="tx_cal_controller[notify][]" />'.$row['title'].'<br />';
					}else{
						$cal_notify_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'"  name="tx_cal_controller[notify][]"/>'.$row['title'].'<br />';
					}
				}else if (empty($allowedGroups)){
					if(array_search($row['uid'],$selectedGroups)!==false){
						$cal_notify_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'" checked="checked" name="tx_cal_controller[notify][]" />'.$row['title'].'<br />';
					}else{
						$cal_notify_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'"  name="tx_cal_controller[notify][]"/>'.$row['title'].'<br />';
					}
				}
				
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);*/
			$sims['###NOTIFY###'] = $this->applyStdWrap($cal_notify_user, 'notify_stdWrap');
		}
	}
	
	function getSharedMarker(& $template, & $sims, & $rems){
		$sims['###SHARED###'] = '';
		if($this->isAllowed('shared')){
			$cal_shared_user = '';
			$allowedUsers = t3lib_div::trimExplode(',',$this->conf['rights.']['allowedUsers'],1);
			$selectedUsers = $this->object->getSharedUsers();
			if (empty($selectedUsers) && !$this->isEditMode) {
				$selectedUsers = t3lib_div::trimExplode(',',$this->conf['rights.']['create.']['event.']['fields.']['shared.']['defaultUser'],1);
			}
			$selectedUsersList = implode(',', $selectedUsers);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','fe_users','pid in ('.$this->conf['pidList'].')' . $this->cObj->enableFields('fe_users'));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if(!empty($allowedUsers) && t3lib_div::inList($this->conf['rights.']['allowedUsers'], $row['uid'])) {
					if (t3lib_div::inList($selectedUsersList,$row['uid'])) {
						$cal_shared_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'" checked="checked" name="tx_cal_controller[shared][]" />'.$row['username'].'<br />';
					}else{
						$cal_shared_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'"  name="tx_cal_controller[shared][]"/>'.$row['username'].'<br />';
					}
				}else if (empty($allowedUsers)){
					if (t3lib_div::inList($selectedUsersList,$row['uid'])) {
						$cal_shared_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'" checked="checked" name="tx_cal_controller[shared][]" />'.$row['username'].'<br />';
					}else{
						$cal_shared_user .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['username'].'"  name="tx_cal_controller[shared][]"/>'.$row['username'].'<br />';
					}
				}
				
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
			$allowedGroups = t3lib_div::trimExplode(',',$this->conf['rights.']['allowedGroups'],1);
			$selectedGroups = $this->object->getSharedGroups();
			if (empty($selectedGroups) && !$this->isEditMode) {
				$selectedGroups = t3lib_div::trimExplode(',',$this->conf['rights.']['create.']['event.']['fields.']['shared.']['defaultGroup'],1);
			}
			$selectedGroupsList = implode(',', $selectedGroups);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','fe_groups','pid in ('.$this->conf['pidList'].')' . $this->cObj->enableFields('fe_groups'));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if(!empty($allowedGroups) && t3lib_div::inList($this->conf['rights.']['allowedGroups'], $row['uid'])) {
					if (t3lib_div::inList($selectedGroupsList,$row['uid'])) {
						$cal_shared_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'" checked="checked" name="tx_cal_controller[shared][]" />'.$row['title'].'<br />';
					}else{
						$cal_shared_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'"  name="tx_cal_controller[shared][]"/>'.$row['title'].'<br />';
					}
				}else if (empty($allowedGroups)){
					if (t3lib_div::inList($selectedGroupsList,$row['uid'])) {
						$cal_shared_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'" checked="checked" name="tx_cal_controller[shared][]" />'.$row['title'].'<br />';
					}else{
						$cal_shared_user .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'"  name="tx_cal_controller[shared][]"/>'.$row['title'].'<br />';
					}
				}
				
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
			$sims['###SHARED###'] = $this->applyStdWrap($cal_shared_user, 'shared_stdWrap');
		}
	}
	
	function getExceptionMarker(& $template, & $sims, & $rems){
		$sims['###EXCEPTION###'] = '';
		if($this->rightsObj->isAllowedToCreateEventException() || $this->rightsObj->isAllowedToEditEventException()){
			// creating options for exception events & -groups
			$exception = '';
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_cal_exception_event','pid in ('.$this->conf['pidList'].')'.$this->cObj->enableFields('tx_cal_exception_event'));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if(is_array($this->object->getExceptionSingleIds()) && array_search($row['uid'], $this->object->getExceptionSingleIds())!==false){
					$exception .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['title'].'" checked="checked" name="tx_cal_controller[exception_ids][]"/>'.$row['title'].'<br />';
				}else{
					$exception .= '<input type="checkbox" value="u_'.$row['uid'].'_'.$row['title'].'" name="tx_cal_controller[exception_ids][]" />'.$row['title'].'<br />';
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
						
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_cal_exception_event_group','pid in ('.$this->conf['pidList'].')'.$this->cObj->enableFields('tx_cal_exception_event_group'));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				if(is_array($this->object->getExceptionGroupIds()) && array_search($row['uid'], $this->object->getExceptionGroupIds())!==false){
					$exception .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'" checked="checked" name="tx_cal_controller[exception_ids][]" />'.$row['title'].'<br />';
				}else{
					$exception .= '<input type="checkbox" value="g_'.$row['uid'].'_'.$row['title'].'" name="tx_cal_controller[exception_ids][]" />'.$row['title'].'<br />';
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
			
			$sims['###EXCEPTION###'] = $this->cObj->stdWrap($exception, $this->conf['view.'][$this->conf['view'].'.']['exception_stdWrap.']);

		}
	}
	
	function getFormStartMarker(& $template, & $sims, & $rems){
		$temp = $this->cObj->getSubpart($template, '###FORM_START###');
		$temp_sims = array();

		$temp_sims['###L_WRONG_SPLIT_SYMBOL_MSG###'] = str_replace('###DATE_SPLIT_SYMBOL###',$this->conf['dateConfig.']['splitSymbol'],$this->controller->pi_getLL('l_wrong_split_symbol_msg'));
		$temp_sims['###L_WRONG_DATE_MSG###'] = $this->controller->pi_getLL('l_wrong_date');
		$temp_sims['###L_WRONG_TIME_MSG###'] = $this->controller->pi_getLL('l_wrong_time');
		$temp_sims['###L_IS_IN_PAST_MSG###'] = $this->controller->pi_getLL('l_is_in_past');
		$rems['###FORM_START###'] = $this->cObj->substituteMarkerArrayCached($temp, $temp_sims, array(), array ());
		
	}
	
	function addAdditionalMarker(& $template, & $sims, & $rems){
		$sims['###DATE_SPLIT_SYMBOL###'] = $this->conf['dateConfig.']['splitSymbol'];
		$sims['###DATE_DAY_POSITION###'] = $this->conf['dateConfig.']['dayPosition'];
		$sims['###DATE_MONTH_POSITION###'] = $this->conf['dateConfig.']['monthPosition'];
		$sims['###DATE_YEAR_POSITION###'] = $this->conf['dateConfig.']['yearPosition'];
		$sims['###VALIDATION###'] = $this->validation;
		
		$sims['###GETDATE###'] = $this->conf['getdate'];
		$sims['###GETTIME###'] = $this->conf['gettime'];
		$sims['###THIS_VIEW###'] = 'create_event';
		$sims['###NEXT_VIEW###'] = 'create_event';
		$sims['###LASTVIEW###'] = $this->controller->extendLastView();

		$sims['###OPTION###'] = $this->conf['option'];
		if(!$this->isEditMode){
			if(($this->isEditMode && !$this->rightsObj->isAllowedToEditEventCalendar()) || (!$this->isEditMode && $this->rightsObj->isAllowedToCreateEventCalendar())){
				$calendarArray = $this->modelObj->findAllCalendar('tx_cal_calendar',$this->conf['pidList']);				
				if (empty($calendarArray['tx_cal_calendar'])) {
				  return '<h3>You have to create a calendar before you can create events</h3>';
				}
			}
		}
		$linkParams = array();
		$linkParams['formCheck'] = '1';
		if(($this->isEditMode && !$this->rightsObj->isAllowedToEditEventCalendar()) || (!$this->isEditMode && $this->rightsObj->isAllowedToCreateEventCalendar())){
			//$linkParams['lastview'] = $this->controller->extendLastView();
		}
		$sims['###ACTION_URL###'] = $this->controller->pi_linkTP_keepPIvars_url( $linkParams);

		$sims['###CHANGE_CALENDAR_ACTION_URL###'] = $this->controller->pi_linkTP_keepPIvars_url();
	}
	
	function getDateFormatMarker(& $template, & $sims, & $rems){
		$dateFormatArray = array();
		$dateFormatArray[$this->conf['dateConfig.']['dayPosition']] = 'd';
		$dateFormatArray[$this->conf['dateConfig.']['monthPosition']] = 'm';
		$dateFormatArray[$this->conf['dateConfig.']['yearPosition']] = 'Y';
		
		$sims['###DATE_FORMAT###'] = $dateFormatArray[0].$this->conf['dateConfig.']['splitSymbol'].$dateFormatArray[1].$this->conf['dateConfig.']['splitSymbol'].$dateFormatArray[2];
	}
}
	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/view/class.tx_cal_create_event_view.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/view/class.tx_cal_create_event_view.php']);
}
?>