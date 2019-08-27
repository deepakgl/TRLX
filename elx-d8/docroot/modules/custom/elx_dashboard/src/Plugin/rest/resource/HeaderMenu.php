<?php

namespace Drupal\elx_dashboard\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_dashboard\Utility\DashboardUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a Header Menu.
 *
 * @RestResource(
 *   id = "header_menu",
 *   label = @Translation("Header Menu"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/headerMenu"
 *   }
 * )
 */
class HeaderMenu extends ResourceBase {

  /**
   * Rest resource.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Json response.
   */
  public function get() {
    $entity_utility = new EntityUtility();
    $uid = \Drupal::currentUser()->id();
    // Prepare redis key.
    $key = ':headerMenu:' . '_' . $uid . '_' .
     \Drupal::currentUser()->getPreferredLangcode();
    // Prepare response.
    list($view_results, $status_code) = $entity_utility->fetchApiResult($key,
     'header_api', 'rest_export_header_api', NULL, $uid);
    $decode = JSON::decode($view_results, TRUE);
    if (!empty($decode)) {
      $view_results = $this->prepareRow($decode);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

  /**
   * Fetch Header menu.
   *
   * @param mixed $decode
   *   View data.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode) {
    $common_utility = new CommonUtility();
    $dashboard_utility = new DashboardUtility();
    // Load Header menu.
    $nav_data = $dashboard_utility->getMenuByName('header-menu', 'header');
    // Fetch user market by language.
    $market = implode(", ", $common_utility->getMarketNameByLang(explode(",", $decode[0]['market'])));
    $decode[0]['market'] = $market;
    $data['userDetails'] = $decode;
    $data['navData'] = array_values(array_filter($nav_data));
    $view_results = JSON::encode($data);

    return $view_results;
  }

}
