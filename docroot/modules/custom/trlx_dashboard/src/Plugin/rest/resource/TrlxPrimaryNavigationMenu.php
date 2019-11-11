<?php

namespace Drupal\trlx_dashboard\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\Component\Serialization\Json;
use Drupal\trlx_dashboard\Utility\TrlxDashboardUtility;
use Drupal\trlx_utility\RedisClientBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * Provides a Trlx Primary Navigation Menu.
 *
 * @RestResource(
 *   id = "main_navigation_menu",
 *   label = @Translation("Main Navigation Menu"),
 *   uri_paths = {
 *     "canonical" = "/api/{version}/primaryNavigationMenu"
 *   }
 * )
 */
class TrlxPrimaryNavigationMenu extends ResourceBase {

  /**
   * Rest resource.
   *
   * @param string $version
   *   Defining version of the api e.g. v1.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Json response.
   */
  public function get($version, Request $request) {
    $this->commonUtility = new CommonUtility();

    // Response format validation.
    $_format = $request->query->get('_format');
    $response = $this->commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Validate language code.
    $langcode = $request->query->get('language');
    $response = $this->commonUtility->validateLanguageCode($langcode, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Prepare redis key.
    $key = \Drupal::config('elx_utility.settings')->get('elx_environment') .
      ':navigationMenu:' .
      $version . '_' . $langcode;
    try {
      list($cached_data, $redis_client) = RedisClientBuilder::getRedisClientObject($key);
      if (!empty($cached_data)) {
        return $this->commonUtility->successResponse($cached_data);
      }
    }
    catch (\Exception $e) {
      $view_results = $this->getNavigationMenuResponse($version, $langcode);
      return $this->commonUtility->successResponse($view_results);
    }
    $view_results = $this->getNavigationMenuResponse($version, $langcode);
    if (empty($view_results)) {
      $emptyResponse['primaryNavigationMenu'] = [];
      return $this->commonUtility->successResponse($emptyResponse, Response::HTTP_OK);
    }
    $key = explode(":", $key);
    $redis_client->set($view_results, $key[0], $key[1], $key[2]);

    return $this->commonUtility->successResponse($view_results);
  }

  /**
   * Fetch Navigation menu.
   *
   * @return json
   *   Response
   */
  private function getNavigationMenuResponse($version, $langcode) {
    $this->dashboardUtility = new TrlxDashboardUtility();
    // Load Navigation menu.
    $primary_navigation_menu = $this->dashboardUtility->getMenuByName('main', 'navigation', $version, $langcode);
    if (array_values(array_filter($primary_navigation_menu)) != NULL) {
      $data['primaryNavigationMenu'] = array_values(array_filter($primary_navigation_menu));
    }
    else {
      $data['primaryNavigationMenu'] = [];
    }
    // Sort by 'sequenceId'.
    usort($data['primaryNavigationMenu'], function ($a, $b) {
      // Compare numbers only.
      return $a['sequenceId'] - $b['sequenceId'];
    });
    return $data;
  }

}
