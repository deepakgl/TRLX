<?php

namespace Drupal\trlx_learning_levels\Utility;

use Drupal\trlx_utility\Utility\UserUtility;
use Drupal\trlx_utility\Utility\CommonUtility;

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

  /**
   * Fetch level activity information.
   *
   * @param int $_userData
   *   User object.
   * @param int $tid
   *   Term id.
   * @param int $nid
   *   Node id.
   * @param string $lang
   *   Language code.
   *
   * @return json
   *   Level activity.
   */
  public function getLevelActivity($_userData, $tid, $nid, $lang) {
    $commonUtility = new CommonUtility();
    $uid = $_userData->userId;
    $query = \Drupal::database();
    $query = $query->select('lm_lrs_records', 'records');
    $query->fields('records', ['statement_status', 'nid']);
    $query->condition('records.uid', $uid);
    if (!empty($tid)) {
      $query->condition('records.tid', $tid);
    }
    $query->condition('records.nid', $nid, 'IN');
    $results = $query->execute()->fetchAll();
    foreach ($results as $key => $result) {
      $data[$result->nid] = $result;
    }
    $incomplete_status = ['progress', NULL];
    foreach ($nid as $key => $value) {
      $status = (isset($data[$value]) && !in_array($data[$value]->statement_status, $incomplete_status)) ? (int) 1 : (int) 0;
      $response[$value] = [
        "nid" => (int) $value,
        "status" => $status,
      ];
    }
    $total_count = count($nid);
    $completed = 0;
    foreach ($response as $key => $module_detail) {
      if ($module_detail['status'] == 1) {
        $completed = $completed + 1;
      }
    }
    $percentage = ceil($completed / $total_count * 100);
    $category_name = $commonUtility->getTermName($tid);
    $like_count = $commonUtility->likeCount($nid);
    $arr = [
      'name' => $category_name,
      'categoryId' => (int) $tid,
      'percentageCompleted' => $percentage,
      'completedCount' => $completed,
      'totalCount' => $total_count,
      'likeCount' => $like_count,
    ];
    return $arr;
  }

  /**
   * Fetch level data by tid.
   *
   * @param int $tid
   *   Term Id.
   *
   * @return array
   *   Level data.
   */
  public function getLevelData($tid) {
    $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
    $query->fields('ttfd', ['name', 'langcode']);
    $query->condition('ttfd.tid', $tid);
    $results = $query->execute()->fetchAll();
    return $results[0];
  }

  /**
   * Fetch brands associated with particular level.
   *
   * @param int $tid
   *   Term Id.
   *
   * @return array
   *   Brands.
   */
  public function getLevelBrands($tid) {
    $query = \Drupal::database()->select('taxonomy_term__field_brands', 'ttfb');
    $query->fields('ttfb', ['field_brands_target_id']);
    $query->join('taxonomy_term__field_brand_key', 'ttfbk', 'ttfbk.entity_id = ttfb.field_brands_target_id');
    $query->fields('ttfbk', ['field_brand_key_value']);
    $query->condition('ttfb.entity_id', $tid);
    return $query->execute()->fetchAll();
  }

  /**
   * Fetch content sections associated with particular level.
   *
   * @param int $tid
   *   Term Id.
   *
   * @return array
   *   Brands.
   */
  public function getLevelContentSection($tid) {
    $query = \Drupal::database()->select('taxonomy_term__field_content_section', 'ttfcs');
    $query->fields('ttfcs', ['field_content_section_target_id']);
    $query->join('taxonomy_term__field_content_section_key', 'ttfcsk', 'ttfcsk.entity_id = ttfcs.field_content_section_target_id');
    $query->fields('ttfcsk', ['field_content_section_key_value']);
    $query->condition('ttfcs.entity_id', $tid);
    return $query->execute()->fetchAll();
  }

}
