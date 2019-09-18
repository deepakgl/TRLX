<?php

namespace Drupal\trlx_faq\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_faq\Utility\FaqUtility;

/**
 * Provides a FAQ listing resource.
 *
 * @RestResource(
 *   id = "faq_listing",
 *   label = @Translation("FAQ Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/faqListing"
 *   }
 * )
 */
class FaqListing extends ResourceBase {

  /**
   * Rest resource for listing of FAQs.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();

    $faqUtility = new FaqUtility();
    $_format = $request->get('_format');
    $language = $request->get('language');
    $brand_id = $request->get('brandId');

    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Check for valid brand id type.
    if (isset($brand_id)) {
      $response = $commonUtility->validateIntegerValue($brand_id);
      if (!($response->getStatusCode() === Response::HTTP_OK)) {
        return $response;
      }
      // Validation for valid brand key
      // Prepare view response for valid brand key.
      list($view_results, $status_code) = $entityUtility->fetchApiResult(
        '',
        'brand_key_validation',
        'rest_export_brand_key_validation',
        '',
        $brand_id
      );

      // Check for empty resultset.
      if (empty($view_results)) {
        return $commonUtility->errorResponse($this->t('Brand Id (@brandId) does not exist.', ['@brandId' => $brand_id]), Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Check for valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    $response = $faqUtility->getFaqContent($language, $brand_id);
    $result = [];
    $i = 0;
    foreach ($response as $value) {
      if ($value->brand_key_value === $brand_id) {
        $result[$i]['nid'] = (int) $value->nid;
        $result[$i]['question'] = $value->question;
        $result[$i]['answer'] = $value->answer;
        $i++;
      }
      elseif (is_null($value->brand_key_value) && !isset($brand_id)) {
        $result[$i]['nid'] = (int) $value->nid;
        $result[$i]['question'] = $value->question;
        $result[$i]['answer'] = $value->answer;
        $i++;
      }
    }

    $page = 1;
    // Total items in array.
    $total = count($result);
    $limit = (int) $limit;
    // Calculate total pages.
    $totalPages = ceil($total / $limit);
    $page = max($page, 1);
    $currentPage = $page - 1;
    if ($offset < 0) {
      $offset = 0;
    }
    if (isset($offset)) {
      $total = count($result) - $offset;
      $totalPages = ceil($total / $limit);
      $page = max($page, 1);
      $currentPage = $page - 1;
    }
    $result = array_slice($result, $offset, $limit);

    // Pager array for faq listing.
    $pager = [
      "count" => (int) $total,
      "pages" => (int) $totalPages,
      "items_per_page" => $limit,
      "current_page" => $currentPage,
      "next_page" => $currentPage + 1,
    ];

    (count($result) == 0) ? $res = $commonUtility->successResponse($result, 200) : $res = $commonUtility->successResponse($result, 200, $pager);
    return $res;
  }

}
