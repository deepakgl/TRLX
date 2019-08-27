<?php

namespace Drupal\elx_dashboard\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a Welcome Message.
 *
 * @RestResource(
 *   id = "welcome_message",
 *   label = @Translation("Welcome Message"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/welcomeMessage"
 *   }
 * )
 */
class WelcomeMessage extends ResourceBase {

  /**
   * Rest resource.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Json response.
   */
  public function get() {
    $entity_utility = new EntityUtility();
    // Get result from welcome Message view.
    list($view_results, $status_code) = $entity_utility->fetchApiResult(NULL,
     'welcome_message_api', 'rest_export_welcome_message_api');
    $decode = JSON::decode($view_results);
    if (!empty($decode)) {
      $view_results = $this->prepareRow($decode);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

  /**
   * Alter welcome message response.
   *
   * @param mixed $decode
   *   View data.
   *
   * @return json
   *   View result.
   */
  protected function prepareRow($decode) {
    // Add additional keys in API response.
    foreach ($decode as $key => $value) {
      // Remove item if not avalabile in user language.
      if (empty($value['nid'])) {
        unset($decode[$key]);
        continue;
      }
      $decode[$key]['id'] = $key + 1;
      $decode[$key]['url'] = !empty($value['url']) ? $value['url'] : '';
      $decode[$key]['subTitle'] = !empty($value['subTitle']) ? $value['subTitle'] : '';
    }
    $view_results = JSON::encode(array_values($decode));

    return $view_results;
  }

}
