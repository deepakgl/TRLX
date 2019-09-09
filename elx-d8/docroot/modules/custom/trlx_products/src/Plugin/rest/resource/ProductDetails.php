<?php

namespace Drupal\trlx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\Component\Serialization\Json;

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
    // $user_utility = new UserUtility(); // fixMe
    $this->commonUtility = new CommonUtility();
    $this->entityUtility = new EntityUtility();
    $nid = $request->query->get('nid');
    $language = $request->query->get('language');

    // Check for empty language
    if (empty($language)) {
      $param = ['language'];

      return $this->commonUtility->invalidData($param);
    }
    // Checkfor valid language code
    $response = $this->commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
  
    if (empty($nid)) {
      $param = ['nid'];
      return $this->commonUtility->invalidData($param);
    }
    if (empty($this->commonUtility->isValidNid($nid, $language))) {
      return $this->commonUtility->errorResponse($this->t('Node id does not exist or requested language data is not available.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'body' => 'string_replace',
      'video' => 'append_host',
    ];
  
    // Prepare redis key.
    $key = ':productDetails:' . '___' . $nid . '_' . $language;

    // Prepare response.
    list($view_results, $status_code, ) = $this->entityUtility->fetchApiResult(
      $key,
      'product_detail',
      'product_details_rest_export',
      $data, ['nid' => $nid, 'language' => $language],
      'product_detail'
    );

    // Check for empty / no result from views
    if (empty($view_results)) {
      return $this->commonUtility->errorResponse($this->t('No result found.'), $status_code);
    }
  
    return $this->commonUtility->successResponse($view_results, $status_code);
  }

}
