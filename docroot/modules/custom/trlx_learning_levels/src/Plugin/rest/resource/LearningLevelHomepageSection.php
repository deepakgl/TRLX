<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_learning_levels\Utility\LevelUtility;

/**
 * Provides a learning levels homepage section.
 *
 * @RestResource(
 *   id = "learning_levels_homepage_section",
 *   label = @Translation("Learning Level Homepage Section"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/learningLevelSection"
 *   }
 * )
 */
class LearningLevelHomepageSection extends ResourceBase {

  /**
   * Fetch Learning Level Section.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Learning Level Section.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
      'language',
    ];
    // Check for required parameters.
    $missingParams = [];
    foreach ($requiredParams as $param) {
      $$param = $request->query->get($param);
      if (empty($$param)) {
        $missingParams[] = $param;
      }
    }
    // Report missing required parameters.
    if (!empty($missingParams)) {
      return $commonUtility->invalidData($missingParams);
    }
    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    // Checkfor valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    global $_userData;
    $all_tids = $this->getDistingTids($_userData->userId);
    $progress = [];
    $count = 0;
    if (!empty($all_tids)) {
      foreach ($all_tids as $key => $tid) {
        if (!empty($tid)) {
          $all_nids = $this->getAllNids($tid, $language);
          if (!empty($all_nids)) {
            $pointValue = $this->getPointValues($all_nids, $language);
            $level_status = $this->getLevelStatus($all_nids, $tid);
            $progress[$count]['pointValue'] = $pointValue;
            if ($level_status['status'] == 'inprogress') {
              $progress[$count]['tid'] = $level_status['term_id'];
              $count++;
              if ($count >= 4) {
                break;
              }
            }
          }
        }
      }
    }

    $result = [];
    $count1 = 0;
    foreach ($progress as $tid) {
      $term = $this->getTaxonomyTerm($tid['tid'], $language);
      if (!empty($term)) {
        $result[$count1]['id'] = $term->id();
        $result[$count1]['displayTitle'] = $term->hasTranslation($language) ? $term->getTranslation($language)->get('name')->value : '';
        $result[$count1]['subTitle'] = $term->hasTranslation($language) ? $term->getTranslation($language)->get('field_sub_title')->value : '';
        $result[$count1]['body'] = $term->hasTranslation($language) ? $term->getTranslation($language)->get('description')->value : '';
        $featured_image = $term->get('field_image')->referencedEntities();
        if (!empty($featured_image)) {
          $image = array_shift($featured_image)->get(field_media_image)->referencedEntities();
          $uri = (!empty($image)) ? (array_shift($image)->get(uri)->value) : '';
          $result[$count1]['imageSmall'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_mobile', $uri)) : '';
          $result[$count1]['imageMedium'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_tablet', $uri)) : '';
          $result[$count1]['imageLarge'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_desktop', $uri)) : '';
        }
        else {
          $result[$count1]['imageSmall'] = '';
          $result[$count1]['imageMedium'] = '';
          $result[$count1]['imageLarge'] = '';
        }

        if (empty($tid['pointValue']) || ($tid['pointValue'] == null)) {
          $result[$count1]['pointValue'] = 0;
        } else {
          $result[$count1]['pointValue'] = $tid['pointValue'];
        }
        $count1++;
      }
    }

    $response = [];
    $response['results'] = $result;
    if (empty($response['results'])) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($response['results'], 200);
  }

  /**
   * Method to get tid data.
   *
   * @return array
   *   tid data.
   */
  public function getDistingTids($uid) {
    // Exception handling
    try {
      // Query to get the nid for in-progress learning level content.
      $database = \Drupal::database();

      $nid_query = $database->select('lm_lrs_records', 'n');
      $nid_query->fields('n', array('nid'));
      $nid_query->condition('uid', $uid, "=");
      $nid_query->condition('statement_status', 'passed', "=");
      $result_nid = $nid_query->execute()->fetchAll();

      $query = $database->select('lm_lrs_records', 'n');
      $query->fields('n', array('id','tid'));
      $query->condition('uid', $uid, "=");
      $query->condition('statement_status', 'passed', "!=");
      if (!empty($result_nid)) {
        foreach ($result_nid as $nid) {
          $query->condition('nid', $nid->nid, "!=");
        }
      }
      $query->orderBy('id', 'DESC');
      $result = $query->execute()->fetchAll();

      $result_array = [];
      foreach ($result as $key => $value) {
        $value = (array) $value;
        $result_array[$key] = $value;
      }

      // Early return
      if (empty($result_array)) {
        return FALSE;
      }

      $tid_array = array_column($result_array , 'tid');
      $uniqueArray = array_unique($tid_array);

      return  $uniqueArray;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Method to get node data.
   *
   * @param int $tid
   *   Term data.
   *
   * @return array
   *   tid data.
   */
  public function getAllNids($tid, $langcode) {
    // Check term translation
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
    if (!$term->hasTranslation($langcode)) {
      return FALSE;
    }

    $tid = (int) $tid;
    $result = '';
    $response = [];

    try {
      // Query to get the nid for in-progress learning level content.
      $query = \Drupal::database()->select('node__field_learning_category', 'n');
      $query->fields('n', ['entity_id']);
      $query->condition('n.bundle', 'level_interactive_content');
      $query->condition('n.field_learning_category_target_id', $tid);
      $result = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      return FALSE;
    }

    $count = 0;
    if (!empty($result)) {
      foreach ($result as $nids) {
        $nid = $nids->entity_id;
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        if ($node->hasTranslation($langcode)) {
          $response[] = $nids;
          $count++;
        }
      }
    }

    if (!empty($response)) {
      return $response;
    } else {
      return FALSE;
    }
  }

  /**
   * Method to get status data.
   *
   * @param array $nids
   *   nid data.
   * @param int $tid
   *   Term data.
   *
   * @return array
   *   status data.
   */
  public function getLevelStatus($nids, $tid) {
    // Query to get the nid for in-progress learning level content.
    try {
      $database = \Drupal::database();
      foreach ($nids as $nid) {
        $nid->entity_id;
        $query = $database->query("select tid from lm_lrs_records where statement_status= 'passed' and 'nid' = " . $nid->entity_id);
        $result = $query->fetchAll();
        if (empty($result)) {
          return ['status' => 'inprogress', 'term_id' => $tid];
          break;
        }
      }
      return ['status' => 'pass', 'term_id' => $tid];
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Method to get status data.
   *
   * @param int $tid
   *   Term data.
   * @param $langcode
   *   lang data
   *
   * @return array
   *   status data.
   */
  public function getTaxonomyTerm($tid, $langcode) {
    try {
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
      if ($term->hasTranslation($langcode)) {
        return $term->getTranslation($langcode);
      }
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }


 /**
  * Method to get point value
  *
  * @param array $nids
  *   node data
  * @param $langcode
  *   lang data
  *
  * @return integer
  *   point value
  */
  public function getPointValues($nids, $langcode) {
    // Fetch point values
    $points_value = 0;
    foreach ($nids as $nid) {
      if (!empty($nid->entity_id)) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid->entity_id);
        if ($node->hasTranslation($langcode)) {
          $points = $node->get('field_point_value')->value;
          $points_value = $points_value + $points;
        }
      }
    }

    return $points_value;
  }
}
