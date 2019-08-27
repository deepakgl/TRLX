<?php

namespace Drupal\elx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_entityqueue_alter\Utility\EntityQueueUtility;
use Drupal\Component\Serialization\Json;

/**
 * Provides a browse products resource.
 *
 * @RestResource(
 *   id = "browse_products",
 *   label = @Translation("Browse Products"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/browseProducts"
 *   }
 * )
 */
class BrowseProducts extends ResourceBase {

  /**
   * Rest resource for browse product.
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
    $queue_utility = new EntityQueueUtility();
    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    $uid = \Drupal::currentUser()->id();
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId($uid);
    $roles = $user_utility->getUserRoles($uid);
    $bypass_market_queue = $queue_utility
      ->fetchQueueOverrideFlagStatus('browse_products');
    $decode = [];
    if (!$bypass_market_queue) {
      // Prepare redis key for spotlight market wise.
      $key = ':browseProductsMarketWise:' . $user_market . '_' . $roles[0] .
       '_' . \Drupal::currentUser()->getPreferredLangcode();
      list($response, $status_code) = $entity_utility->fetchApiResult($key,
       'browse_products', 'rest_export_browse_products_market_wise', $data,
        NULL, 'browse_products');
      $decode = JSON::decode($response, TRUE);
    }
    if (empty(array_filter($decode))) {
      // Prepare redis key.
      $key = ':globalBrowseProducts:' . $roles[0] .
       '_' . \Drupal::currentUser()->getPreferredLangcode();
      // Prepare response.
      list($response, $status_code) = $entity_utility->fetchApiResult($key,
      'browse_products', 'rest_export_browse_products', $data, NULL,
      'browse_products');
    }

    return new JsonResponse($response, $status_code, [], TRUE);
  }

}
