<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_learning_levels\Utility\LevelUtility;

/**
 * Provides a user activities level resource.
 *
 * @RestResource(
 *   id = "user_activities_level",
 *   label = @Translation("User Activity Level"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/userActivitiesLevel"
 *   }
 * )
 */
class UserActivitiesLevel extends ResourceBase {

  /**
   * Fetch user activities level.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $levelUtility = new LevelUtility();
    global $_userData;

    // Required parameters.
    $requiredParams = [
      '_format',
      'language',
      'categoryId',
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

    // @todo Will remove foreach.
    foreach ($categoryId as $key => $value) {
      // Checkfor valid category id.
      if (empty($commonUtility->isValidTid($value, 'learning_category'))) {
        return $commonUtility->errorResponse($this->t('Category id does not exist.'), Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }

    // Get level intreactive node ids assosiated with level.
    $term_nodes = $levelUtility->getTermNodes($categoryId, $_userData, $language);
    $module_details = [];
    if (!empty($term_nodes)) {
      // Get Level activity.
      foreach ($term_nodes as $key => $value) {
        $module_details[] = $levelUtility
          ->getLevelActivity($_userData,
         $key, array_column($term_nodes[$key], 'nid'), $language);
      }
    }

    return $commonUtility->successResponse($module_details, Response::HTTP_OK);

  }

}
