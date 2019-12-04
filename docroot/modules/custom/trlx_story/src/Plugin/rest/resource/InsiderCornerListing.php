<?php

namespace Drupal\trlx_story\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides an Insider Corner listing resource.
 *
 * @RestResource(
 *   id = "insiderCorner_listing",
 *   label = @Translation("Insider Corner Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/insiderCornerListing"
 *   }
 * )
 */
class InsiderCornerListing extends ResourceBase {

  /**
   * Rest resource for listing Insider Corner content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    global $_userData;
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

    // Checkfor valid _format type.
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
      'nid' => 'int',
      'body' => 'string_replace',
      'pointValue' => 'int',
      'firstName' => 'decode',
      'lastName' => 'decode',
      'occupation' => 'decode',
      'subTitle' => 'decode',
    ];

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Prepare view response.
    $key = ":listing:insider_corner_{$language}_{$_userData->uid}_{$limit}_{$offset}";
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      $key,
      'insider_corner',
      'rest_export_insider_corner_listing',
      $data,
      ['language' => $language]
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results['results'], $status_code, $view_results['pager']);
  }

}
