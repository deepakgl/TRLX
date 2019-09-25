<?php

namespace Drupal\elx_tools\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a tools listings resource.
 *
 * @RestResource(
 *   id = "tools_listings",
 *   label = @Translation("Tools Listings"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/toolsListings"
 *   }
 * )
 */
class ToolsListings extends ResourceBase {

  /**
   * Rest resource for tools listings.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    $data = [
      'title' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    list($limit, $offset) = $common_utility->getPagerParam($request);
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    // Prepare redis key.
    $key = ':toolsListings:' . $user_market . '_' . \Drupal::currentUser()
      ->getPreferredLangcode() . '_' . $limit . '_' . $offset;
    // Prepare response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key,
    'tools_listing', 'rest_export_tools_listing', $data);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
