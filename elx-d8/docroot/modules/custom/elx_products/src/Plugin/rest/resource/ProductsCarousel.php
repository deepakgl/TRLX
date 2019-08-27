<?php

namespace Drupal\elx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\elx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a products carousel resource.
 *
 * @RestResource(
 *   id = "products_carousel",
 *   label = @Translation("Products Carousel"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/productsCarousel"
 *   }
 * )
 */
class ProductsCarousel extends ResourceBase {

  /**
   * Rest resource for product carousel.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   resource response.
   */
  public function get(Request $request) {
    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    list($limit, $offset) = $common_utility->getPagerParam($request);
    // Prepare array of keys for alteration in response.
    $data = ['title', 'image'];
    // Prepare redis key.
    $key = ':productsCarousel:' . \Drupal::currentUser()
      ->getPreferredLangcode() . '_' . $limit . '_' . $offset;
    // Prepare response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key, 'products_carousel', 'rest_export_products_carousel', $data, NULL, 'products_carousel');

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
