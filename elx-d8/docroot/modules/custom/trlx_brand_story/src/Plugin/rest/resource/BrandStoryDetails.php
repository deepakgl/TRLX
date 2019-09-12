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
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'title' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'video' => 'append_host',
      'downloadable' => 'boolean',
    ];
    // Prepare redis key.
    $key = ':brandStoryDetails:' . '_' . $nid . '_' . $language;

    // Prepare response.
    list($view_results, $status_code,) = $entityUtility->fetchApiResult(
      $key,
      'brand_story',
      'rest_export_brand_story_details',
      $data, ['nid' => $nid, 'language' => $language],
      'brand_story_detail'
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code);
  }

}
