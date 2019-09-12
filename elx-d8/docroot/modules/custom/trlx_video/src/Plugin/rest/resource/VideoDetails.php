<?php

namespace Drupal\trlx_video\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a video details page resource.
 *
 * @RestResource(
 *   id = "video_details",
 *   label = @Translation("Video Details"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/videoDetails"
 *   }
 * )
 */
class VideoDetails extends ResourceBase {

  /**
   * Rest resource for video details.
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
      'nid' => 'int',
      'pointValue' => 'int',
      'video' => 'append_host',
      'videoSubtitle' => 'append_host',
      'downloadable' => 'boolean',
    ];

    // Prepare redis key.
    $key = ':videoDetails:' . '_' . $nid . '_' . $language;

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      $key,
      'video_details',
      'rest_export_video_details',
      $data, ['nid' => $nid, 'language' => $language],
      'video_detail'
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code);
  }

}
