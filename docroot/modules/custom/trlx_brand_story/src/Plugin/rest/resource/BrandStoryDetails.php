<?php

namespace Drupal\trlx_brand_story\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a brand story details resource.
 *
 * @RestResource(
 *   id = "brand_story_details",
 *   label = @Translation("Brand story Details"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/brandStoryDetails"
 *   }
 * )
 */
class BrandStoryDetails extends ResourceBase {

  /**
   * Fetch brand story details.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Story details.
   */
  public function get(Request $request) {
    global $_userData;
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
      'language',
      'brandId',
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

    // Validation for brand key exists in user token or not.
    if (!in_array($brandId, $_userData->brands)) {
      return $commonUtility->successResponse(NULL, Response::HTTP_OK);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'title' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'body' => 'string_replace',
    ];

    // Prepare response.
    $key = ":brand:story_{$brandId}_{$language}";
    list($view_results, $status_code,) = $entityUtility->fetchApiResult(
      $key,
      'brand_story',
      'rest_export_brand_story_details',
      $data, ['language' => $language, 'brand' => $brandId],
      'brand_story_detail'
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse(NULL, Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code);
  }

}
