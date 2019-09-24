<?php

namespace Drupal\elx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\elx_entityqueue_alter\Utility\EntityQueueUtility;

/**
 * Provides a explore learning levels resource.
 *
 * @RestResource(
 *   id = "explore_learning_levels",
 *   label = @Translation("Explore Learning Levels"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/exploreLearningLevels"
 *   }
 * )
 */
class ExploreLearningLevels extends ResourceBase {

  /**
   * Rest resource for explore learning levels.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get() {
    $entity_utility = new EntityUtility();
    $queue_utility = new EntityQueueUtility();
    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'subTitle' => 'decode',
      'categoryId' => 'int',
      'pointValue' => 'int',
    ];
    $bypass_market_queue = $queue_utility
    ->fetchQueueOverrideFlagStatus('explore_learning_levels');
    $decode = [];
    if (!$bypass_market_queue) {
      list($view_results, $status_code) = $entity_utility->fetchApiResult(NULL,
       'learning_levels', 'rest_export_explore_learning_levels_market_wise',
        $data);
       $decode = JSON::decode($view_results, TRUE);
    }
    if (empty(array_filter($decode))) {
      list($view_results, $status_code) = $entity_utility->fetchApiResult(NULL,
       'learning_levels', 'rest_export_explore_learning_levels', $data);
    }
    $decode = JSON::decode($view_results, TRUE);
    if (!empty($decode)) {
      $view_results = $this->prepareRow($decode);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

  /**
   * Fetch explore learning levels.
   *
   * @param mixed $decode
   *   View data.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode) {
    $common_utility = new CommonUtility();
    $term_ids = array_column($decode, 'categoryId');
    // Fetch level intreactive node ids assosiated with level.
    $term_nodes = $common_utility->getTermNodes($term_ids);
    foreach ($decode as $key => $value) {
      // Get Level completed percentage.
      $module_details = $common_utility->getLevelModuleDetail(\Drupal::currentUser()->id(), $value['categoryId'],
      array_column($term_nodes[$value['categoryId']], 'nid'));
      $decode[$key]['pointValue'] =
      intval(array_sum(array_column($term_nodes[$value['categoryId']],
      'point_value')));
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
    $decode = ['results' => $decode];
    $decode['userActivity'] = $user_activity;
    $view_results = JSON::encode($decode);

    return $view_results;
  }

}
