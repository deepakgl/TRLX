<?php

namespace Drupal\trlx_story\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a story details page resource.
 *
 * @RestResource(
 *   id = "story_details",
 *   label = @Translation("Story Details"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/storyDetails"
 *   }
 * )
 */
class StoryDetails extends ResourceBase {

  /**
   * Rest resource for story details.
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
      'nid',
      'language',
      'section',
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

    if (empty($commonUtility->isValidNid($nid, $language, 'stories'))) {
      return $commonUtility->errorResponse($this->t('Node id does not exist or requested language data is not available.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Check for valid section name.
    $response = $commonUtility->validateStorySectionCode($section, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Prepare redis key.
    $key = ":storyDetail:_{$nid}_{$language}";

    $views = $viewsDisplay = $type = $pointValAlterKey = '';

    // Switch case to specify section specific views & variables.
    switch ($section) {
      case $commonUtility::INSIDER_CORNER:
        $views = 'insider_corner';
        $viewsDisplay = 'rest_export_insider_corner_details';
        $type = $commonUtility::INSIDER_CORNER;
        $pointValAlterKey = 'point_value_' . $commonUtility::INSIDER_CORNER;
        break;

      default:
        $views = 'stories_listing';
        $viewsDisplay = 'rest_export_story_details';
        $type = 'trend_detail';
        $pointValAlterKey = 'point_value_' . $commonUtility::TREND;
        break;
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => $pointValAlterKey,
    ];

    // Add Section specific result keys.
    if ($commonUtility::INSIDER_CORNER == $section) {
      $data['socialMediaHandles'] = 'social_media_handles';
      $data['video'] = 'append_host';
    }

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      $key,
      $views,
      $viewsDisplay,
      $data,
      ['nid' => $nid, 'language' => $language],
      $type
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code);
  }

}
