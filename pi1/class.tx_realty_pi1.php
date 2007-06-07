<?php
/***************************************************************
* Copyright notice
*
* (c) 2006-2007 Oliver Klee <typo3-coding@oliverklee.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'Realty List' for the 'realty' extension.
 *
 * @author	Oliver Klee <typo3-coding@oliverklee.de>
 */

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_templatehelper.php');

// field types for realty objects
define('TYPE_NUMERIC', 0);
define('TYPE_STRING', 1);
define('TYPE_BOOLEAN', 2);

class tx_realty_pi1 extends tx_oelib_templatehelper {
	/** same as class name */
	var $prefixId = 'tx_realty_pi1';
	/** path to this script relative to the extension dir */
	var $scriptRelPath = 'pi1/class.tx_realty_pi1.php';
	/** the extension key */
	var $extKey = 'realty';
	/** the upload directory for images */
	var $uploadDirectory = 'uploads/tx_realty/';
	/** the names of the DB tables for foreign keys */
	var $tableNames = array(
		'objects' => 'tx_realty_objects',
		'city' => 'tx_realty_cities',
		'district' => 'tx_realty_districts',
		'apartment_type' => 'tx_realty_apartment_types',
		'house_type' => 'tx_realty_house_types',
		'heating_type' => 'tx_realty_heating_types',
		'garage_type' => 'tx_realty_car_places',
		'pets' => 'tx_realty_pets',
		'state' => 'tx_realty_conditions',
		'images' => 'tx_realty_images',
		'images_relation' => 'tx_realty_objects_images_mm',
	);
	/** session key for storing the favorites list */
	var $favoritesSessionKey = 'tx_realty_favorites';
	/** session key for storing data of all favorites that currently get displayed */
	var $favoritesSessionKeyVerbose = 'tx_realty_favorites_verbose';

	/** the data of the currently displayed favorites using the keys [uid][fieldname] */
	var $favoritesDataVerbose;

	/**
	 * Display types of the records fields with the column names as keys.
	 * These types are used for deciding whether to display or hide a field
	 */
	var $fieldTypes = array(
		'object_number' => TYPE_STRING,
		'object_type' => TYPE_STRING,
		'title' => TYPE_STRING,
		'emphasized' => TYPE_STRING,
		'street' => TYPE_STRING,
		'zip' => TYPE_STRING,
		'city' => TYPE_STRING,
		'district' => TYPE_STRING,
		'number_of_rooms' => TYPE_STRING,
		'living_area' => TYPE_NUMERIC,
		'total_area' => TYPE_NUMERIC,
		'rent_excluding_bills' => TYPE_NUMERIC,
		'extra_charges' => TYPE_NUMERIC,
		'heating_included' => TYPE_BOOLEAN,
		'deposit' => TYPE_STRING,
		'provision' => TYPE_STRING,
		'usable_from' => TYPE_STRING,
		'buying_price' => TYPE_STRING,
		'year_rent' => TYPE_STRING,
		'rented' => TYPE_BOOLEAN,
		'apartment_type' => TYPE_STRING,
		'house_type' => TYPE_STRING,
		'floor' => TYPE_NUMERIC,
		'floors' => TYPE_NUMERIC,
		'bedrooms' => TYPE_NUMERIC,
		'bathrooms' => TYPE_NUMERIC,
		'heating_type' => TYPE_STRING,
		'garage_type' => TYPE_STRING,
		'garage_rent' => TYPE_NUMERIC,
		'garage_price' => TYPE_NUMERIC,
		'pets' => TYPE_STRING,
		'construction_year' => TYPE_NUMERIC,
		'state' => TYPE_STRING,
		'balcony' => TYPE_BOOLEAN,
		'garden' => TYPE_BOOLEAN,
		'elevator' => TYPE_BOOLEAN,
		'accessible' => TYPE_BOOLEAN,
		'assisted_living' => TYPE_BOOLEAN,
		'fitted_kitchen' => TYPE_BOOLEAN,
		'description' => TYPE_STRING,
		'equipment' => TYPE_STRING,
		'layout' => TYPE_STRING,
		'location' => TYPE_STRING,
		'misc' => TYPE_STRING,
	);

	/**
	 * Sort criteria that can be selected in the BE flexforms.
	 * Flexforms stores all the flags in one word with a bit for each checkbox,
	 * starting with the lowest bit for the first checkbox.
	 * We can have up to 10 checkboxes.
	 */
	var $sortCriteria = array(
		0x0001 => 'object_number',
		0x0002 => 'title',
		0x0004 => 'city',
		0x0008 => 'district',
		0x0010 => 'buying_price',
		0x0020 => 'rent_excluding_bills',
		0x0040 => 'number_of_rooms',
		0x0080 => 'living_area',
		0x0100 => 'tstamp',
	);

	var $pi_checkCHash = true;

	/**
	 * Displays the Realty Manager HTML.
	 *
	 * @param	string		default content string, ignore
	 * @param	array		TypoScript configuration for the plugin
	 *
	 * @return	string		HTML for the plugin
	 *
	 * @access	public
	 */
	function main($content, $conf)	{
		$this->init($conf);
		$this->pi_initPIflexForm();

		$this->getTemplateCode();
		$this->setLabels();
		$this->setCSS();
		$this->addCssToPageHeader();

		if (strstr($this->cObj->currentRecord, 'tt_content')) {
			$this->conf['pidList'] = $this->getConfValueString('pages');
			$this->conf['recursive'] = $this->getConfValueInteger('recursive');
		}

		$this->internal['currentTable'] = $this->tableNames['objects'];
		$this->securePiVars(array('city', 'image', 'remove', 'descFlag'));

		$result = '';

		switch ($this->getConfValueString('what_to_display')) {
			case 'gallery':
				$result = $this->createGallery();
				break;
			case 'city_selector':
				$result = $this->createCitySelector();
				break;
			case 'favorites':
				// The fallthrough is intended because createListView() and
				// createSingleView will differentiate later.
			case 'realty_list':
				// The fallthrough is intended.
			default:
				// Show the single view if a 'showUid' variable is set.
				if (isset($this->piVars['showUid']) && $this->piVars['showUid']) {
					$result = $this->createSingleView();
					// If the single view results in an error, use the list view instead.
					if (empty($result)) {
						$result = $this->createListView();
					}
				} else {
					$result = $this->createListView();
				}
				break;
		}

		return $this->pi_wrapInBaseClass($result);
	}

	/**
	 * Shows a list of database entries.
	 *
	 * @return	string		HTML list of table entries
	 *
	 * @access	protected
	 */
	function createListView()	{
		$result = '';

		$this->setSubpartContent('list_filter', $this->createCheckboxesFilter());

		$dbResult = $this->initListView();

		if (($this->internal['res_count'] > 0)
			&& $dbResult
			&& $GLOBALS['TYPO3_DB']->sql_num_rows($dbResult)) {
			$rows = array();

			$rowCounter = 0;
			while ($this->internal['currentRow'] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))	{
				$this->resetSubpartsHiding();
				$rows[] = $this->createListRow($rowCounter);
				$rowCounter++;
			}

			$listBody = implode('', $rows);
			$this->setMarkerContent('realty_items', $listBody);
			$this->setSubpartContent('pagination', $this->createPagination());
			$this->setSubpartContent('wrapper_sorting', $this->createSorting());
			// We manually populate the subpart because the automatic filling of
			// subparts doesn't work with nesting that deep.
			$this->setSubpartContent('list_result', $this->substituteMarkerArrayCached('LIST_RESULT'));
			$this->setSubpartContent('favorites_result', $this->substituteMarkerArrayCached('FAVORITES_RESULT'));
		} else {
			$this->setMarkerContent('message_noResultsFound', $this->pi_getLL('message_noResultsFound_'.$this->getConfValueString('what_to_display')));
			$this->setSubpartContent('list_result', $this->substituteMarkerArrayCached('EMPTY_LIST_RESULT'));
			$this->setSubpartContent('favorites_result', $this->substituteMarkerArrayCached('EMPTY_LIST_RESULT'));
		}
		if ($this->getConfValueString('what_to_display') == 'favorites') {
			if ($this->hasConfValueInteger('contactPID')) {
				$contact_url = htmlspecialchars($this->pi_linkTP_keepPIvars_url(
					array(),
					true,
					true,
					$this->getConfValueInteger('contactPID')
				));
				$this->setMarkerContent('contact_url', $contact_url);
			} else {
				$this->readSubpartsToHide('contact', 'wrapper');
			}
			$result = $this->substituteMarkerArrayCached('FAVORITES_VIEW');

			if ($this->hasConfValueString('favoriteFieldsInSession')
				&& isset($GLOBALS['TSFE']->fe_user)) {
				$GLOBALS['TSFE']->fe_user->setKey('ses', $this->favoritesSessionKeyVerbose, serialize($this->favoritesDataVerbose));
				$GLOBALS['TSFE']->fe_user->storeSessionData();
			}
		} else {
			$result = $this->substituteMarkerArrayCached('LIST_VIEW');
		}

		return $result;
	}

	/**
	 * Initializes the list view, but does not create any actual HTML output.
	 *
	 * @return	pointer		the result of a DB query for the realty objects to list (may be null)
	 *
	 * @access	protected
	 */
	function initListView() {
		// local settings for the listView function
		$lConf = $this->conf['listView.'];

		if (!isset($this->piVars['pointer'])) {
			$this->piVars['pointer'] = 0;
		}

		// initializing the query parameters
		if (isset($this->piVars['orderBy'])) {
			$this->internal['orderBy'] = $this->piVars['orderBy'];
		} else {
			$this->internal['orderBy'] = $lConf['orderBy'];
		}
		// initializing the query parameters
		if (isset($this->piVars['descFlag'])) {
			$this->internal['descFlag'] = $this->piVars['descFlag'];
		} else {
			$this->internal['descFlag'] = $lConf['descFlag'];
		}

		// number of results to show in a listing
		$this->internal['results_at_a_time'] = t3lib_div::intInRange($lConf['results_at_a_time'], 0, 1000, 3);

		// the maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
		$this->internal['maxPages'] = t3lib_div::intInRange($lConf['maxPages'], 1, 1000, 2);

		$this->internal['orderByList'] = 'object_number,title,city,district,buying_price,rent_excluding_bills,number_of_rooms,living_area,tstamp';

		$additionalWhereClause = ($this->hasConfValueString('staticSqlFilter')) ?
			// The space before the "AND" will be automatically added by pi_exec_query,
			// and so we don't need to explicitely add it.
			'AND '.$this->getConfValueString('staticSqlFilter') :
			'';

		// find only cities that match the uid in piVars['city']
		if (isset($this->piVars['city'])) {
			$additionalWhereClause .=  ' AND city='.$this->piVars['city'];
		}

		if ($this->getConfValueString('what_to_display') == 'favorites') {
			// The favorites page should never get cached.
			$GLOBALS['TSFE']->set_no_cache();
			// The favorites list is the only content element that may
			// accept changes to the favorites list.
			$this->processSubmittedFavorites();
			// If the favorites list is empty, make sure to create a valid query
			// that will produce zero results.
			$additionalWhereClause .= ($this->getFavorites() != '') ?
				' AND uid IN('.$this->getFavorites().')' :
				' AND (0=1)';
			$this->favoritesDataVerbose = array();
		}

		$searchSelection = implode(',', $this->getSearchSelection());
		if (!empty($searchSelection) && ($this->hasConfValueString('checkboxesFilter'))) {
			$additionalWhereClause .=
				' AND '.$this->getConfValueString('checkboxesFilter')
				.' IN ('.$searchSelection.')';
		}

		// get number of records (the "true" activates the "counting" mode)
		$dbResultCounter = $this->pi_exec_query($this->internal['currentTable'], true, $additionalWhereClause);

		$counterRow = $GLOBALS['TYPO3_DB']->sql_fetch_row($dbResultCounter);
		$this->internal['res_count'] = $counterRow[0];
		// The number of the last possible page in a listing
		// (which is the number of pages minus one as the numbering starts at zero).
		// If there are no results, the last page still has the number 0.
		$this->internal['lastPage'] = max(0, ceil($this->internal['res_count'] / $this->internal['results_at_a_time']) - 1);

		// make listing query, pass query to SQL database
		$dbResult = $this->pi_exec_query($this->internal['currentTable'], false, $additionalWhereClause);

		$this->setMarkerContent('self_url', $this->getSelfUrl());
		$this->setMarkerContent('favorites_url', $this->getFavoritesUrl());

		return $dbResult;
	}

	/**
	 * Displays a single item from the database.
	 *
	 * @return	string		HTML of a single database entry (may be an empty string in the case of an error)
	 *
	 * @access	protected
	 */
	function createSingleView()	{
		$result = '';

		$this->internal['currentRow'] = $this->pi_getRecord($this->tableNames['objects'], $this->piVars['showUid']);
		if (!empty($this->internal['currentRow'])) {
			// This sets the title of the page for display and for use in indexed search results.
			if (!empty($this->internal['currentRow']['title']))	{
				$GLOBALS['TSFE']->page['title'] = $this->internal['currentRow']['title'];
				$GLOBALS['TSFE']->indexedDocTitle = $this->internal['currentRow']['title'];
			}

			// stuff that should always be visible
			foreach (array(
				'title',
				'uid',
				'city',
			) as $key) {
				$this->setMarkerContent($key, $this->getFieldContent($key));
			}

			// string stuff that should conditionally be visible
			foreach (array(
				'object_number',
				'street',
				'district',
				'description',
				'location',
				'equipment',
				'misc'
			) as $key) {
				$this->setOrDeleteMarkerIfNotEmpty($key, $this->getFieldContent($key), '', 'field_wrapper');
			}

			// marker for button
			$this->setMarkerContent('back_url', $this->pi_linkTP_keepPIvars_url(array('showUid' => '')));
			$this->setMarkerContent('favorites_url', $this->getFavoritesUrl());

			if ($this->getConfValueString('what_to_display') == 'favorites') {
				$this->readSubpartsToHide('add_to_favorites', 'wrapper');
			} else {
				$this->readSubpartsToHide('remove_from_favorites', 'wrapper');
			}

			$this->createOverviewTableInSingleView();
			$this->setSubpartContent('images_list', $this->createImagesInSingleView());

			$result = $this->substituteMarkerArrayCached('SINGLE_VIEW');
		}

		return $result;
	}

	/**
	 * Fills the subpart ###OVERVIEW_TABLE### with the contents of the current record's
	 * DB fields specified via the TS setup variable "fieldsInSingleViewTable"".
	 *
	 * @return	boolean		true if at least one row has been filled, false otherwise
	 *
	 * @access	protected
	 */
	function createOverviewTableInSingleView() {
		$result = false;

		$rows = array();
		$rowCounter = 0;
		$fieldNames = explode(',', $this->getConfValueString('fieldsInSingleViewTable'));

		foreach ($fieldNames as $currentFieldName) {
			$trimmedFieldName = trim($currentFieldName);
			// Is the field name valid?
			if (isset($this->fieldTypes[$trimmedFieldName])) {
				$isRowSet = false;
				switch($this->fieldTypes[$trimmedFieldName]) {
					case TYPE_NUMERIC:
						$isRowSet = $this->setMarkerIfNotZero('data_current_row',
							$this->getFieldContent($trimmedFieldName));
						break;
					case TYPE_STRING:
						$isRowSet = $this->setMarkerIfNotEmpty('data_current_row',
							$this->getFieldContent($trimmedFieldName));
						break;
					case TYPE_BOOLEAN:
						if ($this->internal['currentRow'][$trimmedFieldName]) {
							$this->setMarkerContent('data_current_row', $this->pi_getLL('message_yes'));
							$isRowSet = true;
						}
						break;
					default:
						break;
				}
				if ($isRowSet) {
					$position = ($rowCounter % 2) ? 'odd' : 'even';
					$this->setMarkerContent('class_position_in_list', $position);
					$this->setMarkerContent('label_current_row', $this->pi_getLL('label_'.$trimmedFieldName));
					$rows[] = $this->substituteMarkerArrayCached('OVERVIEW_ROW');
					$rowCounter++;
					$result = true;
				}
			}
		}

		$this->setSubpartContent('overview_table', implode(chr(10), $rows));

		return $result;
	}

	/**
	 * Creates all images that are attached to the current record.
	 *
	 * Each image's size is limited by singleImageMaxX and singleImageMaxY
	 * in TS setup.
	 *
	 * @return	string		HTML for the images
	 *
	 * @access	protected
	 */
	function createImagesInSingleView() {
		$result = '';

		$counter = 0;
		$currentImageTag = $this->getImageLinkedToGallery('singleImageMax');

		while (!empty($currentImageTag)) {
			$this->setMarkerContent('one_image_tag', $currentImageTag);
			$result .= $this->substituteMarkerArrayCached('ONE_IMAGE_CONTAINER');
			$counter++;
			$currentImageTag = $this->getImageLinkedToGallery('singleImageMax', $counter);
		}

		return $result;
	}

	/**
	 * Returns a single table row for list view.
	 *
	 * @param	integer		Row counter. Starts at 0 (zero). Used for alternating class values in the output rows.
	 *
	 * @return	string		HTML output, a table row with a class attribute set (alternative based on odd/even rows)
	 *
	 * @access	protected
	 */
	function createListRow($rowCounter = 0) {
		$position = ($rowCounter == 0) ? 'first' : '';
		$this->setMarkerContent('class_position_in_list', $position);

		foreach (array(
			'uid',
			'linked_title',
			'city',
			'district',
			'living_area',
			'rent_excluding_bills',
			'extra_charges',
			'number_of_rooms',
			'features',
			'list_image_left',
			'list_image_right',
		) as $key) {
			$this->setMarkerContent($key, $this->getFieldContent($key));
		}

		if (($this->getConfValueString('what_to_display') == 'favorites')
			&& ($this->hasConfValueString('favoriteFieldsInSession'))) {
			$this->favoritesDataVerbose[$this->getFieldContent('uid')] = array();
			foreach (explode(',', $this->getConfValueString('favoriteFieldsInSession')) as $key) {
				$this->favoritesDataVerbose[$this->getFieldContent('uid')][$key] = $this->getFieldContent($key);
			}
		}

		return $this->substituteMarkerArrayCached('LIST_ITEM');
	}

	/**
	 * Returns the trimmed content of a given field for the list view.
	 * In the case of the key "title", the result will be wrapped
	 * in a link to the detail page of that particular item.
	 *
	 * @param	string		key of the field to retrieve (the name of a database column), may not be empty
	 *
	 * @return	string		value of the field (may be empty)
	 *
	 * @access	protected
	 */
	function getFieldContent($key)	{
		$result = '';

		switch($key) {
			case 'linked_title':
				// disable the caching if we are in the favorites list
				$useCache = ($this->getConfValueString('what_to_display') != 'favorites');
				$result = $this->pi_list_linkSingle(
					$this->internal['currentRow']['title'],
					intval($this->internal['currentRow']['uid']),
					$useCache,
					array(),
					false,
					$this->getConfValueInteger('singlePID')
				);
				break;

			case 'state':
				// The fallthrough is intended.
			case 'pets':
				// The fallthrough is intended.
			case 'garage_type':
				// The fallthrough is intended.
			case 'heating_type':
				// The fallthrough is intended.
			case 'house_type':
				// The fallthrough is intended.
			case 'apartment_type':
				// The fallthrough is intended.
			case 'city':
				// The fallthrough is intended.
			case 'district':
				$result = $this->getForeignRecordTitle($key);
				break;

			case 'total_area':
				// The fallthrough is intended.
			case 'living_area':
				$result = $this->getFormattedArea($key);
				break;

			case 'rent_excluding_bills':
				// The fallthrough is intended.
			case 'extra_charges':
				// The fallthrough is intended.
			case 'buying_price':
				// The fallthrough is intended.
			case 'year_rent':
				// The fallthrough is intended.
			case 'garage_rent':
				// The fallthrough is intended.
			case 'garage_price':
				$this->removeSubpartIfEmptyInteger($key, 'wrapper');
				$result = $this->getFormattedCurrency($key);
				break;

			case 'number_of_rooms':
				$this->removeSubpartIfEmptyString($key, 'wrapper');
				$result = $this->internal['currentRow'][$key];
				break;
			case 'features':
				$result = $this->getFeatureList();
				break;
			case 'usable_from':
				// If no date is set, assume "now".
				$result = (!empty($this->internal['currentRow']['usable_from'])) ?
					$this->internal['currentRow']['usable_from'] :
					$this->pi_getLL('message_now');
				break;

			case 'list_image_right':
				// If there is only one image, the right image will be filled.
				$result = $this->getImageLinkedToGallery('listImageMax');
				break;
			case 'list_image_left':
				// If there is only one image, the left image will be empty.
				$result = $this->getImageLinkedToGallery('listImageMax', 1);
				break;

			case 'description':
				// The fallthrough is intended.
			case 'equipment':
				// The fallthrough is intended.
			case 'location':
				// The fallthrough is intended.
			case 'misc':
				$result = $this->pi_RTEcssText($this->internal['currentRow'][$key]);
				break;

			default:
				$result = $this->internal['currentRow'][$key];
				break;
		}

		return trim($result);
	}

	/**
	 * Retrieves a foreign key from the record field $key of the current record.
	 * Then the corresponding record is looked up from $table, trimmed and returned.
	 *
	 * Returns an empty string if there is no such foreign key, the corresponding
	 * foreign record does not exist or if it is an empty string.
	 *
	 * @param	string		key of the field that contains the foreign key of the table to retrieve.
	 *
	 * @return	string		the title of the record with the given UID in the foreign table, may be empty
	 *
	 * @access	protected
	 */
	function getForeignRecordTitle($key) {
		$result = '';

		/** this will be 0 if there is no record entered */
		$foreignKey = intval($this->internal['currentRow'][$key]);
		$tableName = $this->tableNames[$key];

		if ($foreignKey) {
			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'title',
				$tableName,
				'uid='.$foreignKey
					.t3lib_pageSelect::enableFields($tableName)
			);
			if ($dbResult && $GLOBALS['TYPO3_DB']->sql_num_rows($dbResult)) {
				$dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
				$result = $dbResultRow['title'];
			}
		}

		return $result;
	}

	/**
	 * Retrieves the value of the record field $key formatted as an area.
	 * If the field's value is empty or its intval is zero, an empty string will be returned.
	 *
	 * @param	string		key of the field to retrieve (the name of a database column), may not be empty
	 *
	 * @return	string		HTML for the number in the field formatted using decimalSeparator and areaUnit from the TS setup, may be an empty string
	 *
	 * @access	protected
	 */
	function getFormattedArea($key) {
		return $this->getFormattedNumber($key, $this->pi_getLL('label_squareMeters'));
	}

	/**
	 * Retrieves the value of the record field $key formatted as a currency
	 * with the string set via TS setup as "currencyUnit" appended.
	 * If the field's value is empty or its intval is zero, an empty string will be returned.
	 *
	 * @param	string		key of the field to retrieve (the name of a database column), may not be empty
	 *
	 * @return	string		HTML for the number in the field formatted using decimalSeparator and currencyUnit from the TS setup, may be an empty string
	 *
	 * @access	protected
	 */
	function getFormattedCurrency($key) {
		return $this->getFormattedNumber($key, $this->getConfValueString('currencyUnit'));
	}

	/**
	 * Retrieves the value of the record field $key and formats,
	 * using the value from decimalSeparator from the TS setup and appending $unit.
	 * If the field's value is empty or its intval is zero, an empty string will be returned.
	 *
	 * @param	string		key of the field to retrieve (the name of a database column), may not be empty
	 *
	 * @return	string		HTML for the number in the field formatted using decimalSeparator with $unit appended, may be an empty string
	 *
	 * @access	protected
	 */
	function getFormattedNumber($key, $unit) {
		$result = '';

		$rawValue = $this->internal['currentRow'][$key];

		if (!empty($rawValue) && (intval($rawValue) !== 0) ) {
			$withDecimal = preg_replace('/\./', $this->getConfValueString('decimalSeparator'), $rawValue);
			$result = $withDecimal.'&nbsp;'.$unit;
		}

		return $result;
	}

	/**
	 * Removes a subpart ###PREFIX_KEY### (or ###KEY### if the prefix is empty)
	 * if the record field $key intvals to zero.
	 * For the subpart name, $key and $prefix will be automatically uppercased.
	 *
	 * If the record field intvals to a non-zero value, nothing happens.
	 *
	 * @param	string		key of the label to retrieve (the name of a database column), may not be empty
	 * @param	string		prefix to the subpart name (may be empty, case-insensitive, will get uppercased)
	 *
	 * @access	protected
	 */
	function removeSubpartIfEmptyInteger($key, $prefix = '') {
		if (intval($this->internal['currentRow'][$key]) == 0) {
			$this->readSubpartsToHide($key, $prefix);
		}
		return;
	}

	/**
	 * Removes a subpart ###PREFIX_KEY### (or ###KEY### if the prefix is empty)
	 * if the record field $key is an empty string.
	 * For the subpart name, $key and $prefix will be automatically uppercased.
	 *
	 * If the record field is a non-empty-string, nothing happens.
	 *
	 * @param	string		key of the label to retrieve (the name of a database column), may not be empty
	 * @param	string		prefix to the subpart name (may be empty, case-insensitive, will get uppercased)
	 *
	 * @access	protected
	 */
	function removeSubpartIfEmptyString($key, $prefix = '') {
		if (empty($this->internal['currentRow'][$key])) {
			$this->readSubpartsToHide($key, $prefix);
		}
		return;
	}

	/**
	 * Gets a comma-separated short list of important features of the current
	 * realty object:
	 * DB relations: apartment_type, house_type, heating_type, garage_type
	 * boolean: balcony, garden, elevator, accessible, assisted_living, fitted_kitchen
	 * integer: year of construction, first possible usage date, object number
	 *
	 * @return	string		comma-separated list of features
	 */
	function getFeatureList() {
		$features = array();

		// get features described by DB relations
		foreach (array('apartment_type', 'house_type', 'heating_type', 'garage_type') as $key) {
			if ($this->getForeignRecordTitle($key) != '') {
				$features[] = $this->getForeignRecordTitle($key);
			}
		}

		// get features set with (boolean) checkboxes
		foreach (array('balcony', 'garden', 'elevator', 'accessible', 'assisted_living', 'fitted_kitchen') as $key) {
			if ($this->internal['currentRow'][$key]) {
				$features[] = ($this->pi_getLL('label_'.$key.'_short') != '')
					? $this->pi_getLL('label_'.$key.'_short')
					: $this->pi_getLL('label_'.$key);
			}
		}

		if ($this->internal['currentRow']['construction_year']) {
			$features[] = $this->pi_getLL('label_construction_year').' '.$this->internal['currentRow']['construction_year'];
		}

		$features[] = $this->pi_getLL('label_usable_from_short').' '.$this->getFieldContent('usable_from');

		if (!empty($this->internal['currentRow']['object_number'])) {
			$features[] = $this->pi_getLL('label_object_number').' '.$this->internal['currentRow']['object_number'];
		}

		return implode(', ', $features);
	}

	/**
	 * Gets an image from the current record's image list as a complete IMG tag
	 * with alt text and title text (the image caption as defined in the DB).
	 * The image's size can be limited by two TS setup variables.
	 * They names need to begin with the string defined as $maxSizeVariable.
	 * The variable for the maximum width will then have the name set in
	 * $maxSizVariable with a "X" appended. The variable for the maximum height
	 * works the same, just with a "Y" appended.
	 *
	 * Example: If $maxSizeVariable is set to "listImageMax", the maximum width and height should be stored
	 * in the TS setup variables "listImageMaxX" and "listImageMaxY".
	 *
	 * If no image is found, an empty string is returned.
	 *
	 * @param	string		prefix to the TS setup variables that define the max size, will be prepended to "X" and "Y"
	 * @param	integer		the number of the image to retrieve (zero-based, may be zero)
	 *
	 * @return	string		IMG tag
	 *
	 * @access	protected
	 */
	function getImage($maxSizeVariable, $offset = 0) {
		$result = '';

		$dbResult = $this->queryForImage($offset);

		if ($dbResult && $GLOBALS['TYPO3_DB']->sql_num_rows($dbResult)) {
			$dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
			$result = $this->createImageTag($dbResultRow['image'], $maxSizeVariable, $dbResultRow['caption']);
		}

		return $result;
	}

	/**
	 * Gets an image from the current record's image list as a complete IMG tag
	 * with alt text and title text (the image caption as defined in the DB),
	 * wrapped in a link pointing to the image gallery.
	 *
	 * The PID of the target page can be set using flexforms. The link target
	 * can be set using the TS setup variable "galleryLinkTarget".
	 *
	 * If galleryPopupParameters is set in TS setup, the link will have an
	 * additional onclick handler to open the gallery in a pop-up window.
	 *
	 * The image's size can be limited by two TS setup variables.
	 * They names need to begin with the string defined as $maxSizeVariable.
	 * The variable for the maximum width will then have the name set in
	 * $maxSizVariable with a "X" appended. The variable for the maximum height
	 * works the same, just with a "Y" appended.
	 *
	 * Example: If $maxSizeVariable is set to "listImageMax", the maximum width and height should be stored
	 * in the TS setup variables "listImageMaxX" and "listImageMaxY".
	 *
	 * If no image is found, an empty string is returned.
	 *
	 * @param	string		prefix to the TS setup variables that define the max size, will be prepended to "X" and "Y"
	 * @param	integer		the number of the image to retrieve (zero-based, may be zero)
	 *
	 * @return	string		IMG tag wrapped in a link (may be empty)
	 *
	 * @access	protected
	 */
	function getImageLinkedToGallery($maxSizeVariable, $offset = 0) {
		$result = $this->getImage($maxSizeVariable, $offset);

		if (!empty($result) && $this->hasConfValueInteger('galleryPID')) {
			$galleryUrl = htmlspecialchars($this->pi_linkTP_keepPIvars_url(
				array(
					'showUid' => $this->internal['currentRow']['uid'],
					'image' => $offset
				),
				true,
				true,
				$this->getConfValueInteger('galleryPID')
			));
			$linkTarget = $this->hasConfValueString('galleryLinkTarget')
				? ' target="'.$this->getConfValueString('galleryLinkTarget').'"'
				: '' ;
			$onClick = '';
			if ($this->hasConfValueString('galleryPopupParameters')) {
				$onClick = ' onclick="window.open(\''
					.$galleryUrl.'\', \''
					.$this->getConfValueString('galleryPopupWindowName').'\', \''
					.$this->getConfValueString('galleryPopupParameters')
					.'\'); return false;"';
			}
			$result = '<a href="'.$galleryUrl.'"'.$linkTarget.$onClick.'>'.$result.'</a>';
		}

		return $result;
	}

	/**
	 * Gets the caption of an image from the current record's image list.
	 *
	 * If no image is found (or the caption is empty), an empty string is returned.
	 *
	 * @param	integer		the number of the image for which to retrieve the caption (zero-based, may be zero)
	 *
	 * @return	string		image caption (may be empty)
	 *
	 * @access	protected
	 */
	function getImageCaption($offset = 0) {
		$result = '';

		$dbResult = $this->queryForImage($offset);

		if ($dbResult && $GLOBALS['TYPO3_DB']->sql_num_rows($dbResult)) {
			$dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
			$result = $dbResultRow['caption'];
		}

		return $result;
	}

	/**
	 * Queries for an image that is associated with the current record.
	 *
	 * If no image is found or a DB error has occured, null is returned.
	 *
	 * @param	integer		the number of the image to retrieve (zero-based, may be zero)
	 *
	 * @return	pointer		SQL result pointer (may be null)
	 *
	 * @access	protected
	 */
	function queryForImage($offset = 0) {
		$where = 'AND '.$this->tableNames['objects'].'.uid='.$this->internal['currentRow']['uid'];

		return $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'image, caption',
			$this->tableNames['objects'],
			$this->tableNames['images_relation'],
			$this->tableNames['images'],
			$where,
			'',
			'sorting',
			intval($offset).',1'
		);
	}

	/**
	 * Counts the images that are associated with the current record.
	 *
	 * @return	integer		the number of images associated with the current record (may be zero)
	 *
	 * @access	protected
	 */
	function countImages() {
		$result = 0;
		$where = 'uid_local='.$this->internal['currentRow']['uid'];

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'COUNT(*) as number',
			$this->tableNames['images_relation'],
			$where
		);
		if ($dbResult) {
			$dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
			$result = $dbResultRow['number'];
		}

		return $result;
	}

	/**
	 * Creates an IMG tag for a resized image version of $filename in
	 * this extension's upload directory.
	 *
	 * @param	string		filename of the original image relative to this extension's upload directory (may not be empty)
	 * @param	string		prefix to the TS setup variables that define the max size, will be prepended to "X" and "Y"
	 * @param	string		text used for the alt and title attributes (may be empty)
	 *
	 * @return	string		IMG tag
	 *
	 * @access	protected
	 */
	function createImageTag($filename, $maxSizeVariable, $caption = '') {
		$fullPath = $this->uploadDirectory.$filename;
		$maxWidth = $this->getConfValueInteger($maxSizeVariable.'X');
		$maxHeight = $this->getConfValueInteger($maxSizeVariable.'Y');

		return $this->createRestrictedImage($fullPath, $caption, $maxWidth, $maxHeight, 0, $caption);
	}

	/**
	 * Creates an image gallery for the selected gallery item.
	 * If that item contains no images or the image number is invalid, an error
	 * message will be displayed instead.
	 *
	 * @return	string		HTML of the gallery (will not be empty)
	 *
	 * @access	protected
	 */
	function createGallery() {
		$result = '';
		$isOkay = false;

		if (isset($this->piVars['showUid']) && $this->piVars['showUid']) {
			$this->internal['currentRow'] = $this->pi_getRecord($this->tableNames['objects'], $this->piVars['showUid']);

			// This sets the title of the page for display and for use in indexed search results.
			if (!empty($this->internal['currentRow']['title']))	{
				$GLOBALS['TSFE']->page['title'] = $this->internal['currentRow']['title'];
				$GLOBALS['TSFE']->indexedDocTitle = $this->internal['currentRow']['title'];
			}

			$numberOfImages = $this->countImages();
			if ($numberOfImages
				&& ($this->piVars['image'] >= 0)
				&& ($this->piVars['image'] < $numberOfImages)) {
				$this->setMarkerContent('title', $this->internal['currentRow']['title']);

				$this->createGalleryFullSizeImage();

				$this->setSubpartContent('thumbnail_item', $this->createGalleryThumbnails());
				$result = $this->substituteMarkerArrayCached('GALLERY_VIEW');
				$isOkay = true;
			}
		}

		if (!$isOkay) {
			$this->setMarkerContent('message_invalidImage', $this->pi_getLL('message_invalidImage'));
			$result = $this->substituteMarkerArrayCached('GALLERY_ERROR');
			// send a 404 to inform crawlers that this URL is invalid
			header('Status: 404 Not Found');
		}

		return $result;
	}

	/**
	 * Creates the gallery's full size image for the image specified in
	 * $this->piVars['image'] and fills in the corresponding markers and
	 * subparts.
	 *
	 * The image's size is limited by galleryFullSizeImageX and
	 * galleryFullSizeImageY in TS setup.
	 *
	 * @access	protected
	 */
	function createGalleryFullSizeImage() {
		$imageTag = $this->getImage('galleryFullSizeImage', $this->piVars['image']);

		$numberOfImages = $this->countImages();
		if ($numberOfImages > 1) {
			$nextImageNumber = ($this->piVars['image'] + 1) % $numberOfImages;
			$url = htmlspecialchars($this->pi_linkTP_keepPIvars_url(array('image' => $nextImageNumber), true));
			$imageTag = '<a href="'.$url.'" title="'.$this->pi_getLL('label_next_image').'">'.$imageTag.'</a>';
		}

		$this->setMarkerContent('image_fullsize', $imageTag);
		$this->setMarkerContent('caption_fullsize', $this->getImageCaption($this->piVars['image']));

		return;
	}

	/**
	 * Creates thumbnails of the current record for the gallery. The thumbnails
	 * are linked for the full-size display of the corresponding image (except
	 * for the thumbnail of the current image which is not linked).
	 *
	 * Each image's size is limited by galleryThumbnailX and galleryThumbnailY
	 * in TS setup.
	 *
	 * @return	string		HTML for all thumbnails
	 *
	 * @access	protected
	 */
	function createGalleryThumbnails() {
		$result = '';

		$counter = 0;
		$currentImageTag = $this->getImage('galleryThumbnail');

		while (!empty($currentImageTag)) {
			// create a link for the full-size display of images except for the current image
			if ($counter != $this->piVars['image']) {
				$imageTag = $this->pi_linkTP_keepPIvars($currentImageTag, array('image' => $counter), true);
			} else {
				$imageTag = $currentImageTag;
			}

			$this->setMarkerContent('image_thumbnail', $imageTag);
			$result .= $this->substituteMarkerArrayCached('THUMBNAIL_ITEM');

			$counter++;
			$currentImageTag = $this->getImage('galleryThumbnail', $counter);
		}

		return $result;
	}

	/**
	 * Creates a form for selecting a single city.
	 *
	 * @return	string		HTML of the city selector (will not be empty)
	 *
	 * @access	protected
	 */
	function createCitySelector() {
		// set marker for target page of form
		$this->setMarkerContent('target_url', $this->pi_getPageLink($this->getConfValueInteger('citySelectorTargetPID')));

		// setup query
		$localTable = $this->tableNames['objects'];
		$foreignTable = $this->tableNames['city'];

		$selectFields = $foreignTable.'.uid, '.$foreignTable.'.title';
		$table = $localTable.','.$foreignTable;
		$whereClause = $localTable.'.city='.$foreignTable.'.uid';
		$whereClause .= tslib_cObj::enableFields($localTable);
		$whereClause .= tslib_cObj::enableFields($foreignTable);
		$groupBy = 'uid';
		$orderBy = $foreignTable.'.title';

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $table, $whereClause, $groupBy, $orderBy);

		// build array of cities from DB result
		$cities = array();
		if ($dbResult && $GLOBALS['TYPO3_DB']->sql_num_rows($dbResult)) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult))	{
				$cities[] = $row;
			}
		}

		// create options for <select>
		$options = '';
		if (count($cities)) {
			foreach ($cities as $city) {
				$options .= '<option value="'.$city['uid'].'">'.$city['title'].'</option>'.chr(10);
			}
		}
		$this->setOrDeleteMarkerIfNotEmpty('citySelector', $options, 'options', 'wrapper');

		return $this->substituteMarkerArrayCached('CITY_SELECTOR');
	}

	/**
	 * Processes the UIDs submitted in $this->piVars['favorites']
	 * if $this->piVars['favorites'] is set.
	 *
	 * If $this->piVars['remove'] is set to "1", the submitted items will be
	 * removed from the list of favorites.
	 * Otherwise, these items will get added to the list of favorites.
	 *
	 * Please note that $this->piVars['remove'] is expected to already be int-safe.
	 *
	 * @access	protected
	 */
	function processSubmittedFavorites() {
		if (isset($this->piVars['favorites']) && !empty($this->piVars['favorites'])) {
			if ($this->piVars['remove']) {
				$this->removeFromFavorites($this->piVars['favorites']);
			} else {
				$this->addToFavorites($this->piVars['favorites']);
			}
		}
		return;
	}

	/**
	 * Adds some items to the favorites list (which is stored in an anonymous
	 * session). The object UIDs are added to the list regardless of whether
	 * there actually are objects with those UIDs. That case is harmless
	 * because the favorites list serves as a filter merely.
	 *
	 * @param	array		list of realty object UIDs to add (will be intvaled by this function), may be empty or even null
	 *
	 * @access	protected
	 */
	function addToFavorites($itemsToAdd) {
		if ($itemsToAdd) {
			$favorites = $this->getFavoritesArray();

			foreach ($itemsToAdd as $currentItem) {
				$favorites[] = intval($currentItem);
			}
			$this->storeFavorites($favorites);
		}

		return;
	}

	/**
	 * Removes some items to the favorites list (which is stored in an anonymous
	 * session). If some of the UIDs in $itemsToRemove are not in the favorites
	 * list, they will silently being ignored (no harm done here).
	 *
	 * @param	array		list of realty object UIDs to to remove (will be intvaled by this function), may be empty or even null
	 *
	 * @access	protected
	 */
	function removeFromFavorites($itemsToRemove) {
		if ($itemsToRemove) {
			$favorites = $this->getFavoritesArray();

			foreach ($itemsToRemove as $currentItem) {
				$key = array_search($currentItem, $favorites);
				// $key will be false if the item has not been found.
				// Zero, on the other hand, is a valid key.
				if ($key !== false) {
					unset($favorites[$key]);
				}
			}
			$this->storeFavorites($favorites);
		}

		return;
	}

	/**
	 * Gets the favorites list (which is stored in an anonymous session) as a
	 * comma-separated list of UIDs. The UIDs are int-safe (this is ensured by
	 * addToFavorites()), but they are not guaranteed to point to existing
	 * records. In addition, each element is ensured to be unique
	 * (by storeFavorites()).
	 *
	 * If the list is empty (or has not been created yet), an empty string will
	 * be returned.
	 *
	 * @return	string		comma-separated list of UIDs of the objects on the favorites list (may be empty)
	 *
	 * @access	protected
	 *
	 * @see	getFavoritesArray
	 * @see	addToFavorites
	 * @see	storeFavorites
	 */
	function getFavorites() {
		$result = '';

		if (isset($GLOBALS['TSFE']->fe_user)) {
			$result = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->favoritesSessionKey);
		}

		return $result;
	}

	/**
	 * Gets the favorites list (which is stored in an anonymous session) as an
	 * array of UIDs. The UIDs are int-safe (this is ensured by
	 * addToFavorites()), but they are not guaranteed to point to existing
	 * records. In addition, each array element is ensured to be unique
	 * (by storeFavorites()).
	 *
	 * If the list is empty (or has not been created yet), an empty array will
	 * be returned.
	 *
	 * @return	array		list of UIDs of the objects on the favorites list (may be empty, but will not be null)
	 *
	 * @access	protected
	 *
	 * @see	getFavorites
	 * @see	addToFavorites
	 * @see	storeFavorites
	 */
	function getFavoritesArray() {
		$result = array();

		$favorites = $this->getFavorites();
		if (!empty($favorites)) {
			$result = explode(',', $favorites);
		}

		return $result;
	}

	/**
	 * Stores the favorites given in $favorites in an anonymous session.
	 *
	 * Before storing, the list of favorites is clear of duplicates.
	 *
	 * @param	array		list of UIDs in the favorites list to store, must already be int-safe, may be empty, must not be null
	 *
	 * @access	protected
	 */
	function storeFavorites($favorites) {
		$favoritesString = implode(',', array_unique($favorites));

		if (isset($GLOBALS['TSFE']->fe_user)) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->favoritesSessionKey, $favoritesString);
			$GLOBALS['TSFE']->fe_user->storeSessionData();
		}

		return;
	}

	/**
	 * Gets the selected values of the search checkboxes from
	 * $this->piVars['search'].
	 *
	 * @return	array		array of unique, int-safe values from $this->piVars['search'] (may be empty, but not null)
	 *
	 * @access	protected
	 */
	function getSearchSelection() {
		$result = array();

		if (isset($this->piVars['search'])) {
			if (is_array($this->piVars['search'])) {
				foreach ($this->piVars['search'] as $currentItem) {
					$result[] = intval($currentItem);
					$result = array_unique($result);
				}
			} else {
				$this->piVars['search'] = array();
			}
		}

		return $result;
	}

	/**
	 * Creates the URL to the favorites page. If
	 * $this->getConfValueInteger('favoritesPID') is not set, a link to the
	 * current page will be returned.
	 *
	 * The URL will already be htmlspecialchared.
	 *
	 * @return	string		htmlspecialchared URL of the page set in $this->getConfValueInteger('favoritesPID'), will not be empty
	 *
	 * @access	protected
	 */
	function getFavoritesUrl() {
		// use "clear the variables anyway, don't cache"
		return htmlspecialchars($this->pi_linkTP_keepPIvars_url(
			array(),
			false,
			true,
			$this->getConfValueInteger('favoritesPID')
		));
	}

	/**
	 * Creates the URL of the current page. The URL will contain a flag to
	 * disable caching as this URL also is used for forms with method="post".
	 *
	 * The URL will contain the current piVars.
	 *
	 * The URL will already be htmlspecialchared.
	 *
	 * @return	string		htmlspecialchared URL of the current page, will not be empty
	 *
	 * @access	protected
	 */
	function getSelfUrl() {
		// use "don't clear the variables, don't cache"
		return htmlspecialchars($this->pi_linkTP_keepPIvars_url(
			array(),
			false,
			false
		));
	}

	/**
	 * Creates a result browser for the list view with the current page
	 * highlighted (and not linked). In addition, there will be links to the
	 * previous and the next page.
	 *
	 * This function will return an empty string if there is only 1 page of
	 * results.
	 *
	 * @return	string		HTML code for the page browser (may be empty)
	 *
	 * @access	protected
	 */
	function createPagination() {
		$result = '';

		if ($this->internal['lastPage'] > 0) {
			$links = $this->createPaginationLink(
				max(0, $this->piVars['pointer'] - 1),
				'&lt;',
				false
			);
			$links .= $this->createPageList();
			$links .= $this->createPaginationLink(
				min($this->internal['lastPage'],
				$this->piVars['pointer'] + 1),
				'&gt;',
				false
			);

			$this->setMarkerContent('links_to_result_pages', $links);
			// The subpart PAGINATION appears more than once in the template:
			// The first occurance is used as a the main data source while the
			// other subparts contain design dummies that will be replaced.
			// The behavior of substituteMarkerArrayCached() is to use the first
			// occurance.
			$result = $this->substituteMarkerArrayCached('PAGINATION');
		}

		return $result;
	}

	/**
	 * Creates HTML for a list of links to result pages.
	 *
	 * @return	string		HTML for the pages list (will not be empty)
	 *
	 * @access	protected
	 */
	function createPageList() {
		/** how many links to the left and right we want to have at most */
		$surroundings = round(($this->internal['maxPages'] - 1) / 2);

		$minPage = max(0, $this->piVars['pointer'] - $surroundings);
		$maxPage = min($this->internal['lastPage'], $this->piVars['pointer'] + $surroundings);

		$pageLinks = array();
		for ($i = $minPage; $i <= $maxPage; $i++) {
			$pageLinks[] = $this->createPaginationLink($i, $i + 1);
		}

		return implode(chr(10), $pageLinks);
	}

	/**
	 * Creates a link to the page number $pageNum (starting with 0)
	 * with $linkText as link text. If $pageNum is the current page,
	 * the text is not linked.
	 *
	 * @param	integer		the page number to link to
	 * @param	string		link text (may not be empty)
	 * @param	boolean		whether to output the link text nonetheless if $pageNum is the current page
	 *
	 * @return	string		HTML code of the link (will be empty if $alsoShowNonLinks is false and the $pageNum is the current page)
	 *
	 * @access	protected
	 */
	function createPaginationLink($pageNum, $linkText, $alsoShowNonLinks = true) {
		$result = '';
		$this->setMarkerContent('linktext', $linkText);

		// Don't link to the current page (for usability reasons).
		if ($pageNum == $this->piVars['pointer']) {
			if ($alsoShowNonLinks) {
				$result = $this->substituteMarkerArrayCached('NO_LINK_TO_CURRENT_PAGE');
			}
		} else {
			$url = $this->pi_linkTP_keepPIvars_url(array('pointer' => $pageNum));
			$this->setMarkerContent('url', $url);
			$result = $this->substituteMarkerArrayCached('LINK_TO_OTHER_PAGE');
		}

		return $result;
	}

	/**
	 * Creates the UI for sorting the list view. Depending on the selection of
	 * sort criteria in the BE, the drop-down list will be populated
	 * correspondingly, with the current sort criterion selected.
	 *
	 * In addition, the radio button for the current sort order is selected.
	 *
	 * If there are no search criteria selected in the BE, this function will
	 * return an empty string.
	 *
	 * @return	string		HTML for the WRAPPER_SORTING subpart
	 *
	 * @access	protected
	 */
	function createSorting() {
		$result = '';

		// Only have the sort form if at least one sort criteria is selected in the BE.
		if ($this->hasConfValueInteger('sortCriteria')) {
			$selectedSortCriteria = $this->getConfValueInteger('sortCriteria');
			$options = array();
			foreach ($this->sortCriteria as $sortCriterionKey => $sortCriterionName) {
				if ($selectedSortCriteria & $sortCriterionKey) {
					if ($sortCriterionName == $this->internal['orderBy']) {
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}
					$this->setMarkerContent('sort_value', $sortCriterionName);
					$this->setMarkerContent('sort_selected', $selected);
					$this->setMarkerContent('sort_label', $this->pi_getLL('label_'.$sortCriterionName));
					$options[] = $this->substituteMarkerArrayCached('SORT_OPTION');
				}
			}
			$this->setSubpartContent('sort_option', implode(chr(10), $options));
			if (!$this->internal['descFlag']) {
					$this->setMarkerContent('sort_checked_asc', ' checked="checked"');
					$this->setMarkerContent('sort_checked_desc', '');
			} else {
					$this->setMarkerContent('sort_checked_asc', '');
					$this->setMarkerContent('sort_checked_desc', ' checked="checked"');
			}
			$result = $this->substituteMarkerArrayCached('WRAPPER_SORTING');
		}
		return $result;
	}

	/**
	 * Creates the search checkboxes for the DB field selected in the BE.
	 * If no field is selected in the BE or there are not DB records with
	 * non-empty data for that field, this function returns an empty string.
	 *
	 * This function will also return an empty string if "city" is selected in
	 * the BE and $this->piVars['city'] is set (by the city selector).
	 *
	 * @return	string		HTML for the search bar (may be empty)
	 *
	 * @access	protected
	 */
	function createCheckboxesFilter() {
		$result = '';

		// Only have the sort form if at least one sort criteria is selected in the BE.
		if ($this->hasConfValueString('checkboxesFilter')
			&& !(($this->getConfValueString('checkboxesFilter') == 'city')
				&& isset($this->piVars['city']))) {
			$selectedFilterCriteria = $this->getConfValueString('checkboxesFilter');

			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid, title',
				$this->tableNames[$selectedFilterCriteria],
				'EXISTS '
					.'(SELECT * '
					.'FROM '.$this->tableNames['objects'].' '
					.'WHERE '.$this->tableNames['objects'].'.'.$selectedFilterCriteria
						.'='.$this->tableNames[$selectedFilterCriteria].'.uid)'
			);

			if ($dbResult && $GLOBALS['TYPO3_DB']->sql_num_rows($dbResult)) {
				$items = array();
				// Make sure we have an array to work on.
				if (!isset($this->piVars['search']) || !is_array($this->piVars['search'])) {
					$this->piVars['search'] = array();
				}

				while ($dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
					if (in_array($dbResultRow['uid'], $this->piVars['search'])) {
						$checked = ' checked="checked"';
					} else {
						$checked = '';
					}
					$this->setMarkerContent('search_value', $dbResultRow['uid']);
					$this->setMarkerContent('search_checked', $checked);
					$this->setMarkerContent('search_label', $dbResultRow['title']);
					$items[] = $this->substituteMarkerArrayCached('SEARCH_ITEM');
				}
				$this->setSubpartContent('search_item', implode(chr(10), $items));
				$result = $this->substituteMarkerArrayCached('LIST_FILTER');
			}
		}
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php']);
}

?>
