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

  const INSIDER_CORNER = 'insiderCorner';

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
    if (!is_numeric($limit) || $limit < 0) {
      $err[] = 'limit';
    }
    $offset = $request->query->get('offset') ? $request->query->get('offset') : $offset_default;
    if (!is_numeric($offset) || $offset < 0) {
      $err[] = 'offset';
    }

    $errResponse = '';
    if (!empty($err)) {
      $errResponse = $this->errorResponse(t('Please provide only numeric value parameter(s): @params', ['@param' => implode(',', $err)]), Response::HTTP_UNPROCESSABLE_ENTITY);
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

    return $this->errorResponse(t('Following parameters is/are required: @reqParam', ['@reqParam' => $param]), Response::HTTP_BAD_REQUEST);
  }

  /**
   * Check if node data exists for requested parameters.
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
    if (!is_numeric($nid)) {
      return FALSE;
    }
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

  /**
   * Validate story section.
   *
   * @param string $name
   *   Section name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function validateStorySectionCode($name, $request) {
    if (!$request->query->has('section') || empty($name)) {
      return $this->errorResponse(t('Section parameter is required.'), Response::HTTP_BAD_REQUEST);
    }
    $section = ['trend', 'sellingTips', 'insiderCorner'];
    if (!preg_match("/^[A-Za-z\\- \']+$/", $name) || !in_array($name, $section)) {
      return $this->errorResponse(t('Please enter valid section name.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    return $this->successResponse();
  }

  /**
   * Validate story section.
   *
   * @param array $result
   *   Listing array.
   * @param int $limit
   *   Items to be displayed on single page.
   * @param int $offset
   *   Items to be removed from the listing.
   *
   * @return array
   *   Pager array.
   */
  public function listingPagination($result, $limit, $offset) {
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
      "next_page" => $currentPage,
    ];

    return [$result, $pager];
  }

  /**
   * Fetch Content Type to Section Mapping
   *
   * @param string $contentType
   *   Machine name of content type.
   *
   * @return array $sections
   *   Array of Section(s) the content type is mapped to.
   */
  public function getContentTypeSectionMapping($contentType) {
    $sections = [];
    if (empty($contentType)) {
      return $sections;
    }

    // Load module config.
    $config = \Drupal::config('trlx_utility.settings');
    // Fetch content type mapped sections from config.
    $contentTypeMappingArr = $config->get($contentType.'_sections');

    if (!empty($contentTypeMappingArr)) {
      return $contentTypeMappingArr;
    }

    return $sections;
  }

  /**
   * Function to Insider Corner Section Taxonomy Term.
   *
   * @return array $term
   *   Taxonomy Term array for Insider Corner.
   */
  function getInsiderCornerTerm() {
    // Load all Section taxonomy terms.
    $sectionTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('trlx_content_sections', 0, NULL, TRUE);

    if (!empty($sectionTerms)) {
      foreach ($sectionTerms as $tid => $term) {
        // Convert Object to Array.
        $term = $term->toArray();
        // Section key.
        $sectionKey = $term['field_content_section_key'][0]['value'];
        if (self::INSIDER_CORNER == $sectionKey) {
          return $term;
        }
      }
    }

    return [];
  }

}
