<?php

namespace Drupal\trlx_trends\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a tr trend listing resource.
 *
 * @RestResource(
 *   id = "trends_listing",
 *   label = @Translation("TR trends Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/trendListing"
 *   }
 * )
 */
class TrendListing extends ResourceBase {

  /**
   * Fetch tr trend listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Story listing.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
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

    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'body' => 'string_replace',
    ];

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      'stories_listing',
      'rest_export_stories_listing',
      $data, ['language' => $language],
      'trend_listing'
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code, [], 'results');
  }

}
