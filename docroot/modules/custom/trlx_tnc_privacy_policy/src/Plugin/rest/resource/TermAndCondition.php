<?php

namespace Drupal\trlx_tnc_privacy_policy\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a Terms and Conditons resource.
 *
 * @RestResource(
 *   id = "term_and_condition",
 *   label = @Translation("Terms & Conditions"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/termAndCondition"
 *   }
 * )
 */
class TermAndCondition extends ResourceBase {

  /**
   * Rest resource for T&C content.
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
      'title' => 'decode',
      'body' => 'string_replace',
    ];

    // Fetch view response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      't_c',
      'rest_export_tnc',
      $data,
      ['language' => $language]
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse((Object) [], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code);
  }

}
