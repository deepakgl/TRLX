<?php

namespace Drupal\elx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a video category resource.
 *
 * @RestResource(
 *   id = "video_category",
 *   label = @Translation("Video Category"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/videoCategory"
 *   }
 * )
 */
class VideoCategory extends ResourceBase {

  /**
   * Fetch video category.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Video category.
   */
  public function get(Request $request) {
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
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
      'name' => 'decode',
      'categoryId' => 'int',
    ];
    list($limit, $offset) = $common_utility->getPagerParam($request);
    // Prepare redis key.
    $key = ':videoCategory:' . $redis_param . '_' . \Drupal::currentUser()
      ->getPreferredLangcode() . '_' . $limit . '_' . $offset;
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key, 'video_category', 'rest_export_video_category',
    $data, $tid);
    $decode = JSON::decode($view_results, TRUE);
    if (!empty($decode['results'])) {
      $decode = ['totalCount' => count($decode['results'])] + $decode;
      $view_results = JSON::encode($decode);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
