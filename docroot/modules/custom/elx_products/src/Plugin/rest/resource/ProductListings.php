<?php

namespace Drupal\elx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a product listings resource.
 *
 * @RestResource(
 *   id = "product_listings",
 *   label = @Translation("Product Listings"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/productListings"
 *   }
 * )
 */
class ProductListings extends ResourceBase {

  /**
   * Rest resource for product listings.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Json response.
   */
  public function get(Request $request) {
    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
    $tid = $request->query->get('categoryId');
    $redis_param = $tid;
    if (empty($tid)) {
      $tid = '';
      $redis_param = 'all';
    }
    // Check if term id exists.
    if (empty($common_utility->isValidTid($tid)) && !empty($tid)) {
      return new JsonResponse('Term id does not exist.', 422, [], FALSE);
    }
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
    ->id());
    list($limit, $offset) = $common_utility->getPagerParam($request);
    $roles = $user_utility->getUserRoles(\Drupal::currentUser()->id());
    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    // Prepare redis key.
    $key = ':productListings:' . $user_market . '_' . $roles[0] . '_' .
     \Drupal::currentUser()->getPreferredLangcode() . '_' . $redis_param . '_'
      . $limit . '_' . $offset;
    // Prepare response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key, 'product_listings', 'rest_export_product_listings',
     $data, $tid);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
