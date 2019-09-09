<?php

namespace Drupal\trlx_utility\Utility;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Purpose of this class is to build common object.
 */
class CommonUtility {

  /**
   * Build success response
   * 
   * @param  string|array $data
   * @param  int $code
   * @param  boolean $success
   * 
   * @return Illuminate\Http\JsonResponse
   */
  public function successResponse($data = [], $code = Response::HTTP_OK, $success = TRUE) {
    return new JsonResponse(['success' => $success, 'result' => $data, 'code' => $code], $code);
  }

  /**
   * Build error responses
   * 
   * @param  string|array $message
   * @param  int $code
   * 
   * @return Illuminate\Http\JsonResponse
   */
  public function errorResponse($message, $code) {
    return new JsonResponse(['success' => FALSE, 'result' => $message, 'code' => $code], $code);
  }

  /**
   * Validate language code
   * 
   * @param string $langcode
   * @param Request $request
   * 
   * @return JsonResponse
   */
  public function validateLanguageCode($langcode, $request) {
    if (!$request->query->has('language')) {
      return $this->errorResponse(t('Language parameter is required.'), Response::HTTP_BAD_REQUEST);
    }
    // Getting all the available languages.
    $languages = \Drupal::service('language_manager')->getStandardLanguageList();
    if (!array_key_exists(strtolower($langcode), $languages)) {
      return $this->errorResponse(t('Please enter valid language code.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    return $this->successResponse();
  }

  /**
   * Method to get image style based url.
   *
   * @param  string $image_style
   *   Image style machine name.
   * 
   * @param  string $path
   *   Image path.
   *
   * @return string
   *   Image URL.
   */
  public function getImageStyleBasedUrl($image_style, $path) {
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($image_style);
    $image_url = '';
    if ($style != NULL) {
      $image_url = $style->buildUrl($path);
    }
    return $image_url;
  }

}
