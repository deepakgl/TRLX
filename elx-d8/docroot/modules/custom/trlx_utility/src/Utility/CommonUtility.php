<?php

namespace Drupal\trlx_utility\Utility;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\RfcLogLevel;

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
    $logger->log(RfcLogLevel::ERROR, 'Following params required: ' . $param, [
      'user' => \Drupal::currentUser(),
      'request_uri' => $request_uri,
      'data' => $param,
    ]);

    return $this->errorResponse(t('Following params required: ' . $param), Response::HTTP_BAD_REQUEST);
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

}
