<?php

namespace Drupal\elx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a product details resource.
 *
 * @RestResource(
 *   id = "product_details",
 *   label = @Translation("Product Details"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/productDetails"
 *   }
 * )
 */
class ProductDetails extends ResourceBase {

  /**
   * Rest resource for product details.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
    $nid = $request->query->get('nid');
    if (empty($nid)) {
      $param = ['nid'];

      return $common_utility->invalidData($param);
    }
    if (empty($common_utility->isValidNid($nid))) {
      return new JsonResponse('Node id does not exist.', 422, [], FALSE);
    }
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    $roles = $user_utility->getUserRoles(\Drupal::currentUser()->id());
    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'status' => 'int',
      'created' => 'int',
      'whyTheresOnlyOne' => 'string_replace',
      'demonstration' => 'string_replace',
      'benefits' => 'string_replace',
      'ifSheAsksShare' => 'string_replace',
      'customerQuestions' => 'string_replace',
    ];
    // Prepare redis key.
    $key = ':productDetails:' . $user_market . '_' . $roles[0] . '_' . $nid .
     '_' . \Drupal::currentUser()->getPreferredLangcode();
    // Prepare response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key,
    'product_detail', 'rest_export_product_detail', $data, $nid,
    'product_detail');

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
