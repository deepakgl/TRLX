<?php

namespace Drupal\trlx_lang_translation\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\Html;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * Get translation key.
 *
 * @RestResource(
 *   id = "translation_key",
 *   label = @Translation("Get Key Translation"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/translationKey"
 *   }
 * )
 */
class TranslationKey extends ResourceBase {

  /**
   * Fetch translation key.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Translation key.
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    $commonUtility = new CommonUtility();
    $language_code = $request->query->get('language');

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

    $all_languages = \Drupal::languageManager()->getLanguages();
    $lang_code = array_keys($all_languages);

    if (!in_array($language_code, $lang_code)) {
      return new JsonResponse('Invalid language code', 400, [], FALSE);
    }
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult(NULL, 'static_translation_api',
    'rest_export_static_translation_api', NULL, $language_code);

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    $response = $view_results['results'];
    if (!empty($response)) {
      foreach ($response as $key => $value) {
        $response[$key]['StringTransaltion'] = Html::decodeEntities($value['StringTransaltion']);
      }
    }

    return $commonUtility->successResponse($response, $status_code, []);
  }

}
