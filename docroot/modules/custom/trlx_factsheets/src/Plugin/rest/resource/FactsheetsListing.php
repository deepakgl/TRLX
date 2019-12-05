<?php

namespace Drupal\trlx_factsheets\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_brand\Utility\BrandUtility;

/**
 * Provides a Fact Sheets listing resource.
 *
 * @RestResource(
 *   id = "factsheets_listing",
 *   label = @Translation("Fact Sheets Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/factsheetListing"
 *   }
 * )
 */
class FactsheetsListing extends ResourceBase {

  /**
   * Rest resource for listing Fact Sheets.
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
    $brandUtility = new BrandUtility();

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

    // Validation for brand key exists in database.
    $all_brand_keys = $brandUtility->getAllBrandKeys();
    if (!in_array($brandId, $all_brand_keys)) {
      return $commonUtility->errorResponse($this->t('Brand Id (@brandId) does not exist.', ['@brandId' => $brandId]), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Validation for brand key exists in user token or not.
    if (!in_array($brandId, $_userData->brands)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'nid' => 'int',
      'title' => 'decode',
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'pointValue' => 'int',
      'body' => 'string_replace',
    ];

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Prepare response.
    $key = ":brand:factsheets_{$brandId}_{$language}_{$_userData->uid}_{$limit}_{$offset}";
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      $key,
      'fact_sheets_list',
      'rest_export_fact_sheets_list',
      $data,
      ['brand' => $brandId, 'language' => $language]
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results['results'], $status_code, $view_results['pager']);
  }

}
