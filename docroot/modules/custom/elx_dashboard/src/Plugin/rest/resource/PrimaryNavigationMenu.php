<?php

namespace Drupal\elx_dashboard\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\elx_dashboard\Utility\DashboardUtility;
use Drupal\elx_utility\RedisClientBuilder;

/**
 * Provides a Primary Navigation Menu.
 *
 * @RestResource(
 *   id = "primary_navigation_menu",
 *   label = @Translation("Primary Navigation Menu"),
 *   uri_paths = {
 *     "canonical" = "/api/{version}/primaryNavigationMenu"
 *   }
 * )
 */
class PrimaryNavigationMenu extends ResourceBase {

  /**
   * Rest resource.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Json response.
   */
  public function get($version) {
    // Prepare redis key.
    $key = \Drupal::config('elx_utility.settings')->get('elx_environment') .
     ':navigationMenu:' .
     $version . '_' . \Drupal::currentUser()->getPreferredLangcode();
    try {
      list($cached_data, $redis_client) =
      RedisClientBuilder::getRedisClientObject($key);
      if (!empty($cached_data)) {
        return new JsonResponse($cached_data, 200, [], TRUE);
      }
    }
    catch (\Exception $e) {
      $view_results = $this->getNavigationMenuResponse($version);

      return new JsonResponse($view_results, 200, [], TRUE);
    }
    $view_results = $this->getNavigationMenuResponse($version);
    if (empty($view_results)) {
      return new JsonResponse(Json::encode([]), 204, [], TRUE);
    }
    $key = explode(":", $key);
    $redis_client->set($view_results, $key[0], $key[1], $key[2]);

    return new JsonResponse($view_results, 200, [], TRUE);
  }

  /**
   * Fetch Navigation menu.
   *
   * @return json
   *   View result.
   */
  private function getNavigationMenuResponse($version) {
    $dashboard_utility = new DashboardUtility();
    // Load Navigation menu.
    $primary_navigation_menu = $dashboard_utility->getMenuByName('main',
    'navigation', $version);
    $data['primaryNavigationMenu'] =
    array_values(array_filter($primary_navigation_menu));
    $view_results = JSON::encode($data);
    if (is_object($view_results)) {
      $view_results = $view_results->getContent();
    }

    return $view_results;
  }

}
