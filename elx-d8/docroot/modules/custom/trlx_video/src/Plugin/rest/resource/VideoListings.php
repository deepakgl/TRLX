<?php

namespace Drupal\trlx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\trlx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;

/**
 * Provides a video listings resource.
 *
 * @RestResource(
 *   id = "video_listings",
 *   label = @Translation("Video Listings"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/listVideos"
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
    $entity_utility = new EntityUtility();
    $this->commonUtility = new CommonUtility();
    // Validate language code.
    $langcode = $request->query->get('language');
    $response = $this->commonUtility->validateLanguageCode($langcode, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'displayTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    $status_code = 200;
    list($limit, $offset) = $this->commonUtility->getPagerParam($request);
    // Prepare redis key.
    $key = ':videoListings:' . '_' . $langcode . '_' . $limit . '_' . $offset;
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key,
    'video_listing', 'rest_export_video_listing', $data);

    $response = JSON::decode($view_results, TRUE);
    return new JsonResponse(['success' => TRUE, 'result' => $response['results'], 'code' => 200], 200);
  }

}
