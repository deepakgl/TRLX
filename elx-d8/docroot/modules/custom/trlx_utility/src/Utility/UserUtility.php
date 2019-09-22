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

}
