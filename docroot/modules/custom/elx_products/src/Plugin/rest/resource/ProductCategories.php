<?php

namespace Drupal\elx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a product categories resource.
 *
 * @RestResource(
 *   id = "product_categories",
 *   label = @Translation("Product Categories"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/productCategories"
 *   }
 * )
 */
class ProductCategories extends ResourceBase {

  /**
   * Rest resource for product categories.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
    // Get term id.
    $tid = $request->query->get('categoryId');
    $redis_param = $tid;
    if (empty($tid)) {
      $tid = '';
      $redis_param = 'all';
    }
    // Check if term id exists.
    $check = $common_utility->isValidTid($tid);
    if (empty($check) && !empty($tid)) {
      return new JsonResponse('Term id does not exist.', 422, [], FALSE);
    }
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    list($limit, $offset) = $common_utility->getPagerParam($request);
    // Prepare array of keys for alteration in response.
    $data = [
      'categoryId' => 'int',
    ];
    // Prepare redis key.
    $key = ':productCategories:' . $user_market . '_' . $redis_param . '_' .
     \Drupal::currentUser()->getPreferredLangcode() . '_' . $limit . '_' . $offset;
    // Prepare response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key,
    'product_categories', 'rest_export_product_categories', $data, $tid);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
