<?php

namespace Drupal\trlx_utility\Utility;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Purpose of this class is to build common object.
 */
class CommonUtility {

  /**
   * Build success response.
   *
   * @param string|array $data
   * @param int $code
   * @param bool $success
   *
   * @return Illuminate\Http\JsonResponse
   */
  public function successResponse($data = [], $code = Response::HTTP_OK, $success = TRUE, $pager = []) {
    $responseArr = ['result' => $data];
    if (!empty($pager)) {
      $responseArr['pager'] = $pager;
    }
    return new JsonResponse($responseArr, $code);
  }

  /**
   * Build error responses.
   *
   * @param string|array $message
   * @param int $code
   *
   * @return Illuminate\Http\JsonResponse
   */
  public function errorResponse($message, $code) {
    return new JsonResponse(['message' => $message], $code);
  }

  /**
   * Validate language code.
   *
   * @param string $langcode
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
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
   * Validate _format parameter.
   *
   * @param string $_format
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function validateFormat($_format, $request) {
    if (!$request->query->has('_format')) {
      return $this->errorResponse(t('"_format" parameter is required.'), Response::HTTP_BAD_REQUEST);
    }

    if (!in_array(strtolower($_format), ['json'])) {
      return $this->errorResponse(t('Please enter valid format.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    return $this->successResponse();
  }

  /**
   * Method to get image style based url.
   *
   * @param string $image_style
   *   Image style machine name.
   *
   * @param string $path
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

  /**
   * Set the limit and pager.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   * @param int $limit_default
   *   Limit.
   * @param int $offset_default
   *   Offset.
   *
   * @return array
   *   Limit and offset.
   */
  public function getPagerParam(Request $request, $limit_default = 10, $offset_default = 0) {
    $limit = $request->query->get('limit') ? $request->query->get('limit') : $limit_default;
    $offset = $request->query->get('offset') ? $request->query->get('offset') : $offset_default;

    return [$limit, $offset];
  }

  /**
   * Check rest resource params.
   *
   * @param mixed $param
   *   Parameter name.
   *
   * @return JsonResponse
   *   Following params required.
   */
  public function invalidData($param = []) {
    global $base_url;
    $request_uri = $base_url . \Drupal::request()->getRequestUri();
    $param = implode(',', $param);
    $logger = \Drupal::service('logger.stdout');
    $logger->log(RfcLogLevel::ERROR, 'Following parameters is/are required: ' . $param, [
      'user' => \Drupal::currentUser(),
      'request_uri' => $request_uri,
      'data' => $param,
    ]);

    return $this->errorResponse(t('Following parameters is/are required: ' . $param), Response::HTTP_BAD_REQUEST);
  }

  /**
   * Check if node id exists, is published
   * & requested language is available for that nid.
   *
   * @param int $nid
   *   Node id.
   *
   * @param string $langcode
   *   Two characters long language code
   *
   * @return bool
   *   True or false.
   */
  public function isValidNid($nid, $langcode) {
    $query = \Drupal::database();
    $query = $query->select('node_field_data', 'n');
    $query->fields('n', ['nid'])
      ->condition('n.nid', $nid, '=')
      ->condition('n.langcode', $langcode, '=')
      ->condition('n.status', 1, '=')
      ->range(0, 1);
    $result = $query->execute()->fetchAll();
    if (empty($result)) {
      global $base_url;
      $request_uri = $base_url . \Drupal::request()->getRequestUri();
      $logger = \Drupal::service('logger.stdout');
      $logger->log(RfcLogLevel::ERROR, 'Node Id @nid does not exist in database or is invalid.', [
        '@nid' => $nid,
        'user' => \Drupal::currentUser(),
        'request_uri' => $request_uri,
        'data' => $nid,
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate response format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function validateResponseFormat($request) {
    if (!$request->query->has('_format')) {
      return $this->errorResponse(t('Format parameter is required.'), Response::HTTP_BAD_REQUEST);
    }
    return $this->successResponse();
  }

}
