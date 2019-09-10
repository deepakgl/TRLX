<?php

namespace Drupal\trlx_factsheets\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

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
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();
  
    // Required parameters
    $requiredParams = [
      '_format',
      'brandId',
      'language',
    ];

    // Check for required parameters
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

    // Checkfor valid _format type
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Checkfor valid language code
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
  
    // Validation for valid brand key
    // Prepare view response for valid brand key
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      'brand_key_validation',
      'rest_export_brand_key_validation',
      '',
      $brandId
    );
  
    // Check for empty resultset
    if (empty($view_results)) {
      return $commonUtility->errorResponse($this->t('Brand Id (@brandId) does not exist.', ['@brandId' => $brandId]), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'nid' => 'int',
      'link' => 'int',
      'title' => 'decode',
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'pointValue' => 'int',
      'downloadable' => 'boolean'
    ];

    list($limit, $offset) = $commonUtility->getPagerParam($request);
  
    // Prepare redis key.
    $key = ':factsheetsListings:' . '_' . $language . '_' . $limit . '_' . $offset;

    // Prepare view response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      $key,
      'fact_sheets_list',
      'rest_export_fact_sheets_list',
      $data,
      ['brand' => $brandId, 'language' => $language, 'limit' => $limit, 'offset' => $offset]
    );

    // Check for empty / no result from views
    if (empty($view_results)) {
      // fixMe - Check what code to pass to response
      return $commonUtility->errorResponse($this->t('No result found.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    return $commonUtility->successResponse($view_results['results'], $status_code, TRUE, $view_results['pager']);
  }
}