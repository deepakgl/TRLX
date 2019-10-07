<?php

namespace Drupal\trlx_lang_translation\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\elx_utility\Utility\EntityUtility;

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
    $language_code = $request->query->get('languageCode');
    $all_languages = \Drupal::languageManager()->getLanguages();
    $lang_code = [];
    foreach ($all_languages as $key => $all_language) {
      $lang_code[] = $all_language->getId();
    }
    if (!in_array($language_code, $lang_code)) {

      return new JsonResponse('Invalid language code', 400, [], FALSE);
    }
    // Prepare view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult(NULL, 'static_translation_api',
    'rest_export_static_translation_api', NULL, $language_code);
    $decode = JSON::decode($view_results, TRUE);
    if (!empty($decode)) {
      foreach ($decode as $key => $value) {
        $decode[$key] = [
          $value['SourceKey'] =>
          Html::decodeEntities($value['StringTransaltion']),
        ];
        unset($decode[$key]['SourceKey']);
        unset($decode[$key]['StringTransaltion']);
      }
      $view_results = JSON::encode($decode);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
