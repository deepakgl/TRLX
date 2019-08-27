<?php

namespace Drupal\elx_search\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Provides a ar key value data resource.
 *
 * @RestResource(
 *   id = "ar_key_value",
 *   label = @Translation("AR Key and value"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/arKeyValueData"
 *   }
 * )
 */
class ArKeyValueData extends ResourceBase {

  /**
   * Fetch ar search key value data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   View result.
   */
  public function get(Request $request) {
    // Fetch ar key value tree.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('ar_key_value');
    $data = [];
    foreach ($terms as $key => $value) {
      $data[] = [
        'key' => $value->name,
        'value' => trim(strip_tags(Html::decodeEntities($value->description__value))),
      ];
    }

    return new JsonResponse(['results' => $data], 200, [], FALSE);
  }
}
