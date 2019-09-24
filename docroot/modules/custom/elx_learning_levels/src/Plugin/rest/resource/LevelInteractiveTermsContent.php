<?php

namespace Drupal\elx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a module listing resource.
 *
 * @RestResource(
 *   id = "level_interactive_terms_content",
 *   label = @Translation("Level Interactive Terms Content"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/levelInteractiveTermsContent"
 *   }
 * )
 */
class LevelInteractiveTermsContent extends ResourceBase {

  /**
   * Rest resource for module listing.
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
    $user_utility = new UserUtility();
    $tid = (int) $request->query->get('categoryId');
    if (empty($tid)) {
      $param = ['categoryId'];

      return $common_utility->invalidData($param);
    }
    // Check if term id exists.
    if (empty($common_utility->isValidTid($tid)) && !empty($tid)) {
      return new JsonResponse('Term id does not exist.', 422, [], FALSE);
    }
    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult(NULL, 'level_interactive_content',
    'rest_export_level_interactive_content', $data, $tid);
    $decode = JSON::decode($view_results, TRUE);

    if (!empty($decode['results'])) {
      $view_results = $this->prepareRow($decode, $tid);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

  /**
   * Fetch result form module listing.
   *
   * @param mixed $decode
   *   View data.
   * @param int $tid
   *   Term id.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode, int $tid) {
    $common_utility = new CommonUtility();
    $learning_level = isset($tid) ? $common_utility->getTermName($tid) :
    '';
    $nid = array_column($decode['results'], 'nid');
    $module_details = $common_utility
      ->getLevelModuleDetail(\Drupal::currentUser()->id(), $tid, $nid);
    $decode['levelDetail']['name'] = $learning_level;
    $decode['levelDetail']['completedCount'] =
    $module_details[$tid]['completedCount'];
    $decode['levelDetail']['totalCount'] = $module_details[$tid]['totalCount'];
    $percentage = $module_details[$tid]['percentage'];
    $term_nodes = $common_utility->getTermNodes([$tid]);
    $decode['levelDetail']['totalPoints'] = array_sum(array_column($term_nodes[$tid],
     'point_value'));
    $decode['levelDetail']['percentageCompleted'] = $percentage;
    $decode['levelDetail']['userLevelActivity'] = [
      "categoryId" => $tid,
      "favourites" => 100,
      "bookmarks" => 100,
      "downloadCount" => 100,
      "userFavouriteStatus" => TRUE,
      "userBookmarkStatus" => TRUE,
    ];
    $view_results = JSON::encode($decode);

    return $view_results;
  }

}
