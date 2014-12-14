<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_DefaultListViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_DefaultListView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var int UID of the first dummy realty object
	 */
	private $firstRealtyUid = 0;

	/**
	 * @var int second dummy realty object
	 */
	private $secondRealtyUid = 0;

	/**
	 * @var int first dummy city UID
	 */
	private $firstCityUid = 0;
	/**
	 * @var string title for the first dummy city
	 */
	private static $firstCityTitle = 'Bonn';

	/**
	 * @var int second dummy city UID
	 */
	private $secondCityUid = 0;
	/**
	 * @var string title for the second dummy city
	 */
	private static $secondCityTitle = 'bar city';

	/**
	 * @var int system folder PID
	 */
	private $systemFolderPid = 0;

	protected function setUp() {
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$this->systemFolderPid = $this->testingFramework->createSystemFolder(1);

		$this->createDummyObjects();

		$this->fixture = new tx_realty_pi1_DefaultListView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'pages' => $this->systemFolderPid,
				'showGoogleMaps' => 0,
			),
			$GLOBALS['TSFE']->cObj,
			TRUE
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////


	/**
	 * Creates dummy realty objects in the DB.
	 *
	 * @return void
	 */
	private function createDummyObjects() {
		$this->createDummyCities();
		$this->firstRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'pid' => $this->systemFolderPid,
				'city' => $this->firstCityUid,
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
			)
		);
		$this->secondRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'pid' => $this->systemFolderPid,
				'city' => $this->secondCityUid,
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
			)
		);
	}

	/**
	 * Creates dummy city records in the DB.
	 *
	 * @return void
	 */
	private function createDummyCities() {
		$this->firstCityUid = $this->testingFramework->createRecord(
			'tx_realty_cities',
			array('title' => self::$firstCityTitle)
		);
		$this->secondCityUid = $this->testingFramework->createRecord(
			'tx_realty_cities',
			array('title' => self::$secondCityTitle)
		);
	}


	//////////////////////////////////////////
	// Tests for the list filter checkboxes.
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function listFilterIsVisibleIfCheckboxesFilterSetToDistrictAndCitySelectorIsInactive() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('district' => $this->testingFramework->createRecord(
				'tx_realty_districts', array('title' => 'test district')
			))
		);
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function checkboxesFilterDoesNotHaveUnreplacedMarkersForMinimalContent() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				// A city is the minimum requirement for an object to be displayed,
				// though the object is rendered empty because the city has no title.
				'city' => $this->testingFramework->createRecord('tx_realty_cities'),
				'pid' => $systemFolder
			)
		);

		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$this->fixture->setConfigurationValue('pages', $systemFolder);

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render()
		);
		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listFilterIsVisibleIfCheckboxesFilterIsSetToDistrictAndCitySelectorIsActive() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('district' => $this->testingFramework->createRecord(
				'tx_realty_districts', array('title' => 'test district')
			))
		);
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render(array('city' => $this->firstCityUid))
		);
	}

	/**
	 * @test
	 */
	public function listFilterIsInvisibleIfCheckboxesFilterSetToDistrictAndNoRecordIsLinkedToADistrict() {
		$this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'test district')
		);
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');

		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listFilterIsInvisibleIfCheckboxesFilterSetToDistrictAndNoDistrictsExists() {
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');

		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listFilterIsVisibleIfCheckboxesFilterSetToCityAndCitySelectorIsInactive() {
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listFilterIsInvisibleIfCheckboxesFilterIsSetToCityAndCitySelectorIsActive() {
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render(array('city' => $this->firstCityUid))
		);
	}

	/**
	 * @test
	 */
	public function listFilterIsInvisibleIfCheckboxesFilterNotSet() {
		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listFilterDoesNotDisplayUnlinkedCity() {
		$this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'unlinked city')
		);
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$output = $this->fixture->render();

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$output
		);
		$this->assertNotContains(
			'unlinked city',
			$output
		);
	}

	/**
	 * @test
	 */
	public function listFilterDoesNotDisplayDeletedCity() {
		$deletedCityUid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'deleted city', 'deleted' => 1)
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->firstRealtyUid, array('city' => $deletedCityUid)
		);
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$output = $this->fixture->render();

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$output
		);
		$this->assertNotContains(
			'deleted city',
			$output
		);
	}

	/**
	 * @test
	 */
	public function listIsFilteredForOneCriterion() {
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$piVars = array('search' => array($this->firstCityUid));

		// The city's title will occur twice if it is within the list view and
		// within the list filter. It will occur once if it is only a filter
		// criterion.
		// piVars would usually be set by each submit of the list filter.
		$output = $this->fixture->render($piVars);

		$this->assertEquals(
			2,
			substr_count($output, self::$firstCityTitle)
		);
		$this->assertEquals(
			1,
			substr_count($output, self::$secondCityTitle)
		);
	}

	/**
	 * @test
	 */
	public function listIsFilteredForTwoCriteria() {
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$piVars = array('search' => array(
			$this->firstCityUid, $this->secondCityUid
		));

		// The city's title will occur twice if it is within the list view and
		// within the list filter. It will occur once if it is only a filter
		// criterion.
		// piVars would usually be set by each submit of the list filter.
		$output = $this->fixture->render($piVars);

		$this->assertEquals(
			2,
			substr_count($output, self::$firstCityTitle)
		);
		$this->assertEquals(
			2,
			substr_count($output, self::$secondCityTitle)
		);
	}

	/**
	 * @test
	 */
	public function listFilterLinksToTheSelfUrl() {
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertContains(
			'?id=' . $GLOBALS['TSFE']->id,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listFiltersLinkDoesNotContainSearchPiVars() {
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertNotContains(
			'tx_realty_pi1[search][0]=' . $this->firstCityUid,
			$this->fixture->render(array('search' => array($this->firstCityUid)))
		);
	}

	/**
	 * @test
	 */
	public function listFilterKeepsAlreadySetPiVars() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertContains(
			'tx_realty_pi1%5Bowner%5D=25',
			$this->fixture->render(array('owner' => 25))
		);
	}
}