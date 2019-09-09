<?php

namespace Drupal\trlx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\trlx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;

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
    $entity_utility = new EntityUtility();
    $user_utility = new UserUtility();
    $this->commonUtility = new CommonUtility();
    // Validate language code.
    $langcode = $request->query->get('language');
    $response = $this->commonUtility->validateLanguageCode($langcode, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    // Node id.
    $nid = $request->query->get('nid');
    if (empty($nid)) {
      $param = ['nid'];

      return $this->commonUtility->invalidData($param);
    }
    // Check if node id exists.
    if (empty($this->commonUtility->isValidNid($nid))) {
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

    $response = JSON::decode($view_results, TRUE);
    return new JsonResponse(['success' => TRUE, 'result' => $response, 'code' => 200], 200);
  }

}
