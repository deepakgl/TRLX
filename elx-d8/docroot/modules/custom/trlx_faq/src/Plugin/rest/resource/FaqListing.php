<?php

namespace Drupal\trlx_faq\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
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
    $faqUtility = new FaqUtility();
    $_format = $request->get('_format');
    $language = $request->get('language');
    $brand_id = $request->get('brandId');

    // Check for valid _format type
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Check for valid brand id type
    if (isset($brand_id)) {
      $response = $commonUtility->validateIntegerValue($brand_id);
      if (!($response->getStatusCode() === Response::HTTP_OK)) {
        return $response;
      }
    }

    // Check for valid language code
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    $response = $faqUtility->getFaqContent($language, $brand_id);
    $result = [];
    $i = 0;
    foreach ($response as $value) {
      if ($value->brand_key_value === $brand_id) {
        $result[$i]['nid'] = $value->nid;
        $result[$i]['question'] = $value->question;
        $result[$i]['answer'] = $value->answer;
        $i++;
      }
      elseif (is_null($value->brand_key_value) && !isset($brand_id)) {
        $result[$i]['nid'] = $value->nid;
        $result[$i]['question'] = $value->question;
        $result[$i]['answer'] = $value->answer;
        $i++;
      }
    }

    // Pager array for faq listing.
    $pager = [
      "count" => count($result),
      "pages" => 0,
      "items_per_page" => count($result),
      "current_page" => 0,
      "next_page" => 0
    ];

    (count($result) == 0) ? $res =  $commonUtility->successResponse($result, 200) : $res =  $commonUtility->successResponse($result, 200, $pager);
    return $res;
  }

}
