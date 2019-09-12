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
   *   Response data.
   * @param int $code
   *   Response code.
   * @param array $pager
   *   Response if pager is there.
   * @param string $res
   *   Param if result is from view.
   *
   * @return Illuminate\Http\JsonResponse
   *   Success json response.
   */
  public function successResponse($data = [], $code = Response::HTTP_OK, $pager = [], $res = NULL) {
    $responseArr = $data;
    if (empty($res)) {
      $responseArr = ['results' => $data];
    }
    if (!empty($pager)) {
      $responseArr['pager'] = $pager;
    }
    return new JsonResponse($responseArr, $code);
  }

  /**
   * Build error responses.
   *
   * @param string|array $message
   *   Error response message.
   * @param int $code
   *   Response code.
   *
   * @return Illuminate\Http\JsonResponse
   *   Error json response.
   */
  public function errorResponse($message, $code) {
    return new JsonResponse(['message' => $message], $code);
  }

  /**
   * Validate language code.
   *
   * @param string $langcode
   *   Language code.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function validateLanguageCode($langcode, $request) {
    if (!$request->query->has('language') || empty($langcode)) {
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
   *   Format param.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function validateFormat($_format, $request) {
    if (!$request->query->has('_format') || empty($_format)) {
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
    $err = [];
    if (!is_numeric($limit)) {
      $err[] = 'limit';
    }
    $offset = $request->query->get('offset') ? $request->query->get('offset') : $offset_default;
    if (!is_numeric($offset)) {
      $err[] = 'offset';
    }

    $errResponse = '';
    if (!empty($err)) {
      $errResponse = $this->errorResponse(t('Please provide only numeric value parameter(s): ' . implode(',', $err)), Response::HTTP_BAD_REQUEST);
    }

    return [$limit, $offset, $errResponse];
  }

  /**
   * Check rest resource params.
   *
   * @param mixed $param
   *   Parameter name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
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
   * @param string $langcode
   *   Two characters long language code.
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
   * Fetch term name by tid.
   *
   * @param int $tid
   *   Term Id.
   *
   * @return string
   *   Term name.
   */
  public function getTermName($tid) {
    $lang = \Drupal::currentUser()->getPreferredLangcode();
    $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
    $query->fields('ttfd', ['name', 'tid', 'langcode']);
    $query->condition('ttfd.tid', $tid);
    $query->condition('ttfd.langcode', ['en', $lang], 'IN');
    $results = $query->execute()->fetchAll();
    $data = [];
    foreach ($results as $key => $result) {
      if (empty($data[$result->tid]) || $data[$result->tid]['lang'] == 'en') {
        $data[$result->tid] = [
          'name' => $result->name,
          'lang' => $result->langcode,
        ];
      }
    }
    $term_name = array_column($data, 'name');

    return $term_name[0];
  }

  /**
   * Validate integer value.
   *
   * @param int $num
   *   Integer number.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function validateIntegerValue($num) {
    if ((int) $num == $num && (int) $num > 0) {
      return $this->successResponse();
    }

    return $this->errorResponse(t('Please enter positive integer value.'), Response::HTTP_UNPROCESSABLE_ENTITY);
  }

}
