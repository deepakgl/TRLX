<?php

namespace Drupal\trlx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

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
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();

    // Required parameters
    $requiredParams = [
      '_format',
      'nid',
      'language',
    ];

    // Check for required parameters
    $missingParams = [];
    foreach ($requiredParams as $param) {
      $$param = $request->query->get($param);
      if (empty($$param)) {
        $missingParams[] = $param;
      }
    }

    // Report missing required parameters.
    if (!empty($missingParams)) {
      return $commonUtility->invalidData($missingParams);
    }

    // Checkfor valid _format type
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Checkfor valid language code
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    if (empty($commonUtility->isValidNid($nid, $language))) {
      return $commonUtility->errorResponse($this->t('Node id does not exist or requested language data is not available.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'body' => 'decode',
      'video' => 'append_host',
    ];
  
    // Prepare redis key.
    $key = ':productDetails:' . '___' . $nid . '_' . $language;

    // Prepare response.
    list($view_results, $status_code, ) = $entityUtility->fetchApiResult(
      $key,
      'product_detail',
      'product_details_rest_export',
      $data, ['nid' => $nid, 'language' => $language],
      'product_detail'
    );

    // Check for empty / no result from views
    if (empty($view_results)) {
      $status_code = Response::HTTP_NO_CONTENT;
    }
  
    return $commonUtility->successResponse($view_results, $status_code);
  }

}
