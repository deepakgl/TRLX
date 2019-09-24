<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

/**
 * Provides a modules listing resource.
 *
 * @RestResource(
 *   id = "modules_listing",
 *   label = @Translation("Modules Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/modulesListing"
 *   }
 * )
 */
class ModulesListing extends ResourceBase {

  /**
   * Fetch modules listing.
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

    // Checkfor valid category id.
    if (empty($commonUtility->isValidTid($categoryId))) {
      return $commonUtility->errorResponse($this->t('Category id does not exist.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'nid' => 'int',
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'pointValue' => 'int',
    ];

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
        NULL,
        'level_interactive_content',
        'rest_export_level_interactive_content',
        $data, ['language' => $language, 'categoryId' => $categoryId],
        'modules_listing'
      );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($view_results, $status_code, [], 'results');
  }

}
