<?php

namespace Drupal\trlx_story\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides an Selling Tips listing resource.
 *
 * @RestResource(
 *   id = "selling_tips_listing",
 *   label = @Translation("Selling Tips Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/sellingTipsListing"
 *   }
 * )
 */
class SellingTipsListing extends ResourceBase {

  /**
   * Rest resource for listing Selling Tips content.
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

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Fetch learning_category term response.
    list($term_view_results, $term_status_code) = $entityUtility->fetchApiResult(
      '',
      'selling_tips_learning_level_vocabulary',
      'rest_export_selling_tips_learning_level_listing',
      '',
      ['language' => $language]
    );

    // Fetch stories bundle content response.
    list($content_view_results, $term_status_code) = $entityUtility->fetchApiResult(
      '',
      'selling_tips',
      'rest_export_selling_tips_content_listing',
      '',
      ['language' => $language]
    );

    // Intialize variables.
    $results = $view_results = [];
    $count = 0;

    if (empty($limit)) {
      $limit = 10;
    }

    if (empty($offset)) {
      $offset = 0;
    }

    // Fetch Learning Level Selling Tips term(s).
    if (!empty($term_view_results['results'])) {
      $results = $term_view_results['results'];
      $count = $term_view_results['pager']['count'];
    }

    // Fetch Stories Selling Tips Content(s).
    if (!empty($content_view_results['results'])) {
      $results = array_merge($results, $content_view_results['results']);
      $count = $count + $content_view_results['pager']['count'];
    }

    if (!empty($results)) {
      // Sort merged array using "timestamp" key.
      usort($results, function ($timestamp1, $timestamp2) {
        return $timestamp2['timestamp'] <=> $timestamp1['timestamp'];
      });

      // Slice array as per passed limit & offset.
      $results = array_slice($results, $offset, $limit);
    }

    if (!empty($results)) {
      $pagerCount = round($count - $offset);
      $pages = round(($pagerCount / $limit), 0);
      $pager['count'] = $pagerCount;
      $pager['pages'] = $pages;
      $pager['items_per_page'] = $limit;
      $pager['current_page'] = 0;
      $pager['next_page'] = ($pages > 1) ? 1 : 0;

      // Set results, pager & status code.
      $view_results['results'] = $results;

      $data = [
        'id' => 'int',
        'displayTitle' => 'decode',
        'subTitle' => 'decode',
        'pointValue' => 'int',
        'pointValueLevel' => 'point_value_level',
        'body' => 'string_replace',
      ];

      $view_results = $entityUtility->buildListingResponse($view_results, $data, [], ['timestamp', 'pointValueLevel']);

      $view_results['pager'] = $pager;
      $status_code = Response::HTTP_OK;
    }

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results['results'], $status_code, $view_results['pager']);
  }

}
