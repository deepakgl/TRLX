<?php

namespace Drupal\elx_lang_translation\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_lang_translation\Utility\LangUtility;
use Drupal\Component\Serialization\Json;

/**
 * Get string translation.
 *
 * @RestResource(
 *   id = "string_translation",
 *   label = @Translation("Get String Translation"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/stringTranslation"
 *   }
 * )
 */
class StringTranslation extends ResourceBase {

  /**
   * Fetch string translation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   String translation.
   */
  public function get(Request $request) {
    $lang_utility = new LangUtility();
    $lang_code = $request->query->get('languageCode');
    // Check if empty lang code.
    if (empty($lang_code)) {
      return new JsonResponse('Please provide languageCode.', 400, [], FALSE);
    }
    // Fetch string translation.
    $translation = $lang_utility->getStringTranslation($lang_code);
    if (empty($translation)) {
      return new JsonResponse(JSON::encode([]), 204, [], TRUE);
    }

    return new JsonResponse($translation, 200, [], FALSE);
  }

}
