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
 * Provides a video listings resource.
 *
 * @RestResource(
 *   id = "video_listings",
 *   label = @Translation("Video Listings"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/videoListings"
 *   }
 * )
 */
class VideoListings extends ResourceBase {

  /**
   * Rest resource for video listings.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response
   */
  public function get(Request $request) {
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
    $user_utility = new UserUtility();
    // Term id.
    $tid = $request->query->get('categoryId');
    $redis_param = $tid;
    if (empty($tid)) {
      $tid = '';
      $redis_param = 'all';
    }
    // Check if term id exists.
    if (empty($common_utility->isValidTid($tid)) && !empty($tid)) {
      return new JsonResponse('Term id does not exist.', 422, [], FALSE);
    }
    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'categoryName' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'categoryId' => 'int',
    ];
    list($limit, $offset) = $common_utility->getPagerParam($request);
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    $roles = $user_utility->getUserRoles(\Drupal::currentUser()->id());
    // Prepare redis key.
    $key = ':videoListings:' . $user_market . '_' . $roles[0] . '_' .
     \Drupal::currentUser()->getPreferredLangcode() . '_' . $redis_param . '_'
      . $limit . '_' . $offset;
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key,
    'video_listing', 'rest_export_video_listing', $data, $tid);
    // Add user activities.
    if (!empty(JSON::decode($view_results, TRUE))) {
      $view_results = $common_utility->getUserActivities($view_results, 'videoListing');
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
