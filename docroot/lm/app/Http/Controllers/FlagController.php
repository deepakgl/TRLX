<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Mysql\ContentModel;
use App\Model\Mysql\UserModel;
use App\Model\Elastic\ElasticUserModel;
use App\Model\Elastic\FlagModel;
use App\Model\Elastic\BadgeModel;
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
    $faq_config_data = ContentModel::getFaqValues();
    $validatedData = $this->validate($request, [
      'nid' => 'sometimes|required|positiveinteger|exists:node,nid',
      'flag' => 'required|likebookmarkflag',
      'status' => 'required|boolean',
      '_format' => 'required|format',
      'brandId' => 'sometimes|positiveinteger|brandid',
    ]);
    $this->uid = $_userData->userId;
    $pageType = $request->get('type');
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
   *   User bookmark and favorites data.
   */
  public function myFlags(Request $request) {
    $this->uid = Helper::getJtiToken($request);
    try {
      $this->elasticClient = Helper::checkElasticClient();
    }
    catch (\Exception $e) {
      return Helper::jsonError($e->getMessage(), 400);
    }
    $lang = UserModel::getUserInfoByUid($this->uid, 'language');
    $img_url = getenv("SITE_IMAGE_URL");
    if (!$this->uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    $this->limit = !empty($request->input('limit')) ? $request->input('limit') : $this->limit;
    $this->offset = !empty($request->input('offset')) ? $request->input('offset') : $this->offset;
    $this->type = !empty($request->input('contentType')) ? $request->input('contentType') : $this->type;
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->elasticClient);
    if ($exist) {
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
      if (empty($response['_source']['bookmarks'])) {
        return new Response(NULL, 204);
      }
      $results = $nid_user_activity = $bookmark_data = [];
      $pages = 0;
      if ($this->type != 'all') {
        foreach ($response['_source']['bookmarks'] as $key => $value) {
          $type = ContentModel::getTypeByLang($value, $lang[0]->language);
          if (!empty($type) && $type->type == $this->type) {
            array_push($bookmark_data, $value);
          }
        }
      }
      else {
        $bookmark_data = $response['_source']['bookmarks'];
      }
      $bookmark_data_by_type = [];
      foreach ($bookmark_data as $key => $values) {
        $this->type = ContentModel::getTypeByLang($values, $lang[0]->language);
        if (!empty($this->type)) {
          array_push($bookmark_data_by_type, $values);
        }
      }
      if (empty(array_slice($bookmark_data_by_type, $this->offset, $this->limit))) {
        return new Response(NULL, 204);
      }
      foreach (array_slice($bookmark_data_by_type, $this->offset, $this->limit) as $key => $value) {
        $type = ContentModel::getTypeByLang($value, $lang[0]->language);
        $lang = UserModel::getUserInfoByUid($this->uid, 'language');
        $point_value = ContentModel::getPointValueByNid($value, $lang[0]->language);
        $body = $title = $image_id = $url = '';
        if (isset($type->type)) {
          if ($type->type == 'product_detail') {
            list($title, $image_id, $body, $sub_title) = ContentModel::getProductsContent($value, $lang[0]->language);
          }
          elseif ($type->type == 'tools' || $type->type == 'tools-pdf') {
            $sub_title = '';
            list($title, $image_id, $body) = ContentModel::getToolsContent($value, $lang[0]->language);
          }
          elseif ($type->type == 'stories') {
            list($title, $image_id, $body, $sub_title) = ContentModel::getStoriesContent($value, $lang[0]->language);
          }
          elseif ($type->type == 'level_interactive_content') {
            list($title, $image_id, $id) = ContentModel::getLevelContent($value, $lang[0]->language);
            if (!empty($id)) {
              list($body, $sub_title) = ContentModel::getLevelParagraphById($id, $lang[0]->language);
            }
          }
          if (!empty($image_id)) {
            $url = ContentModel::getImageUrlByFid($image_id);
          }
        }
        $body = !(empty($body)) ? str_replace('"/sites/default/files', '"' . $img_url, $body) : '';
        $nid_user_activity[] = $value;
        $results['results'][] = [
          "nid" => $value,
          "imageLarge" => isset($url) ? $url : '',
          "imageMedium" => isset($url) ? $url : '',
          "imageSmall" => isset($url) ? $url : '',
          "title" => isset($title) ? $title : '',
          "subTitle" => isset($sub_title) ? $sub_title : '',
          "description" => isset($body) ? $body : '',
          "pointValue" => isset($point_value) ? $point_value : '',
          "type" => $type->type,
        ];
      }

      $total_count = count($bookmark_data_by_type) - $this->offset;
      $pages = ceil($total_count / $this->limit);
    }
    $results['pager'] = [
      "count" => $total_count,
      "pages" => $pages,
      "items_per_page" => $this->limit,
      "current_page" => 0,
      "next_page" => 1,
    ];
    header('Content-language: ' . $lang[0]->language);

    return new Response($results, 200);
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
    $faq_config_data = ContentModel::getFaqValues();
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
    if ($pageType != 'faq') {
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
        return $this->errorResponse('Node id already exist.', Response::HTTP_BAD_REQUEST);
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
      $badge_info['old_points'] = $response['_source']['total_points'];
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
      $badge_info['old_points'] = 0;
      $params['body'] = [
        'doc' => [
          'total_points' => $point_value,
        ],
        'doc_as_upsert' => TRUE,
      ];
      $output = ElasticUserModel::updateElasticUserData($params, $this->uid, $this->elasticClient);
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    }
    // New points of user.
    $badge_info['new_points'] = $response['_source']['total_points'];
    $new_points = $badge_info['new_points'];
    $badge_info['uid'] = $this->uid;
    // Old points of user.
    $old_points = $badge_info['old_points'];
    $badge = [];
    // Allocate badge to user on the basis of old points & new points.
    if ($old_points < 1000 && $new_points >= 1000) {
      $badge[] = 'first_1_000_points_badge';
    }
    if ($old_points < 5000 && $new_points >= 5000) {
      $badge[] = 'first_5_000_points_badge';
    }
    if ($old_points < 10000 && $new_points >= 10000) {
      $badge[] = 'first_10000_points_badge';
    }
    $set_user_point = BadgeModel::allocateBadgeToUser($nid, $badge_info, $badge, $this->elasticClient);

    return $set_user_point;
  }

}
