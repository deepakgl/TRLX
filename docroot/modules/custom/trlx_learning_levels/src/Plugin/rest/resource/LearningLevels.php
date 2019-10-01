<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_learning_levels\Utility\LevelUtility;

/**
 * Provides a learning levels resource.
 *
 * @RestResource(
 *   id = "learning_levels",
 *   label = @Translation("Learning Levels"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/learningLevels"
 *   }
 * )
 */
class LearningLevels extends ResourceBase {

  /**
   * Fetch learning levels.
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
    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    // Checkfor valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    $content_section = '';
    $brandId = $request->query->get('brandId');
    $params = ['language' => $language];
    if (!empty($brandId)) {
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
      $content_section = 'brandLevel';
      $params = ['language' => $language, 'brand' => $brandId, 'section' => $content_section];
    }
    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'subTitle' => 'decode',
      'categoryId' => 'int',
      'pointValue' => 'int',
    ];

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
        NULL,
        'learning_levels',
        'rest_export_learning_levels',
        $data, $params,
        'level_listing'
      );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }
    if (!empty($view_results['results'])) {
      $view_results = $this->prepareRow($view_results, $offset, $limit, $language);
    }

    return $commonUtility->successResponse($view_results, $status_code, [], 'results');
  }

  /**
   * Fetch learning levels.
   *
   * @param mixed $decode
   *   View data.
   * @param int $offset
   *   View offset.
   * @param int $limit
   *   View limit.
   * @param string $language
   *   Language code.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode, $offset, $limit, $language) {
    $levelUtility = new LevelUtility();
    $term_ids = array_column($decode['results'], 'categoryId');
    global $_userData;
    // Get level intreactive node ids assosiated with level.
    $term_nodes = $levelUtility->getTermNodes($term_ids, $_userData, $language);
    $user_activity = [];
    $tmp = 0;
    foreach ($decode['results'] as $key => $value) {
      if (!isset($term_nodes[$value['categoryId']])) {
        // Remove level from listing in no module belongs to user market and.
        // language.
        unset($value);
        $decode['results'][$key] = $value;
        $tmp++;
      }
    }
    $data = array_values(array_filter($decode['results']));
    $decode['results'] = array_slice($data, $offset, $limit);
    $decode['pager']['count'] = count($data) - $offset;
    $decode['pager']['pages'] = ceil(count($data) / $limit);
    $decode['pager']['items_per_page'] = $limit;

    return $decode;
  }

}
