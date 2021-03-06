<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Test class for CRM_Price_BAO_PriceSet.
 * @group headless
 */
class CRM_Price_BAO_PriceSetTest extends CiviUnitTestCase {

  /**
   * Sets up the fixtures.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture.
   */
  protected function tearDown() {
  }

  /**
   * Test the correct amount level is returned for an event which is not presented as a price set event.
   *
   * (these are denoted as 'quickConfig' in the code - but quickConfig is only supposed to refer to the
   * configuration interface - there should be no different post process.
   */
  public function testGetAmountLevelTextAmount() {
    $priceSetID = $this->eventPriceSetCreate(9);
    $priceSet = CRM_Price_BAO_PriceSet::getCachedPriceSetDetail($priceSetID);
    $field = reset($priceSet['fields']);
    $params = array('priceSetId' => $priceSetID, 'price_' . $field['id'] => 1);
    $amountLevel = CRM_Price_BAO_PriceSet::getAmountLevelText($params);
    $this->assertEquals(CRM_Core_DAO::VALUE_SEPARATOR . 'Price Field - 1' . CRM_Core_DAO::VALUE_SEPARATOR, $amountLevel);
  }

  /**
   * Test CRM_Price_BAO_PriceSet::getMembershipCount() that return correct number of
   *   membership type occurances against it's corresponding member orgaisation
   */
  public function testGetMembershipCount() {
    // create two organisations
    $organization1 = $this->organizationCreate();
    $organization2 = $this->organizationCreate();

    // create three membership type where first two belong to same organisation
    $membershipType1 = $this->membershipTypeCreate(array(
      'name' => 'Membership Type 1',
      'member_of_contact_id' => $organization1,
    ));
    $membershipType2 = $this->membershipTypeCreate(array(
      'name' => 'Membership Type 2',
      'member_of_contact_id' => $organization1,
    ));
    $membershipType3 = $this->membershipTypeCreate(array(
      'name' => 'Membership Type 3',
      'member_of_contact_id' => $organization2,
    ));

    $priceDetails = CRM_Price_BAO_PriceSet::getSetDetail(CRM_Core_DAO::getFieldValue(
      'CRM_Price_DAO_PriceSet',
      'default_membership_type_amount',
      'id', 'name'
    ));
    // fetch price field value IDs in array('membership_type_id' => 'price_field_value_id') format
    $priceFieldValueIDs = array();
    foreach ($priceDetails as $priceFields) {
      foreach ($priceFields['fields'] as $priceField) {
        foreach ($priceField['options'] as $id => $priceFieldValue) {
          if (in_array($priceFieldValue['membership_type_id'], array($membershipType1, $membershipType2, $membershipType3))) {
            $priceFieldValueIDs[$priceFieldValue['membership_type_id']] = $id;
          }
        }
      }
    }

    // CASE 1: when two price field value IDs of membership type that belong to same organization, are chosen
    $sameOrgPriceFieldIDs = implode(', ', array(
      $priceFieldValueIDs[$membershipType1],
      $priceFieldValueIDs[$membershipType2],
    ));
    $occurences = CRM_Price_BAO_PriceSet::getMembershipCount($sameOrgPriceFieldIDs);
    // total number of membership type occurences of same organisation is one
    $this->assertEquals(1, count($occurences));
    $this->assertTrue(array_key_exists($organization1, $occurences));
    // assert that two membership types were chosen from same organisation
    $this->assertEquals(2, $occurences[$organization1]);

    // CASE 2: when two price field value IDs of membership type that belong to different organizations, are chosen
    $differentOrgPriceFieldIDs = implode(', ', array(
      $priceFieldValueIDs[$membershipType1],
      $priceFieldValueIDs[$membershipType3],
    ));
    $occurences = CRM_Price_BAO_PriceSet::getMembershipCount($differentOrgPriceFieldIDs);
    // total number of membership type occurences of different organisation is two
    $this->assertEquals(2, count($occurences));
    $this->assertTrue(array_key_exists($organization1, $occurences));
    $this->assertTrue(array_key_exists($organization2, $occurences));
    // assert that two membership types were chosen from different organisation
    $this->assertEquals(1, $occurences[$organization1]);
    $this->assertEquals(1, $occurences[$organization2]);
  }

}
