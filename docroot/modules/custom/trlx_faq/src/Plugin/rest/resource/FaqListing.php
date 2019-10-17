<?php

namespace Drupal\trlx_faq\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;

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

    $_format = $request->get('_format');
    $language = $request->get('language');
    $brand_id = $request->get('brandId');

    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
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

    // Prepare array of keys for alteration in response.
    $data = [
      'nid' => 'int',
      'question' => 'decode',
      'answer' => 'string_replace',
    ];
    // Get FAQ default id.
    $config = \Drupal::config('trlx_utility.settings');
    $faqId = $config->get('faq_id') == 0 ? 9999999 : (int) $config->get('faq_id');
    $faqPointValue = $config->get('faq_points') == 0 ? 50 : (int) $config->get('faq_points');

    // To show the brand FAQs.
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

      // Prepare view response.
      list($view_results, $status_code) = $entityUtility->fetchApiResult(
        '',
        'trlx_faq_listing',
        'rest_export_faq_listing',
        $data,
        ['brand' => $brand_id, 'language' => $language]
      );

      // Check for empty / no result from views.
      if (empty($view_results)) {
        return $commonUtility->successResponse([], Response::HTTP_OK);
      }
      return $commonUtility->successResponse($view_results['results'], $status_code, $view_results['pager'], NULL, $faqId, $faqPointValue);
    }
    // To show the global help FAQs.
    // Prepare view response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      'trlx_faq_listing',
      'rest_export_global_faq_listing',
      $data,
      ['language' => $language]
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }
    return $commonUtility->successResponse($view_results['results'], $status_code, $view_results['pager'], NULL, $faqId, $faqPointValue);
  }

}
