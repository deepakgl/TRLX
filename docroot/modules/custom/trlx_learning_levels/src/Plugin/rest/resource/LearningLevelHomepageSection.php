<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\UserUtility;
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
    $levelUtility = new LevelUtility();
    $userUtility = new UserUtility();

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

    // Get user.
    global $_userData;
    $all_tids = $this->getDistingTids($_userData->userId);
    $markets = $userUtility->getMarketByUserData($_userData);
    $count1 = 0;
    foreach ($all_tids as $tid) {
      // Query to fetch all associated nids.
      $database = \Drupal::database();
      $query = $database->select('node', 'n');
      $query->condition('n.type', 'level_interactive_content', '=');
      $query->join('node__field_learning_category', 'nflc', 'n.nid = nflc.entity_id');
      $query->condition('nflc.field_learning_category_target_id', $tid, '=');
      $query->join('node__field_markets', 'm', 'n.nid = m.entity_id');
      $query->condition('m.bundle', 'level_interactive_content', '=');
      $query->condition('m.field_markets_target_id', $markets, 'IN');
      $query->fields('n', ['nid']);
      $results = $query->execute()->fetchAllAssoc('nid');
      $nids = [];
      $count = 0;
      foreach ($results as $key => $value) {
        $nids[$count] = $value->nid;
        $count++;
      }

      // Check if nids not empty
      if (!empty($nids)) {
        //Get status in-progress in percentage.
        $status_array = $levelUtility->getLevelActivity($_userData, $tid, $nids, $language);
        if ($status_array['percentageCompleted'] != 100) {
          // Get term by tid.
          $term = $this->getTaxonomyTerm($status_array['categoryId'], $language);
          // $term = $this->getTaxonomyTerm($tid, $language);
          $translation = $this->validateTraslation($nids, $language);
          if ((!empty($term)) && ($translation['status'] == 1)) {
            $result[$count1]['id'] = $term->id();
            $result[$count1]['displayTitle'] = $term->hasTranslation($language) ? $term->getTranslation($language)->get('name')->value : '';
            $result[$count1]['subTitle'] = $term->hasTranslation($language) ? $term->getTranslation($language)->get('field_sub_title')->value : '';
            $result[$count1]['body'] = $term->hasTranslation($language) ? ((!empty($term->getTranslation($language)->get('description')->value) ||
            ($term->getTranslation($language)->get('description')->value != NULL) ? $term->getTranslation($language)->get('description')->value : '')) : '';
            // Get image reference field.
            $featured_image = $term->get('field_image')->referencedEntities();
            if (!empty($featured_image)) {
              $image = array_shift($featured_image)->get('field_media_image')->referencedEntities();
              $uri = (!empty($image)) ? (array_shift($image)->get('uri')->value) : '';
              $result[$count1]['imageSmall'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_mobile', $uri)) : '';
              $result[$count1]['imageMedium'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_tablet', $uri)) : '';
              $result[$count1]['imageLarge'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_desktop', $uri)) : '';
            }
            else {
              $result[$count1]['imageSmall'] = '';
              $result[$count1]['imageMedium'] = '';
              $result[$count1]['imageLarge'] = '';
            }
            // Get point values count.
            $pointValues = $this->getPointValues($nids, $language, $_userData);
            if ((empty($pointValues)) || ($pointValues == NULL)) {
              $result[$count1]['pointValue'] = 0;
            }
            else {
              $result[$count1]['pointValue'] = $pointValues;
            }

            if ($count1 >= 3) {
              break;
            }
            $count1++;
          }
        }
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
    // Exception handling.
    try {
      // Query to get the nid for in-progress learning level content.
      $database = \Drupal::database();
      $query = $database->select('lm_lrs_records', 'n');
      $query->fields('n', ['id', 'tid']);
      $query->condition('uid', $uid, "=");
      $query->orderBy('id', 'DESC');
      $result = $query->execute()->fetchAll();

      $result_array = [];
      foreach ($result as $key => $value) {
        $value = (array) $value;
        // Check for term exist.
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value['tid']);
        if (!empty($term)) {
          $result_array[$key] = $value;
        }
      }

      // Early return.
      if (empty($result_array)) {
        return FALSE;
      }

      $tid_array = array_column($result_array, 'tid');
      $uniqueArray = array_unique($tid_array);

      return $uniqueArray;
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
   * Method to get point value.
   *
   * @param array $nids
   *   node data.
   * @param $langcode
   *   lang data
   *
   * @return int
   *   point value
   */
  public function getPointValues($nids, $langcode, $_userData) {
    // Fetch point values.
    $points_value = 0;
    $has_market = 0;
    $market_regions = array_merge($_userData->region, $_userData->subregion);
    foreach ($nids as $nid) {
      if (!empty($nid)) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        // Validating regions.
        if (!empty($node)) {
          // Fetch reference entities.
          $terms = $node->get('field_markets')->referencedEntities();
          foreach ($terms as $term) {
            // Get single region key.
            $region_key = $term->get('field_region_subreg_country_id')->value;
            foreach ($market_regions as $region) {
              if ($region_key === $region) {
                $has_market = 1;
              }
            }

            if ($has_market == 1) {
              break;
            }
          }
          // Check for translation and market region.
          if ($node->hasTranslation($langcode) && ($has_market == 1)) {
            // Checking publish content.
            if (($node->getTranslation($langcode)->get('status')->value) == 1) {
              $points = $node->get('field_point_value')->value;
              $points_value = $points_value + $points;
            }
          }
        }
      }
      $has_market = 0;
    }

    return $points_value;
  }

  /**
   * Method to validate tranlsation.
   *
   * @param array $nids
   *   node data.
   * @param $langcode
   *   lang data
   *
   * @return int
   *   status
   */
  public function validateTraslation($nids, $langcode) {
    foreach ($nids as $nid) {
      if (!empty($nid)) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        if (($node->hasTranslation($langcode)) && (($node->getTranslation($langcode)->get('status')->value))) {
          return ['status' => 1];
        }
      }
    }

    return ['status' => 0];
  }

}
