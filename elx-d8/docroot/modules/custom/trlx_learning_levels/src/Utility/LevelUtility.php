<?php

namespace Drupal\trlx_learning_levels\Utility;

use Drupal\trlx_utility\Utility\UserUtility;

/**
 * Purpose of this class is to build learning levels object.
 */
class LevelUtility {

  /**
   * Fetch interactive learning level category.
   *
   * @param int $nid
   *   Node id.
   *
   * @return int
   *   Learning level category.
   */
  public function getLevelCategory($nid) {
    try {
      $query = db_select('node__field_learning_category', 'lc')
        ->fields('lc', ['field_learning_category_target_id'])
        ->condition('lc.entity_id', $nid, '=')
        ->execute()->fetchAssoc();

      return $query['field_learning_category_target_id'];
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  /**
   * Get term nodes by tid.
   *
   * @param int $tids
   *   Term id.
   * @param mixed $_userData
   *   User information.
   * @param int $language
   *   Language code.
   *
   * @return string
   *   Return array of learning levels.
   */
  public function getTermNodes($tids, $_userData, $language) {
    $regions = $_userData->region;
    $subregions = $_userData->subregion;
    $country = $_userData->country;
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
    $user_utility = new UserUtility();
    // Get current user markets.
    $markets = array_column($user_utility->getMarketByReferenceId($ref_keys), 'entity_id');
    $query = \Drupal::database();
    $query = $query->select('node_field_data', 'n');
    $query->join('node__field_learning_category', 'nflc', 'n.nid = nflc.entity_id');
    $query->join('node__field_point_value', 'nfpv', 'n.nid = nfpv.entity_id');
    $query->distinct('nid');
    $query->fields('n', ['nid']);
    $query->fields('nflc', ['field_learning_category_target_id']);
    $query->fields('nfpv', ['field_point_value_value']);
    $query->condition('nflc.field_learning_category_target_id', $tids, 'IN');
    $query->condition('n.status', '1');
    $query->condition('n.langcode', $language);
    $query->condition('n.type', 'level_interactive_content');
    $query->condition('nflc.langcode', $language);
    $query->join('node__field_markets', 'nfm', 'n.nid = nfm.entity_id');
    $query->condition('nfm.field_markets_target_id', $markets, 'IN');
    $result = $query->execute()->fetchAll();
    foreach ($result as $key => $value) {
      $arr[$value->field_learning_category_target_id][] = [
        'nid' => $value->nid,
        'point_value' => $value->field_point_value_value,
      ];
    }

    return $arr;
  }

}
