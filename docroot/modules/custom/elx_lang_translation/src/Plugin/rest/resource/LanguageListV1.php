<?php

namespace Drupal\elx_lang_translation\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;

/**
 * Get language list.
 *
 * @RestResource(
 *   id = "language_list",
 *   label = @Translation("Get language list"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/languageList"
 *   }
 * )
 */
class LanguageListV1 extends ResourceBase {

  /**
   * Fetch all languages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   All languages.
   */
  public function get(Request $request) {
    $language = \Drupal::languageManager()->getLanguages();
    if (empty($language)) {
      return new JsonResponse(JSON::encode([]), 204, [], TRUE);
    }
    $response = [];
    foreach ($language as $key => $value) {
      $response[] = [
        'languageCode' => $value->getId(),
        'languageName' => $value->getName(),
        'languageDirection' => $value->getDirection(),
      ];
    }

    return new JsonResponse($response, 200, [], FALSE);
  }

}
