<?php

namespace Drupal\trlx_brand_story\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_user\Utility\UserUtility;
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
    $user_utility = new UserUtility();
    $this->commonUtility = new CommonUtility();
    $this->entityUtility = new EntityUtility();
    $nid = $request->query->get('nid');
    $language = $request->query->get('language');

    // Check for empty language.
    if (empty($language)) {
      $param = ['language'];

      return $this->commonUtility->invalidData($param);
    }

    // Checkfor valid language code.
    $response = $this->commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    if (empty($nid)) {
      $param = ['nid'];
      return $this->commonUtility->invalidData($param);
    }

    if (empty($this->commonUtility->isValidNid($nid, $language))) {
      return $this->commonUtility->errorResponse($this->t('Node id does not exist or requested language data is not available.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'title' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'video' => 'append_host',
    ];
    // Prepare redis key.
    $key = ':brandStoryDetails:' . '_' . $nid . '_' . $language;

    // Prepare response.
    list($view_results, $status_code,) = $this->entityUtility->fetchApiResult(
      $key,
      'brand_story',
      'rest_export_brand_story_details',
      $data, ['nid' => $nid, 'language' => $language],
      'brand_story_detail'
    );

    // Check for empty/no result from views.
    if (empty($view_results)) {
      return $this->commonUtility->errorResponse($this->t('No result found.'), $status_code);
    }

    return $this->commonUtility->successResponse([$view_results], $status_code);
  }

}
