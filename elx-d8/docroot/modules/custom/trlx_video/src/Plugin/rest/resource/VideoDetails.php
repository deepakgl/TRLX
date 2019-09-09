<?php

namespace Drupal\trlx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a video details page resource.
 *
 * @RestResource(
 *   id = "video_details",
 *   label = @Translation("Video Details"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/videoDetails"
 *   }
 * )
 */
class VideoDetails extends ResourceBase {

  /**
   * Rest resource for video details.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
    $user_utility = new UserUtility();
    // Node id.
    $nid = $request->query->get('nid');
    if (empty($nid)) {
      $param = ['nid'];

      return $common_utility->invalidData($param);
    }
    // Check if node id exists.
    if (empty($common_utility->isValidNid($nid))) {
      return new JsonResponse('Node id does not exist.', 422, [], FALSE);
    }
    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'displayTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    $key = ':videoDetails:' . '_' . $nid;
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key, 'video_details', 'rest_export_video_details',
       $data, $nid);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
