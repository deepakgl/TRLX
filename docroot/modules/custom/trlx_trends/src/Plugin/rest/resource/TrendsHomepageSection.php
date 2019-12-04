<?php

namespace Drupal\trlx_trends\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a TR Trends section resource.
 *
 * @RestResource(
 *   id = "tr_trends_homepage_section",
 *   label = @Translation("TR Trends Homepage Section"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/trendSection"
 *   }
 * )
 */
class TrendsHomepageSection extends ResourceBase {

  /**
   * GET resource for TR Trends Section.
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

    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Check for valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'nid' => 'int',
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'body' => 'string_replace',
      'pointValue' => 'int',
    ];

    // Prepare view response.
    $key = ":home:tr_trends_{$language}_{$_userData->uid}";
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      $key,
      'stories_listing',
      'rest_export_tr_trends_homepage',
      $data,
      ['language' => $language]
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results['results'], $status_code);
  }

}
