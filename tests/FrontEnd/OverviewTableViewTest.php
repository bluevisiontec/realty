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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_OverviewTableViewTest extends Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_OverviewTableView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        /** @var TypoScriptFrontendController $frontEndController */
        $frontEndController = $GLOBALS['TSFE'];
        $this->fixture = new tx_realty_pi1_OverviewTableView(
            array(
                'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
                'fieldsInSingleViewTable', '',
            ),
            $frontEndController->cObj
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    ////////////////////////////////////
    // Testing the overview table view
    ////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsNonEmptyResultForShowUidOfExistingRecord()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('object_number' => '12345'));

        self::assertNotEquals(
            '',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('object_number' => '12345'));

        $result = $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertNotEquals(
            '',
            $result
        );
        self::assertNotContains(
            '###',
            $result
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheRealtyObjectsObjectNumberForValidRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('object_number' => '12345'));

        self::assertContains(
            '12345',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheRealtyObjectsTitleHtmlspecialcharedForValidRealtyObject()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('object_number' => '12345</br>'));

        self::assertContains(
            htmlspecialchars('12345</br>'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForValidRealtyObjectWithoutData()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array());

        self::assertEquals(
            '',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    ///////////////////////////////////////////////
    // Testing the contents of the overview table
    ///////////////////////////////////////////////

    /**
     * @test
     */
    public function renderReturnsHasAirConditioningRowForTrue()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('has_air_conditioning' => 1));

        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable', 'has_air_conditioning'
        );

        self::assertContains(
            $this->fixture->translate('label_has_air_conditioning'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsHasAirConditioningRowForFalse()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('has_air_conditioning' => 0));

        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable', 'has_air_conditioning'
        );

        self::assertNotContains(
            $this->fixture->translate('label_has_air_conditioning'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsHasPoolRowForTrue()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('has_pool' => 1));

        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable', 'has_pool'
        );

        self::assertContains(
            $this->fixture->translate('label_has_pool'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsHasPoolRowForFalse()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('has_pool' => 0));

        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable', 'has_pool'
        );

        self::assertNotContains(
            $this->fixture->translate('label_has_pool'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsHasCommunityPoolRowForTrue()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('has_community_pool' => 1));

        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable', 'has_community_pool'
        );

        self::assertContains(
            $this->fixture->translate('label_has_community_pool'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsHasCommunityPoolRowForFalse()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('has_community_pool' => 0));

        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable', 'has_community_pool'
        );

        self::assertNotContains(
            $this->fixture->translate('label_has_community_pool'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheLabelForStateIfAValidStateIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('state' => 8));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertContains(
            $this->fixture->translate('label_state'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheStateIfAValidStateIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('state' => 8));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertContains(
            $this->fixture->translate('label_state_8'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsTheLabelForStateIfNoStateIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('state' => 0));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertNotContains(
            $this->fixture->translate('label_state'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsTheLabelForStateIfTheStateIsInvalid()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('state' => 10000000));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

        self::assertNotContains(
            $this->fixture->translate('label_state'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheLabelForHeatingTypeIfOneValidHeatingTypeIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('heating_type' => '1'));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertContains(
            $this->fixture->translate('label_heating_type'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsTheHeatingTypeIfOneValidHeatingTypeIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('heating_type' => '1'));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertContains(
            $this->fixture->translate('label_heating_type_1'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderReturnsAHeatingTypeListIfMultipleValidHeatingTypesAreSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('heating_type' => '1,3,4'));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertContains(
            $this->fixture->translate('label_heating_type_1') . ', ' .
                $this->fixture->translate('label_heating_type_3') . ', ' .
                $this->fixture->translate('label_heating_type_4'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderNotReturnsTheHeatingTypeLabelIfOnlyAnInvalidHeatingTypeIsSet()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array('heating_type' => '100'));

        $this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

        self::assertNotContains(
            $this->fixture->translate('label_heating_type'),
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    ///////////////////////////////////
    // Tests concerning getFieldNames
    ///////////////////////////////////

    /**
     * @test
     */
    public function getFieldNamesByDefaultReturnsAllFieldsNamesFromConfiguration()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(array());
        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,extra_charges,deposit,' .
                'provision,buying_price,hoa_fee,year_rent,' .
                'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        self::assertEquals(
            array(
                'heating_type', 'rent_excluding_bills', 'extra_charges',
                'deposit', 'provision', 'buying_price', 'hoa_fee', 'year_rent',
                'rent_per_square_meter', 'garage_rent', 'garage_price', 'pets'
            ),
            $this->fixture->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForVacantObjectReturnsAllFieldsNamesFromConfiguration()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(
                array('status' => tx_realty_Model_RealtyObject::STATUS_VACANT)
            );
        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,extra_charges,deposit,' .
                'provision,buying_price,hoa_fee,year_rent,' .
                'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            array(
                'heating_type', 'rent_excluding_bills', 'extra_charges',
                'deposit', 'provision', 'buying_price', 'hoa_fee', 'year_rent',
                'rent_per_square_meter', 'garage_rent', 'garage_price', 'pets'
            ),
            $this->fixture->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForReservedObjectReturnsAllFieldsNamesFromConfiguration()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(
                array('status' => tx_realty_Model_RealtyObject::STATUS_RESERVED)
            );
        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,extra_charges,deposit,' .
                'provision,buying_price,hoa_fee,year_rent,' .
                'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            array(
                'heating_type', 'rent_excluding_bills', 'extra_charges',
                'deposit', 'provision', 'buying_price', 'hoa_fee', 'year_rent',
                'rent_per_square_meter', 'garage_rent', 'garage_price', 'pets'
            ),
            $this->fixture->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForSoldObjectDropsPriceRelatedFields()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(
                array('status' => tx_realty_Model_RealtyObject::STATUS_SOLD)
            );
        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,extra_charges,deposit,' .
                'provision,buying_price,hoa_fee,year_rent,' .
                'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            array(
                'heating_type', 'pets'
            ),
            $this->fixture->getFieldNames($realtyObject->getUid())
        );
    }

    /**
     * @test
     */
    public function getFieldNamesWithPriceOnlyIfAvailableForRentedObjectDropsPriceRelatedFields()
    {
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
            ->getLoadedTestingModel(
                array('status' => tx_realty_Model_RealtyObject::STATUS_RENTED)
            );
        $this->fixture->setConfigurationValue(
            'fieldsInSingleViewTable',
            'heating_type,rent_excluding_bills,extra_charges,deposit,' .
                'provision,buying_price,hoa_fee,year_rent,' .
                'rent_per_square_meter,garage_rent,garage_price,pets'
        );

        $this->fixture->setConfigurationValue('priceOnlyIfAvailable', true);

        self::assertEquals(
            array(
                'heating_type', 'pets'
            ),
            $this->fixture->getFieldNames($realtyObject->getUid())
        );
    }
}
