<?php

namespace Drupal\elx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a learning levels resource.
 *
 * @RestResource(
 *   id = "learning_levels",
 *   label = @Translation("Learning Levels"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/learningLevels"
 *   }
 * )
 */
class LearningLevels extends ResourceBase {

  /**
   * Fetch learning levels.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    $common_utility = new CommonUtility();
    $level_status = $request->query->get('levelStatus');
    $all_status = ['progress', 'completed', 'all'];
    list($limit, $offset) = $common_utility->getPagerParam($request);
    if (!in_array($level_status, $all_status)) {
      return new JsonResponse('Invalid levelStatus', 400);
    }
    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'subTitle' => 'decode',
      'categoryId' => 'int',
      'pointValue' => 'int',
    ];
    if ($level_status != 'all') {
      $tid = $common_utility->getUserLevelStatus(\Drupal::currentUser()->id(), $level_status);
      if (empty($tid) || is_object($tid)) {
        return new JsonResponse(JSON::encode([]), 204, [], TRUE);
      }
    }
    else {
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('learning_category');
      $tid = array_map(function ($e) {
        return is_object($e) ? $e->tid : $e['tid'];
      }, $terms);
    }
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult(NULL, 'learning_levels', 'rest_export_learning_levels',
     $data, $tid);
    $decode = JSON::decode($view_results, TRUE);
    if (!empty($decode['results'])) {
      $view_results = $this->prepareRow($decode, $offset, $limit);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

  /**
   * Fetch learning levels.
   *
   * @param mixed $decode
   *   View data.
   * @param int $offset
   *   View offset.
   * @param int $limit
   *   View limit.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode, $offset, $limit) {
    $common_utility = new CommonUtility();
    $term_ids = array_column($decode['results'], 'categoryId');
    // Get level intreactive node ids assosiated with level.
    $term_nodes = $common_utility->getTermNodes($term_ids);
    $user_activity = [];
    $tmp = 0;
    foreach ($decode['results'] as $key => $value) {
      if (!isset($term_nodes[$value['categoryId']])) {
        // Remove level from listing in no module belongs to user market and.
        // language.
        unset($value);
        $decode['results'][$key] = $value;
        $tmp++;
      }
      else {
        // Get Level completed percentage.
        $module_details = $common_utility
          ->getLevelModuleDetail(\Drupal::currentUser()->id(),
         $value['categoryId'],
         array_column($term_nodes[$value['categoryId']], 'nid'));
        $decode['results'][$key]['pointValue'] =
         array_sum(array_column($term_nodes[$value['categoryId']],
          'point_value'));
        $user_activity[] = [
          "categoryId" => intval($value['categoryId']),
          "percentage" => intval($module_details[$value['categoryId']]['percentage']),
          "favourites" => -100,
          "bookmarks" => -100,
          "downloadCount" => -100,
          "userFavouriteStatus" => TRUE,
          "userBookmarkStatus" => TRUE,
        ];
      }
    }
    $data = array_values(array_filter($decode['results']));
    $decode['results'] = array_slice($data, $offset, $limit);
    $decode['userActivity'] = array_slice($user_activity, $offset, $limit);
    $decode['pager']['count'] = count($data) - $offset;
    $decode['pager']['pages'] = ceil(count($data) / $limit);
    $decode['pager']['items_per_page'] = $limit;
    $view_results = JSON::encode($decode);

    return $view_results;
  }

}
