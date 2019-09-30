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
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
      'brandId',
      'language',
    ];

    // Check for required parameters.
    $missingParams = [];
    foreach ($requiredParams as $param) {
      $$param = $request->query->get($param);
      if (empty($$param)) {
        $missingParams[] = $param;
      }
    }
    // Report missing required parameters.
    if (!empty($missingParams)) {
      return $commonUtility->invalidData($missingParams);
    }
    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    // Checkfor valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Validation for valid brand key
    // Prepare view response for valid brand key.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      'brand_key_validation',
      'rest_export_brand_key_validation',
      '',
      $brandId
    );

    // Check for empty resultset.
    if (empty($view_results)) {
      return $commonUtility->errorResponse($this->t('Brand Id (@brandId) does not exist.', ['@brandId' => $brandId]), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'displayTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'video' => 'append_host',
      'videoSubtitle' => 'append_host',
      'body' => 'string_replace',
    ];

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      'video_listing',
      'rest_export_video_listing',
      $data, ['language' => $language, 'brand' => $brandId],
      'video_listing'
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code, [], 'results');
  }

}
