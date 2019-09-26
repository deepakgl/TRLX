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
    $user_utility = new UserUtility();
    // Get current user markets.
    $markets = $user_utility->getMarketByUserData($_userData);
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

  /**
   * Fetch previous and next level.
   *
   * @param int $_userData
   *   User object.
   * @param string $lang
   *   User language.
   * @param int $learning_category
   *   Level category.
   * @param int $nid
   *   Node id.
   *
   * @return array
   *   Level previous and next values.
   */
  public function fetchPreviousAndNextLevel($_userData, $lang, $learning_category, $nid) {
    $uid = $_userData->userId;
    $user_utility = new UserUtility();
    // Get current user markets.
    $markets = $user_utility->getMarketByUserData($_userData);

    // Get nid for previous and next interactive content.
    $query = \Drupal::database();
    $query = $query->select('node_field_data', 'n');
    $query->join('taxonomy_index', 'ti', 'n.nid = ti.nid');
    $query->leftjoin('draggableviews_structure', 'ds', 'n.nid = ds.entity_id');
    $query->join('node__field_markets', 'nfm', 'n.nid = nfm.entity_id');
    $query->fields('n', ['nid'])
      ->condition('ti.tid', $learning_category)
      ->condition('n.status', '1')
      ->condition('n.type', 'level_interactive_content')
      ->condition('n.langcode', $lang)
      ->condition('nfm.field_markets_target_id', $markets, IN)
      ->orderBy('ds.weight', 'ASC');
    $result = $query->execute()->fetchAll();
    foreach ($result as $key => $value) {
      $arr[] = $value->nid;
    }
    $arr = array_values(array_unique($arr));
    $interactive_content = array_search($nid, $arr);
    $previous = isset($arr[$interactive_content - 1]) ? intval($arr[$interactive_content - 1]) : "";
    $next = isset($arr[$interactive_content + 1]) ? intval($arr[$interactive_content + 1]) : "";

    return [$previous, $next];
  }

}
