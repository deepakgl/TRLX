<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

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

    $brandId = $request->query->get('brandId');
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
        $data, ['language' => $language, 'brand' => $brandId],
        'level_listing'
      );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code, [], 'results');
  }

}
