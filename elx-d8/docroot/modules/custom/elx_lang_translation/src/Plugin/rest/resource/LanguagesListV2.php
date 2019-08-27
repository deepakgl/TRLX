<?php

namespace Drupal\elx_lang_translation\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_lang_translation\Utility\LangUtility;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Get primary & secondary languages.
 *
 * @RestResource(
 *   id = "languages",
 *   label = @Translation("Get primary & secondary languages"),
 *   uri_paths = {
 *     "canonical" = "/api/v2/languages"
 *   }
 * )
 */
class LanguagesListV2 extends ResourceBase {

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
    // Get all languages.
    $lang_utility = new LangUtility();
    $common_utility = new CommonUtility();
    $market_id = $request->query->get('marketId');
    if (empty($market_id)) {
      return $common_utility->invalidData(['marketId']);
    }
    $lang_list =
    $lang_utility->getMarketPrimaryAndSecondaryLanguage($market_id, 'lang');
    $primary_lang_code = array_unique(array_column(
    $lang_list, 'field_primary_language_target_id'));
    $secondary_lang_code = array_unique(array_column(
    $lang_list, 'field_secondary_language_target_id'));
    $primary_language_info =
     \Drupal::languageManager()->getLanguage($primary_lang_code[0]);
    // Response of primary language.
    $result['primary'] = [];
    if (!empty($primary_language_info)) {
      $result['primary'][] = [
        'languageCode' => $primary_lang_code[0],
        'languageName' => $primary_language_info->getName(),
        'languageDirection' => $primary_language_info->getDirection(),
      ];
    }
    // Response of secondary language.
    $result['secondary'] = [];
    foreach ($secondary_lang_code as $key => $values) {
      if (!empty($values)) {
        $result['secondary'][] = [
          'languageCode' => $values,
          'languageName' =>
          \Drupal::languageManager()->getLanguage($values)->getName(),
          'languageDirection' =>
          \Drupal::languageManager()->getLanguage($values)->getDirection(),
        ];
      }
    }

    return new JsonResponse($result, 200, [], FALSE);
  }

}
