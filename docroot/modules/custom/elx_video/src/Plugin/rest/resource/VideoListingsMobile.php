<?php

namespace Drupal\elx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a video listings mobile resource.
 *
 * @RestResource(
 *   id = "video_listing_Mobile",
 *   label = @Translation("Video Listing Mobile"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/videoListingMobile"
 *   }
 * )
 */
class VideoListingsMobile extends ResourceBase {

  /**
   * Rest resource for video listings mobile.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    $user_utility = new UserUtility();
    $offset = $request->query->get('offset') ? $request->query->get('offset') : 0;
    $limit = $request->query->get('limit') ? $request->query->get('limit') : 3;
    // Prepare array of keys for alteration in response.
    $data = [
      'categoryName' => 'decode',
      'categoryId' => 'int',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    $roles = $user_utility->getUserRoles(\Drupal::currentUser()->id());
    // Prepare redis key.
    $key = ':videoListingMobile:' . $user_market . '_' . $roles[0] . '_' .
     \Drupal::currentUser()->getPreferredLangcode() . '_' . $limit . '_' .
      $offset;
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key, 'video_listing_mobile',
    'rest_export_video_listing_mobile', $data);
    $decode = JSON::decode($view_results, TRUE);
    if (!empty($decode)) {
      $view_results = $this->prepareRow($decode, $limit, $offset);
    }

    return new JsonResponse($view_results, $status_code, [], TURE);
  }

  /**
   * Fetch result form video listing mobile.
   *
   * @param mixed $decode
   *   View data.
   * @param int $limit
   *   View limit.
   * @param int $offset
   *   View offset.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode, $limit, $offset) {
    $common_utility = new CommonUtility();
    $data = $response = [];
    // Alter response of view result.
    foreach ($decode as $key => $element) {
      $total[$element['categoryId']]['results'][] = $element;
      $data[$element['categoryId']]['results'][] = $element;
    }
    $result = array_values($data);
    // Show response video category wise.
    foreach ($result as $key => $value) {
      $response[$key]['categoryId'] = $value['results'][0]['categoryId'];
      $response[$key]['categoryName'] =
      !empty($value['results'][0]['categoryName']) ?
      $value['results'][0]['categoryName'] : 'Others';
      $response[$key]['totalCount'] =
      count($total[$value['results'][0]['categoryId']]['results']);
      $response[$key]['results'] = array_slice($value['results'], $offset,
      $limit);
      $response[$key]['userActivity'] = [];
      if (!empty($response[$key]['results'])) {
        $response[$key]['userActivity'] = $common_utility
          ->getUserActivities(JSON::encode($response), 'videoListingMobile');
      }
    }

    return JSON::encode($response);
  }

}
