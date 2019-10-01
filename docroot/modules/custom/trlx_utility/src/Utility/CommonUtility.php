<?php

namespace Drupal\trlx_utility\Utility;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Purpose of this class is to build common object.
 */
class CommonUtility {

  const INSIDER_CORNER = 'insiderCorner';
  const TREND = 'trend';
  const SELLING_TIPS = 'sellingTips';

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
   * @param string $type
   *   Content Type/Type of node.
   *
   * @return bool
   *   True or false.
   */
  public function isValidNid($nid, $langcode = 'en', $type = '') {
    if (!is_numeric($nid)) {
      return FALSE;
    }
    $query = \Drupal::database();
    $query = $query->select('node_field_data', 'n');
    $query->fields('n', ['nid'])
      ->condition('n.nid', $nid, '=')
      ->condition('n.langcode', $langcode, '=')
      ->condition('n.status', 1, '=');
    if (!empty($type)) {
      $query->condition('n.type', $type);
    }
    $query->range(0, 1);
    $result = $query->execute()->fetchAll();
    if (empty($result)) {
      global $base_url;
      $request_uri = $base_url . \Drupal::request()->getRequestUri();
      $logger = \Drupal::service('logger.stdout');
      $logger->log(RfcLogLevel::ERROR, 'Node Id @nid does not exist in database or is invalid.', [
        '@nid' => $nid,
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
   * Validate positive value.
   *
   * @param int $num
   *   Integer number.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function validatePositiveValue($num) {
    if (preg_match("/^[0-9]\d*$/", $num)) {
      return $this->successResponse();
    }

    return $this->errorResponse(t('Please enter positive value.'), Response::HTTP_UNPROCESSABLE_ENTITY);
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
    $section = [self::TREND, self::SELLING_TIPS, self::INSIDER_CORNER];
    if (!preg_match("/^[A-Za-z\\- \']+$/", $name) || !in_array($name, $section)) {
      return $this->errorResponse(t('Please enter valid section name.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    return $this->successResponse();
  }

  /**
   * Check if term id exists.
   *
   * @param int $tid
   *   Term id.
   *
   * @return bool
   *   True or false.
   */
  public function isValidTid($tid) {
    if (!is_numeric($tid)) {
      return FALSE;
    }
    $query = \Drupal::database();
    $query = $query->select('taxonomy_term_data', 't');
    $query->fields('t', ['tid'])
      ->condition('t.tid', $tid, '=')
      ->range(0, 1);
    $result = $query->execute()->fetchAll();
    if (empty($result)) {
      global $base_url;
      $request_uri = $base_url . \Drupal::request()->getRequestUri();
      $logger = \Drupal::service('logger.stdout');
      $logger->log(RfcLogLevel::ERROR, 'Term Id @tid does not exist in database.', [
        '@tid' => $tid,
        'user' => \Drupal::currentUser(),
        'request_uri' => $request_uri,
        'data' => $tid,
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Fetch Content Type to Section Mapping.
   *
   * @param string $contentType
   *   Machine name of content type.
   *
   * @return array
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
    $contentTypeMappingArr = $config->get($contentType . '_sections');

    if (!empty($contentTypeMappingArr)) {
      return $contentTypeMappingArr;
    }

    return $sections;
  }

  /**
   * Function to Insider Corner Section Taxonomy Term.
   *
   * @return array
   *   Taxonomy Term array for Insider Corner.
   */
  public function getInsiderCornerTerm() {
    // Load all Section taxonomy terms.
    $sectionTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('trlx_content_sections', 0, NULL, TRUE);

    if (!empty($sectionTerms)) {
      foreach ($sectionTerms as $delta => $term) {
        // Convert Object to Array.
        $term = $term->toArray();
        // Section key.
        $sectionKey = $term['field_content_section_key'][0]['value'];
        if (self::INSIDER_CORNER == $sectionKey) {
          return [$term['tid'][0]['value'], $term];
        }
      }
    }

    return ['', ''];
  }

  /**
   * Fetch social media handles for Insider Corner section.
   *
   * @param int $nid
   *   Nid to which the social media handle is associated.
   *
   * @return array
   *   Array objects for social media handles.
   */
  public function getSocialMediaHandles(int $nid) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $paragraph = $node->field_social_media_handles->getValue();

    $socialMediaHandles = [];
    if (!empty($paragraph)) {
      // Loop through the result set.
      foreach ($paragraph as $element) {
        $socialMediaPara = Paragraph::load($element['target_id']);
        // Social Media Title.
        $title = $socialMediaPara->field_social_media_title->getValue()[0]['value'];
        // Social Media Handle.
        $handle = $socialMediaPara->field_social_media_handle->getValue()[0]['value'];
        $socialMediaHandles[] = ['title' => $title, 'handle' => $handle];
      }
    }

    return $socialMediaHandles;
  }

  /**
   * Fetch aggregate Point Value for each Learning Level.
   *
   * @param int $levelTermId
   *   Learning Level Term Id.
   *
   * @return int
   *   Aggregate Point Value.
   */
  public function getLearningLevelPointValue($levelTermId) {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'field_learning_category' => $levelTermId,
    ]);

    $pointValue = 0;
    if (!empty($nodes)) {
      foreach ($nodes as $nid => $node) {
        $pointValue += $node->get('field_point_value')->value;
      }
    }

    return $pointValue;
  }

}
