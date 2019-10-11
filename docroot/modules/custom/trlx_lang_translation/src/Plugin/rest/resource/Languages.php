<?php

namespace Drupal\trlx_lang_translation\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * Get language list.
 *
 * @RestResource(
 *   id = "languages",
 *   label = @Translation("Get language list"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/languages"
 *   }
 * )
 */
class Languages extends ResourceBase {

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
    $commonUtility = new CommonUtility();
    $language = \Drupal::languageManager()->getLanguages();
    $_format = $request->get('_format');
    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    if (empty($language)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }
    $response = [];
    foreach ($language as $key => $value) {
      if (in_array($value->getId(), ['en', 'zh-hans'])) {
        $response[] = [
          'languageCode' => $value->getId(),
          'languageName' => $value->getName(),
        ];
      }
    }

    return $commonUtility->successResponse($response, Response::HTTP_OK);
  }

}
