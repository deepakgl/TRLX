<?php

namespace Drupal\trlx_utility\Utility;

/**
 * Purpose is to write useful user associated functions.
 */
class UserUtility {

  /**
   * Fetch user market by user region, subregion or country reference id.
   *
   * @param int $region_subregion_country_ids
   *   Region, subregion or country reference ids.
   *
   * @return array
   *   User market.
   */
  public function getMarketByReferenceId($region_subregion_country_ids) {
    $query = db_select('taxonomy_term__field_region_subreg_country_id', 'tfrsc');
    $query->fields('tfrsc', ['entity_id', 'field_region_subreg_country_id_value']);
    $query->condition('tfrsc.field_region_subreg_country_id_value', $region_subregion_country_ids, 'IN');
    // Fetch user markets.
    return $query->execute()->fetchAll();
  }

  /**
   * Fetch user market by user object.
   *
   * @param mixed $userData
   *   User object.
   *
   * @return array
   *   User market.
   */
  public function getMarketByUserData($userData) {
    $regions = $userData->region;
    $subregions = $userData->subregion;
    $country = $userData->country;
    // Get region, subregion or country from token if array is not empty.
    if (!empty($country)) {
      $ref_keys = $country;
    }
    elseif (!empty($subregions)) {
      $ref_keys = $subregions;
    }
    elseif (!empty($regions)) {
      $ref_keys = $regions;
    }
    // Get current user markets.
    $markets = array_column(self::getMarketByReferenceId($ref_keys), 'entity_id');

    return $markets;
  }

  /**
   * Fetch user market key by category id.
   *
   * @param int $market_ids
   *   Market ids.
   *
   * @return array
   *   User market keys.
   */
  public function getMarketKeyByCategoryId($market_ids) {
    $query = db_select('taxonomy_term__field_region_subreg_country_id', 'tfrsc');
    $query->fields('tfrsc', ['entity_id', 'field_region_subreg_country_id_value']);
    $query->condition('tfrsc.entity_id', $market_ids, 'IN');
    // Fetch user markets.
    return $query->execute()->fetchAll();
  }

  /**
   * Fetch user brand key for current user.
   *
   * @return array
   *   User brand keys
   */
  public function getUserBrandIds() {
    global $_userData;

    return $_userData->brands;
  }

}
