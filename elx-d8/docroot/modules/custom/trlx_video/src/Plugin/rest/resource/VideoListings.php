<?php

namespace Drupal\trlx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a video listings resource.
 *
 * @RestResource(
 *   id = "video_listings",
 *   label = @Translation("Video Listings"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/videoListing"
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
    $this->commonUtility = new CommonUtility();
    $this->entityUtility = new EntityUtility();
    $language = $request->query->get('language');

    // Check for empty language.
    if (empty($language)) {
      $param = ['language'];

      return $this->commonUtility->invalidData($param);
    }

    // Checkfor valid language code.
    $response = $this->commonUtility->validateLanguageCode($language, $request);
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

    // Prepare redis key.
    $key = ':videoDetails:' . '_' . $language;

    // Prepare response.
    list($view_results, $status_code) = $this->entityUtility->fetchApiResult(
      $key,
      'video_listing',
      'rest_export_video_listing',
      $data, ['language' => $language],
      'video_listing'
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $this->commonUtility->errorResponse($this->t('No result found.'), $status_code);
    }

    return $this->commonUtility->successResponse($view_results, $status_code);
  }

}
