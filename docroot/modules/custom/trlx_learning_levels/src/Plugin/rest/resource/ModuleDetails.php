<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_learning_levels\Utility\LevelUtility;

/**
 * Provides a modules details resource.
 *
 * @RestResource(
 *   id = "module_details",
 *   label = @Translation("Module Details"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/moduleDetails"
 *   }
 * )
 */
class ModuleDetails extends ResourceBase {

  /**
   * Fetch module details.
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
      'nid',
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

    if (empty($commonUtility->isValidNid($nid, $language))) {
      return $commonUtility->errorResponse($this->t('Node id does not exist or requested language data is not available.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'nid' => 'int',
      'displayTitle' => 'decode',
      'pointValue' => 'int',
      'categoryName' => 'decode',
    ];

    // Prepare response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
        NULL,
        'interactive_content_detail_page',
        'rest_export_interactive_content_detail_page',
        $data, ['nid' => $nid, 'language' => $language],
        'modules_details'
      );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    if (!empty($view_results)) {
      $view_results = $this->prepareRow($view_results, $nid, $language);
    }

    return $commonUtility->successResponse($view_results, $status_code);
  }

  /**
   * Fetch result form module detail.
   *
   * @param mixed $decode
   *   View data.
   * @param int $nid
   *   Node id.
   * @param string $language
   *   Language code.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode, $nid, $language) {
    $levelUtility = new LevelUtility();
    global $_userData;
    global $base_url;
    // @todo will add dynamic data once user repository work done.
    // $base_url = Settings::get('file_public_root_base_url');
    // Get all user information from user repository.
    // $uuid = $user_utility->getUserUuid(\Drupal::currentUser()->id());
    // $user_roles = $user_utility->getUserRoles(\Drupal::currentUser()->id(),
    // 'all_roles');
    // $user_email = \Drupal::currentUser()->getEmail();
    // $user_name = \Drupal::currentUser()->getUsername();
    $user_roles = ['Administrator'];
    $user_email = 'lmx@mailinator.com';
    $user_name = 'superadmin';

    $actor = '"mbox":"' . $user_email . '","name":"' .
      $user_name . '","objectType":"' .
      implode(',', $user_roles) . '"';
    $actor = "{" . urlencode($actor) . "}";
    $statement_id = \Drupal::config('elx_utility.settings')
      ->get('lrs_statement_id');

    $learning_category = $levelUtility->getLevelCategory($nid);
    $decode['articulateFile'] = $base_url . $decode['articulateFile']
      . '?tincan=true&endpoint=' . $base_url . '/lm/api/v1/slrsa&auth='
      . $statement_id . '&actor=' . $actor . '&registration=' .
          $uuid . '&uid='
      . $_userData->userId . '&tid=' . $learning_category . '&nid=' . $nid;
    // Fetch previous and next level.
    list($previous, $next) = $levelUtility
      ->fetchPreviousAndNextLevel($_userData, $language, $learning_category, $nid);
    $data = [
      'categoryId' => (int) $learning_category,
      'interactiveContentPrevious' => $previous,
      'interactiveContentNext' => $next,
    ];
    $decode = $decode + $data;

    return $decode;
  }

}
