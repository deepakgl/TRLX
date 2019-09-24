<?php

namespace Drupal\trlx_spotlight\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a spotlight section resource.
 *
 * @RestResource(
 *   id = "spotlight_section",
 *   label = @Translation("Spotlight Section"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/spotlightSection"
 *   }
 * )
 */
class SpotlightSection extends ResourceBase {

  /**
   * GET resource for Spotlight Section.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
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

    // Check for valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'nid' => 'int',
      'displayTitle' => 'decode',
      'displayTitle_dup' => 'decode',
      'body' => 'decode',
      'pointValue' => 'int',
    ];

    // Prepare array for fields that need to be replaced.
    $field_replace = [
      'displayTitle' => 'displayTitle_dup',
    ];

    // Prepare view response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      'spotlight_section',
      'rest_export_spotlight_section',
      $data,
      ['language' => $language],
      NULL,
      $field_replace
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results['results'], $status_code);
  }

}
