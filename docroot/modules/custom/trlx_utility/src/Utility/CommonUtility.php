<?php

namespace Drupal\trlx_utility\Utility;

use Mockery\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Elasticsearch\ClientBuilder;

/**
 * Purpose of this class is to build common object.
 */
class CommonUtility {

  const INSIDER_CORNER = 'insiderCorner';
  const TREND = 'trend';
  const SELLING_TIPS = 'sellingTips';
  const CONSUMER = 'consumer';
  const BRAND_LEVEL = 'brandLevel';

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
   * @param array $faq_id
   *   Param if faq id is there.
   * @param array $faq_point_value
   *   Param if faq point value is there.
   *
   * @return Illuminate\Http\JsonResponse
   *   Success json response.
   */
  public function successResponse($data = [], $code = Response::HTTP_OK, $pager = [], $res = NULL, $faq_id = [], $faq_point_value = [], $extraData = []) {
    $responseArr = $data;
    if (empty($res)) {
      $responseArr = ['results' => $data];
    }
    if (!empty($pager)) {
      $responseArr['pager'] = $pager;
    }
    if (!empty($faq_id)) {
      $responseArr['faqId'] = $faq_id;
    }
    if (!empty($faq_point_value)) {
      $responseArr['pointValue'] = $faq_point_value;
    }
    if (!empty($extraData)) {
      $responseArr = array_merge($responseArr, $extraData);
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
    try {
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($image_style);
      $image_url = '';
      if ($style != NULL) {
        $image_url = $style->buildUrl($path);
      }
      // Fetch Image url
      return $image_url;
    } catch (\Exception $e) {
      // Return False
      return FALSE;
    }
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

    try {
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
    } catch (\Exception $e) {
      $result = '';
    }

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
  public function getTermName($tid, $lang = NULL) {
    try {
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
    } catch (\Exception $e) {
      return FALSE;
    }
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
    $section = [
      self::TREND,
      self::SELLING_TIPS,
      self::INSIDER_CORNER,
      self::CONSUMER,
    ];
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
   * @param int $vid
   *   Vocabulary id.
   *
   * @return bool
   *   True or false.
   */
  public function isValidTid($tid, $vid = NULL) {
    if (!is_numeric($tid)) {
      return FALSE;
    }

    try {
      $query = \Drupal::database();
      $query = $query->select('taxonomy_term_data', 't');
      $query->fields('t', ['tid']);
      $query->condition('t.tid', $tid, '=');
      if (!is_null($vid)) {
        $query->condition('t.vid', $vid, '=');
      }
      $query->range(0, 1);
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
    } catch (\Exception $e) {
      // Return FALSE;
      return FALSE;
    }

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
   * Function to fetch Section Taxonomy Term.
   *
   * @param string $sectionKey
   *   Section key of the section.
   *
   * @return array
   *   Taxonomy Term array for Insider Corner.
   */
  public function getSectionTerm(string $sectionKey) {
    // Load all Section taxonomy terms.
    $sectionTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('trlx_content_sections', 0, NULL, TRUE);

    if (!empty($sectionTerms)) {
      foreach ($sectionTerms as $delta => $term) {
        // Convert Object to Array.
        $term = $term->toArray();
        // Section key.
        $termSectionKey = $term['field_content_section_key'][0]['value'];
        if ($sectionKey == $termSectionKey) {
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
    try {
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
    } catch (\Exception $e) {
      // Return False
      return FALSE;
    }
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
    try {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
        'field_learning_category' => $levelTermId,
      ]);

      $pointValue = 0;
      if (!empty($nodes)) {
        foreach ($nodes as $nid => $node) {
          if ($node->get('field_learning_category')->target_id == $levelTermId) {
            $pointValue += $node->get('field_point_value')->value;
          }
        }
      }

      return $pointValue;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Set Entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   EntityInterface object.
   * @param string $field
   *   Field name.
   * @param array $styles
   *   Image styles array for the field in the entity type.
   */
  public function setMediaEntity(EntityInterface $entity, string $field, array $styles) {
    if ($entity->hasField($field)) {
      if (!$entity->$field->isEmpty() && !empty($styles)) {
        if (in_array($entity->bundle(), ['user', 'brands'])) {
          $file_id = $entity->get($field)->getValue()[0]['target_id'];
          $media_entity = ($file_id) ? File::Load($file_id) : '';
          $path = $media_entity->getFileUri();
        }
        else {
          $file_id = $entity->$field->getString();
          $media_entity = ($file_id) ? Media::load($file_id) : '';
          $path = $media_entity->field_media_image->entity->getFileUri();
        }
        foreach ($styles as $img_style) {
          $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($img_style);
          $build_uri = $style->buildUri($path);
          $style->createDerivative($path, $build_uri);
        }
      }
    }
  }

  /**
   * Fetch like count.
   *
   * @param mixed $nids
   *   Node ids.
   *
   * @return json
   *   Like count.
   */
  public function likeCount($nids) {
    try {
      global $_userData;
      $uid = $_userData->userId;
      $client = self::setElasticConnectivity();
      $env = \Drupal::config('elx_utility.settings')->get('elx_environment');

      $params['index'] = $env . '_node_data';
      $params['type'] = 'node';
      $params['body'] = ['ids' => $nids];
      $response = $client->mget($params);
      foreach ($response['docs'] as $key => $value) {
        // If data found in elastic.
        if ($value['found'] == 1) {
          if (array_key_exists('like_by_user', $value['_source'])) {
            $like_count[] = (int) count($value['_source']['like_by_user']);
          }
        }
      }
      return array_sum($like_count);
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Set Elastic Connectivity.
   *
   * @return json
   *   Elastic Client.
   */
  public function setElasticConnectivity() {
    try {
      // Create elastic connection.
      $hosts = [
        [
          'host' => \Drupal::config('elx_utility.settings')
            ->get('elastic_host'),
          'port' => \Drupal::config('elx_utility.settings')
            ->get('elastic_port'),
          'scheme' => \Drupal::config('elx_utility.settings')
            ->get('elastic_scheme'),
          'user' => \Drupal::config('elx_utility.settings')
            ->get('elastic_username'),
          'pass' => \Drupal::config('elx_utility.settings')
            ->get('elastic_password'),
        ],
      ];
      $client = ClientBuilder::create()->setHosts($hosts)->build();

      return $client;
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Function to fetch Section tid by section key.
   *
   * @param int $sectionTid
   *   Term id of the section.
   *
   * @return string
   *   Section key associated with the term id.
   */
  public function getSectionKeyByTermId(int $sectionTid) {
    // Load all Section taxonomy terms.
    try {
      $sectionTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('trlx_content_sections', 0, NULL, TRUE);
    } catch (\Exception $e) {
      $sectionTerms = '';
    }

    $sectionKey = '';
    if (!empty($sectionTerms)) {
      foreach ($sectionTerms as $delta => $term) {
        // Convert Object to Array.
        $term = $term->toArray();

        if ($sectionTid == $term['tid'][0]['value']) {
          $sectionKey = $term['field_content_section_key'][0]['value'];
        }
      }
    }

    return $sectionKey;
  }

  /**
   * Function to fetch brand key by brand id.
   *
   * @param int $brandTid
   *   Term id of the brand.
   *
   * @return int
   *   Brand key associated with the term id.
   */
  public function getBrandKeyByTermId(int $brandTid) {
    // Load all Section taxonomy terms.
    try {
      $brandTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('brands', 0, NULL, TRUE);
    } catch (\Exception $e) {
      $brandTerms = '';
    }

    $brandKey = '';
    if (!empty($brandTerms)) {
      foreach ($brandTerms as $delta => $term) {
        // Convert Object to Array.
        $term = $term->toArray();

        if ($brandTid == $term['tid'][0]['value']) {
          $brandKey = $term['field_brand_key'][0]['value'];
        }
      }
    }
    return (int) $brandKey;
  }

  /**
   * Method to get node data.
   *
   * @param int $nid
   *   Node id.
   * @param string $language
   *   Language code.
   *
   * @return mixed
   *   Node data.
   */
  public function getNodeData($nid, $language) {
    // Load node by nid and language code.
    try {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node->hasTranslation($language)) {
        return $node->getTranslation($language);
      }
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Method to load image style.
   *
   * @param string $style_name
   *   Style name.
   * @param string $file_uri
   *   File uri.
   *
   * @return mixed
   *   Image style url.
   */
  public function loadImageStyle($style_name, $file_uri) {
    try {
      $image_style = \Drupal::entityTypeManager()->getStorage('image_style')->load($style_name);
      $result = $image_style->buildUrl($file_uri);
      // Fetch uri
      return $result;
    } catch (\Exception $e) {
      // Return false
      return FALSE;
    }
  }

  /**
   * Function to fetch Product Carousel from nid.
   *
   * @param int $nid
   *   Node Id.
   * @param string $language
   *   Language code.
   *
   * @return array
   *   Array of product carousel data.
   */
  public function fetchProductCarouselByNodeId(int $nid, string $language) {
    $carouselData = [];
    if (!is_numeric($nid)) {
      return $carouselData;
    }

    try {
      // Query to fetch respective Paragraph Entities.
      $query = \Drupal::database();
      $query = $query->select('node__field_product_carousel', 'fpc');
      $query->join('paragraphs_item_field_data', 'pifd', 'pifd.id = fpc.field_product_carousel_target_id');
      $query->condition('fpc.entity_id', $nid);
      $query->condition('fpc.langcode', $language);
      $query->condition('pifd.type', 'product_carousel');
      $query->condition('pifd.langcode', $language);
      $query->condition('pifd.status', 1);
      $query->condition('pifd.parent_type', 'node');
      $query->fields('fpc', ['field_product_carousel_target_id']);
      $result = $query->execute()->fetchAllAssoc('field_product_carousel_target_id');
    } catch (\Exception $e) {
      $result = '';
    }

    if (!empty($result)) {
      $entityUtility = new EntityUtility();

      // Fetch paragraph data from views.
      list($view_results) = $entityUtility->fetchApiResult(
        '',
        'paragraph_product_carousel',
        'rest_export_paragraph_product_carousel',
        ['title' => 'decode', 'subTitle' => 'decode'],
        ['id' => implode(",", array_keys($result)), 'language' => $language]
      );
    }

    if (!empty($view_results['results'])) {
      $carouselData = $view_results['results'];
    }

    return $carouselData;
  }

  /**
   * Method to get listing images.
   *
   * @param string $section
   *   expects parammeter of section key of taxonomy
   *
   * @return array
   *   Listing Images
   */
  public function getListingImg($section) {
    try {
      // Custom query to get image name based on section_key.
      $query = \Drupal::database()->select('taxonomy_term_field_data', 't1');
      $query->fields('t1');
      $query->join('taxonomy_term__field_section', 't2', 't1.tid = t2.entity_id');
      $query->fields('t2');
      $query->join('taxonomy_term__field_content_section_key', 't3', 't2.field_section_target_id = t3.entity_id');
      $query->fields('t3');
      $query->condition('t1.vid', "listing_image", "=");
      $query->condition('t1.status', 1, "=");
      $query->range(0, 1);
      $query->condition('t3.field_content_section_key_value', $section, "=");
      $query->orderBy('t1.content_translation_created', 'DESC');
      $query->join('taxonomy_term__field_hero_image', 't4', 't1.tid = t4.entity_id');
      $query->fields('t4');
      $query->join('media__field_media_image', 't5', 't4.field_hero_image_target_id = t5.entity_id');
      $query->fields('t5');
      $query->join('file_managed', 't6', 't6.fid = t5.field_media_image_target_id');
      $query->fields('t6');
      $entries = $query->execute()->fetchAll();

      $result = [];
      if (!empty($entries)) {
        $result['image'] = array_shift($entries)->uri;
      }
      else {
        $result['image'] = '';
      }

      return $result;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

}
