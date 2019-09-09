<?php

namespace Drupal\trlx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a video listings resource.
 *
 * @RestResource(
 *   id = "video_listings",
 *   label = @Translation("Video Listings"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/content/list/video"
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

    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'displayTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    $status_code = 200;
    list($limit, $offset) = $common_utility->getPagerParam($request);
    // Prepare redis key.
    $key = ':videoListings:' . '_' . $limit . '_' . $offset;
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key,
    'video_listing', 'rest_export_video_listing', $data);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
