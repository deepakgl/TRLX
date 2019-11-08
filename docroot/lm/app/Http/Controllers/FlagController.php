<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Mysql\ContentModel;
use App\Model\Mysql\UserModel;
use App\Model\Elastic\ElasticUserModel;
use App\Model\Elastic\FlagModel;
use App\Traits\ApiResponser;

/**
 * Purpose of building this class is to set and fetch user flag.
 */
class FlagController extends Controller {

  use ApiResponser;

  private $elasticClient = NULL;
  private $uid = NULL;
  private $limit = NULL;
  private $offset = NULL;
  private $type = NULL;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct(Request $request) {
    $this->elasticClient = '';
    $this->uid = '';
    $this->limit = 10;
    $this->offset = 0;
    $this->type = 'all';
  }

  /**
   * Set flag.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return JSON
   *   Set user flag.
   */
  public function setFlag(Request $request) {
    global $_userData;
    $faq_config_data = ContentModel::getTrlxUtilityConfigValues();
    $validatedData = $this->validate($request, [
      'nid' => 'sometimes|required|positiveinteger|exists:node,nid',
      'flag' => 'required|likebookmarkflag',
      'status' => 'required|boolean',
      '_format' => 'required|format',
      'brandId' => 'sometimes|required|regex:/^[0-9]+$/|brandid',
    ]);
    $this->uid = $_userData->userId;
    $pageType = $request->get('type');
    if (isset($pageType) && empty($pageType)) {
      return $this->errorResponse('Valid page type is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    $brand_id = 0;
    // Get brand id if faq is part of brand section.
    if (isset($validatedData['brandId'])) {
      $brand_id = $validatedData['brandId'];
    }
    elseif (!empty($pageType) && !isset($validatedData['nid'])) {
      // Set brand id zero if faq not part of brand section.
      $brand_id = 0;
    }
    // Set node id value.
    if (isset($validatedData['nid'])) {
      $nid = $validatedData['nid'];
    }
    else {
      $nid = !empty($faq_config_data['faq_id']) ? (int) $faq_config_data['faq_id'] : 9999999;
      // Unique faq id summed with respective brand id.
      $nid = $nid + $brand_id;
    }
    if (empty($pageType) && !empty($nid)) {
      // Check node status.
      if (empty(ContentModel::getStatusByNid($nid))) {
        return $this->errorResponse('Node is not published.', Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }
    $flag = $validatedData['flag'];
    $status = $validatedData['status'];

    if (isset($pageType) && $pageType != 'faq') {
      return $this->errorResponse('Type param value must only be faq', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    $this->elasticClient = Helper::checkElasticClient();
    if (!$this->elasticClient) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $this->updateUserIndex($nid, $flag, $status);
    $this->updateNodeIndex($nid, $flag, $status);

    $lang = UserModel::getUserInfoByUid($this->uid, 'language');
    $language = isset($lang[0]) ? $lang[0]->language : 'en';
    header('Content-language: ' . $language);

    // Fetch node data from elastic.
    $response = FlagModel::fetchMultipleElasticNodeData([$nid], $this->elasticClient);
    // Check user flag status.
    $user_activities = UserActivitiesController::userFlagStatus($response, $this->uid, NULL, 'globalActivity');

    $message = $user_activities[0] + [
      'status' => TRUE,
      'message' => 'Flag successfully updated',
    ];

    return $this->successResponse($message, Response::HTTP_CREATED);
  }

  /**
   * Update user flag index.
   *
   * @param int $nid
   *   Node id.
   * @param string $flag
   *   Flag name.
   * @param bool $status
   *   Status value.
   *
   * @return json
   *   Update user flag index.
   */
  private function updateUserIndex($nid, $flag, $status) {
    $params = [];
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->elasticClient);
    // If index not exist, create new index.
    if (!$exist) {
      $params['body'] = ['uid' => $this->uid, $flag => [$nid]];
      $output = ElasticUserModel::createElasticUserIndex($params, $this->uid, $this->elasticClient);
    }
    else {
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
      if (!isset($response['_source'][$flag])) {
        $params['body'] = [
          'doc' => [
            'uid' => $this->uid,
            $flag => [],
          ],
          'doc_as_upsert' => TRUE,
        ];
        ElasticUserModel::updateElasticUserData($params, $this->uid, $this->elasticClient);
        $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
      }
      if ($status == 0) {
        if (($key = array_search($nid, $response['_source'][$flag])) !== FALSE) {
          unset($response['_source'][$flag][$key]);
        }
        $response['_source'][$flag] = array_values($response['_source'][$flag]);
      }
      elseif ($status == 1 && !in_array($nid, $response['_source'][$flag])) {
        $response['_source'][$flag][] = $nid;
      }
      $params['body'] = [
        'doc' => [
          'uid' => $this->uid,
          $flag => $response['_source'][$flag],
        ],
        'doc_as_upsert' => TRUE,
      ];
      $output = ElasticUserModel::updateElasticUserData($params, $this->uid, $this->elasticClient);
    }

    return Helper::jsonSuccess(TRUE);
  }

  /**
   * Update node flag index.
   *
   * @param int $nid
   *   Node id.
   * @param string $flag
   *   Flag name.
   * @param bool $status
   *   Status value.
   *
   * @return json
   *   Update node index.
   */
  private function updateNodeIndex($nid, $flag, $status) {
    $exist = FlagModel::checkElasticNodeIndex($nid, $this->elasticClient);
    if ($flag == 'bookmark') {
      $other_flag = 'like';
    }
    elseif ($flag == 'like') {
      $other_flag = 'bookmark';
    }
    // If index not exist, create new index.
    if (!$exist) {
      $flag_uid = [];
      if ($status == 1) {
        $flag_uid = [$this->uid];
      }
      $params['body'] = [
        $flag . '_by_user' => $flag_uid,
        $other_flag . '_by_user' => [],
      ];
      $output = FlagModel::createElasticNodeIndex($params, $nid, $this->elasticClient);
    }
    else {
      $response = FlagModel::fetchElasticNodeData($nid, $this->elasticClient);
      if ($status == 0) {
        if (($key = array_search($this->uid, $response['_source'][$flag . '_by_user'])) !== FALSE) {
          unset($response['_source'][$flag . '_by_user'][$key]);
        }
        $response['_source'][$flag . '_by_user'] = array_values($response['_source'][$flag . '_by_user']);
      }
      elseif ($status == 1 && !in_array($this->uid, $response['_source'][$flag . '_by_user'])) {
        $response['_source'][$flag . '_by_user'][] = $this->uid;
      }
      $params['body'] = [
        'doc' => [
          $flag . '_by_user' => $response['_source'][$flag . '_by_user'],
        ],
        'doc_as_upsert' => TRUE,
      ];
      $output = FlagModel::updateElasticNodeData($params, $nid, $this->elasticClient);
    }
    return Helper::jsonSuccess(TRUE);
  }

  /**
   * Get my bookmarks.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User bookmarks data.
   */
  public function myBookmarks(Request $request) {
    global $_userData;
    $this->uid = $_userData->userId;
    $validatedData = $this->validate($request, [
      'limit' => 'sometimes|required|integer|min:0',
      'offset' => 'sometimes|required|integer|min:0',
      '_format' => 'required|format',
      'language' => 'required|languagecode',
      'type' => 'required|bookmarklisttype',
    ]);
    try {
      $this->elasticClient = Helper::checkElasticClient();
    }
    catch (\Exception $e) {
      return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $img_url = getenv("SITE_IMAGE_URL");
    $lang = $validatedData['language'];
    $this->type = $validatedData['type'];
    $this->limit = isset($validatedData['limit']) ? $validatedData['limit'] : 10;
    $this->offset = isset($validatedData['offset']) ? $validatedData['offset'] : 0;
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->elasticClient);
    if ($exist) {
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
      if (empty($response['_source']['bookmark'])) {
        return $this->successResponse((Object) [], Response::HTTP_OK);
      }
      $results = $nid_user_activity = $bookmark_data = [];
      $pages = 0;
      $i = 0;
      // To get all brand keys.
      $brands_terms_ids = ContentModel::getBrandTermIds();
      $brand_keys = array_column($brands_terms_ids, 'field_brand_key_value');
      // To get faq page id.
      $faq_config_data = ContentModel::getTrlxUtilityConfigValues();
      $default_faq_id = !empty($faq_config_data['faq_id']) ? (int) $faq_config_data['faq_id'] : 9999999;
      // Array of sum of brands key and faq page id.
      $faq_ids = [$default_faq_id];
      foreach ($brand_keys as $value) {
        $faq_ids[] = (int) $value + (int) $default_faq_id;
      }
      // Bookmark ids in latest bookmarked order.
      $bookmark_ids = array_reverse($response['_source']['bookmark']);
      // TRLX section names.
      $sectionNames = ContentModel::getTrlxSectionNames();
      $brandinfo = ContentModel::getBrandTermIds();
      $bookmark_data = [];
      foreach ($bookmark_ids as $bookmark_id) {
        if (!in_array($bookmark_id, $faq_ids)) {
          $node_data = ContentModel::getNodeDataByNid($bookmark_id, $lang);
          if (!is_null($node_data)) {
            $node_type = ContentModel::getTypeByNid($bookmark_id);
            $bookmark_data[$i]['id'] = $node_data->nid;
            $bookmark_data[$i]['title'] = ($node_type->type == 'level_interactive_content') ? $node_data->field_headline_value : $node_data->field_display_title_value;
            $bookmark_data[$i]['brandKey'] = 0;
            $bookmark_data[$i]['brandName'] = "";
            $bookmark_data[$i]['sectionKey'] = "";
            $bookmark_data[$i]['sectionName'] = array_key_exists($node_type->type, $sectionNames) ? $sectionNames[$node_type->type] : "";
            $bookmark_data[$i]['pointValue'] = 0;
            $bookmark_data[$i]['imageSmall'] = "";
            $bookmark_data[$i]['imageMedium'] = "";
            $bookmark_data[$i]['imageLarge'] = "";
            $bookmark_data[$i]['faqId'] = 0;
            if ($node_type->type == 'level_interactive_content') {
              $bookmark_data[$i]['sectionKey'] = 'lesson';
            }
            if ($node_type->type == 'product_detail') {
              $bookmark_data[$i]['sectionKey'] = 'productDetail';
            }
            if ($node_type->type == 'brand_story') {
              $bookmark_data[$i]['sectionKey'] = 'brandStory';
            }
            if ($node_type->type == 'tools') {
              $bookmark_data[$i]['sectionKey'] = 'video';
            }
            if ($node_data->field_brands_target_id != NULL) {
              foreach ($brandinfo as $brand) {
                if ($brand['entity_id'] == $node_data->field_brands_target_id) {
                  $brand_key = (int) $brand['field_brand_key_value'];
                }
              }
              $bookmark_data[$i]['brandKey'] = $brand_key;
              $bookmark_data[$i]['brandName'] = ContentModel::getTermName([$node_data->field_brands_target_id])[0];
            }
            if ($node_data->field_content_section_target_id != NULL) {
              $bookmark_data[$i]['sectionKey'] = ContentModel::getContentSectionKeyByTid($node_data->field_content_section_target_id);
              $bookmark_data[$i]['sectionName'] = array_key_exists($bookmark_data[$i]['sectionKey'], $sectionNames) ? $sectionNames[$bookmark_data[$i]['sectionKey']] : "";
            }
            if ($node_data->field_point_value_value != NULL) {
              $bookmark_data[$i]['pointValue'] = (int) $node_data->field_point_value_value;
            }
            if ($node_data->field_hero_image_target_id != NULL && !in_array($node_type->type, [
              'product_detail',
              'tools',
              'brand_story',
            ])) {
              $bookmark_data[$i]['imageSmall'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_hero_image_target_id)[0];
              $bookmark_data[$i]['imageMedium'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_hero_image_target_id)[1];
              $bookmark_data[$i]['imageLarge'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_hero_image_target_id)[2];
            }
            if ($node_data->field_field_product_image_target_id != NULL  && $node_type->type == 'product_detail') {
              $bookmark_data[$i]['imageSmall'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_field_product_image_target_id)[0];
              $bookmark_data[$i]['imageMedium'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_field_product_image_target_id)[1];
              $bookmark_data[$i]['imageLarge'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_field_product_image_target_id)[2];
            }
            if ($node_data->field_tool_thumbnail_target_id != NULL && $node_type->type == 'tools') {
              $bookmark_data[$i]['imageSmall'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_tool_thumbnail_target_id)[0];
              $bookmark_data[$i]['imageMedium'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_tool_thumbnail_target_id)[1];
              $bookmark_data[$i]['imageLarge'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_tool_thumbnail_target_id)[2];
            }
            if ($node_data->field_featured_image_target_id != NULL  && $node_type->type == 'brand_story') {
              $bookmark_data[$i]['imageSmall'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_featured_image_target_id)[0];
              $bookmark_data[$i]['imageMedium'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_featured_image_target_id)[1];
              $bookmark_data[$i]['imageLarge'] = ContentModel::getBookmarkImageUrlByFid($node_data->field_featured_image_target_id)[2];
            }
          }
          $i++;
        }
        if (in_array($bookmark_id, $faq_ids)) {
          $brand_key = $bookmark_id - $default_faq_id;
          if ($brand_key > 0) {
            $brand_data = ContentModel::getBrandDataFromBrandKey($brand_key);
            if (!empty($brand_data)) {
              $bookmark_data[$i]['id'] = 0;
              $bookmark_data[$i]['title'] = mb_strtoupper($brand_data['name']) . ' CUSTOMER QUESTIONS';
              $bookmark_data[$i]['brandKey'] = $brand_key;
              $bookmark_data[$i]['brandName'] = $brand_data['name'];
              $bookmark_data[$i]['sectionKey'] = "faq";
              $bookmark_data[$i]['sectionName'] = $sectionNames['faq'];
              $bookmark_data[$i]['pointValue'] = (int) $faq_config_data['faq_points'];
              $bookmark_data[$i]['imageSmall'] = "";
              $bookmark_data[$i]['imageMedium'] = "";
              $bookmark_data[$i]['imageLarge'] = "";
              $bookmark_data[$i]['faqId'] = $bookmark_id;
            }
          }
          else {
            $bookmark_data[$i]['id'] = 0;
            $bookmark_data[$i]['title'] = 'HELP QUESTIONS';
            $bookmark_data[$i]['brandKey'] = 0;
            $bookmark_data[$i]['brandName'] = "";
            $bookmark_data[$i]['sectionKey'] = "helpFaq";
            $bookmark_data[$i]['sectionName'] = $sectionNames['faq'];
            $bookmark_data[$i]['pointValue'] = (int) $faq_config_data['faq_points'];
            $bookmark_data[$i]['imageSmall'] = "";
            $bookmark_data[$i]['imageMedium'] = "";
            $bookmark_data[$i]['imageLarge'] = "";
            $bookmark_data[$i]['faqId'] = $bookmark_id;
          }
          $i++;
        }
      }
    }
    else {
      return $this->successResponse((Object) [], Response::HTTP_OK);
    }
    // Filter bookmark data by type value.
    $filterBy = 'VIDEOS';
    $bookmark_data = array_values($bookmark_data);
    if ($this->type == 'video') {
      $bookmark_data = array_filter($bookmark_data, function ($var) use ($filterBy) {
          return ($var['sectionName'] == $filterBy);
      });
    }
    else {
      $bookmark_data = array_filter($bookmark_data, function ($var) use ($filterBy) {
          return ($var['sectionName'] != $filterBy);
      });
    }
    // Pagination logic.
    $total_count = count($bookmark_data) - $this->offset;
    $pages = ceil($total_count / $this->limit);
    $bookmark_data = array_slice($bookmark_data, $this->offset, $this->limit);
    $results['bookmark'] = $bookmark_data;
    $pager = [
      "count" => $total_count,
      "pages" => $pages,
      "items_per_page" => (int) $this->limit,
      "current_page" => 0,
      "next_page" => 0,
    ];
    header('Content-language: ' . $lang);
    if (empty($results['bookmark'])) {
      return $this->successResponse((Object) [], Response::HTTP_OK);
    }
    else {
      return $this->successResponse($results, Response::HTTP_OK, $pager);
    }
  }

  /**
   * User content view flag.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Allocate user points and badges.
   */
  public function contentViewFlag(Request $request) {
    global $_userData;
    $faq_config_data = ContentModel::getTrlxUtilityConfigValues();
    $validatedData = $this->validate($request, [
      'nid' => 'sometimes|required|positiveinteger|exists:node,nid',
      'brandId' => 'sometimes|required|positiveinteger|brandid',
      '_format' => 'required|format',
    ]);
    $this->uid = $_userData->userId;
    $pageType = $request->get('type');
    // Get brand id if faq is part of brand section.
    if (isset($validatedData['brandId'])) {
      $brand_id = $validatedData['brandId'];
    }
    elseif (!empty($pageType) && !isset($validatedData['nid'])) {
      // Set brand id zero if faq not part of brand section.
      $brand_id = 0;
    }
    $brand_id = isset($validatedData['brandId']) ? $validatedData['brandId'] : 0;
    // Set node id value.
    if (isset($validatedData['nid'])) {
      $nid = $validatedData['nid'];
    }
    else {
      $nid = !empty($faq_config_data['faq_id']) ? (int) $faq_config_data['faq_id'] : 9999999;
      // Unique faq id summed with respective brand id.
      $nid = $nid + $brand_id;
    }
    if (isset($pageType) && $pageType != 'faq') {
      return $this->errorResponse('Type param value must only be faq', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if (empty($pageType) && !empty($nid)) {
      // Check node status.
      if (empty(ContentModel::getStatusByNid($nid))) {
        return $this->errorResponse('Node is not published.', Response::HTTP_UNPROCESSABLE_ENTITY);
      }
      // Check node type.
      $type = ContentModel::getTypeByNid($nid);
    }
    // Check whether elastic client exists.
    $this->elasticClient = Helper::checkElasticClient();
    if (!$this->elasticClient) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    // Check whether user elastic index exists.
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->elasticClient);
    // If index not exist, create new index.
    if (!$exist) {
      $params['body'] = ['uid' => $this->uid];
      $output = ElasticUserModel::createElasticUserIndex($params, $this->uid, $this->elasticClient);
    }
    // Fetch user data from elastic index.
    $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    $content_type = empty($type->type) ? 'faq' : $type->type;
    if (isset($response['_source']['node_views_' . $content_type])) {
      $node_ids = $response['_source']['node_views_' . $content_type];
      if (in_array($nid, $node_ids)) {
        return $this->errorResponse('Node id already exist.', Response::HTTP_OK);
      }
      $node_ids[] = $nid;
      $params['body'] = [
        'doc' => [
          'node_views_' . $content_type => $node_ids,
        ],
        'doc_as_upsert' => TRUE,
      ];
      ElasticUserModel::updateElasticUserData($params, $this->uid, $this->elasticClient);
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    }
    else {
      $node_ids[] = $nid;
      $params['body'] = [
        'doc' => [
          'node_views_' . $content_type => $node_ids,
        ],
        'doc_as_upsert' => TRUE,
      ];
      ElasticUserModel::updateElasticUserData($params, $this->uid, $this->elasticClient);
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    }
    $lang = UserModel::getUserInfoByUid($this->uid, 'language');
    $language = isset($lang[0]) ? $lang[0]->language : 'en';
    // Get point value by node id.
    $point_value = ContentModel::getPointValueByNid($nid, $language);
    if ($point_value === 0 && $pageType == 'faq') {
      $point_value = !empty($faq_config_data['faq_points']) ? (int) $faq_config_data['faq_points'] : 50;
    }
    if (isset($response['_source']['total_points'])) {
      $response['_source']['total_points'] = $response['_source']['total_points'] + $point_value;
      // Prepare the elastic params and update the user index.
      $params['body'] = [
        'doc' => [
          'total_points' => $response['_source']['total_points'],
          'node_views_' . $content_type => $node_ids,
        ],
        'doc_as_upsert' => TRUE,
      ];
      $output = ElasticUserModel::updateElasticUserData($params, $this->uid, $this->elasticClient);
    }
    else {
      $params['body'] = [
        'doc' => [
          'total_points' => $point_value,
        ],
        'doc_as_upsert' => TRUE,
      ];
      $output = ElasticUserModel::updateElasticUserData($params, $this->uid, $this->elasticClient);
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    }

    return $this->successResponse([
      'nid' => $nid,
      'status' => TRUE,
      'message' => 'Successfully updated',
    ], Response::HTTP_OK);
  }

}
