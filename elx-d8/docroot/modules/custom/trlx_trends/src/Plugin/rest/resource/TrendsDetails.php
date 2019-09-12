<?php

namespace Drupal\trlx_trends\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a tr trends details page resource.
 *
 * @RestResource(
 *   id = "trend_details",
 *   label = @Translation("TR Trends Details"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/trendDetails"
 *   }
 * )
 */
class TrendsDetails extends ResourceBase {

  /**
   * Rest resource for tr trends details.
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
    $nid = $request->query->get('nid');
    $language = $request->query->get('language');

    // Check for empty language.
    if (empty($language)) {
      $param = ['language'];

      return $commonUtility->invalidData($param);
    }

    // Checkfor valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    if (empty($nid)) {
      $param = ['nid'];
      return $commonUtility->invalidData($param);
    }

    if (empty($commonUtility->isValidNid($nid, $language))) {
      return $commonUtility->errorResponse($this->t('Node id does not exist or requested language data is not available.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'downloadable' => 'boolean',
    ];

    // Prepare redis key.
    $key = ':trendDetail:' . '_' . $nid . '_' . $language;

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      $key,
      'stories_listing',
      'rest_export_trend_details',
      $data, ['nid' => $nid, 'language' => $language],
      'trend_detail'
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code);
  }

}
