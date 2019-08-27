<?php

namespace Drupal\elx_learning_levels\Utility;

use Drupal\elx_user\Utility\UserUtility;

/**
 * Purpose of this class is to build learning levels object.
 */
class LevelUtility {

  /**
   * Fetch previous and next level.
   *
   * @param int $uid
   *   User uid.
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
  public function fetchPreviousAndNextLevel($uid, $lang, $learning_category, $nid) {
    $user_utility = new UserUtility();
    // Get current user markets.
    $market = $user_utility->getMarketByUserId($uid);
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
      ->condition('nfm.field_markets_target_id', $market)
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

}
