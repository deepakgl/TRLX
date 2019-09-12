<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Elastic\FlagModel;
use App\Model\Mysql\ContentModel;
use App\Traits\ApiResponser;
use App\Model\Mysql\UserModel;

/**
 * Purpose of this class is to build and fetch user actitivities.
 */
class UserActivitiesController extends Controller {
  
  use ApiResponser;

  /**
   * Create a new controller instance.
   */
  public function __construct() {
    
  }

  /**
   * Get user activities.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User activities.
   */
  public function getUserActivities(Request $request) {
    // User id.
    $uid = $request->input('uid');
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    $client = Helper::checkElasticClient();
    if (!$client) {
      return FALSE;
    }
    // Node id.
    $nid = $request->input('nid');
    $nid_decode = unserialize($nid);
    // Fetch node data from elastic.
    $response = FlagModel::fetchMultipleElasticNodeData($nid_decode, $client);
    // Check user flag status.
    $user_activities = $this->userFlagStatus($response, $uid);
    $output['userActivities'] = $user_activities;

    return Helper::jsonSuccess($output);
  }

  /**
   * Get UserActivities.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User activities.
   */
  public function userActivities(Request $request) {
    // $uid = Helper::getJtiToken($request);
    $validatedData = $this->validate($request, [
      'uid' => 'required|positiveinteger|exists:users_field_data,uid',
      'nid' => 'required|numericarray|exists:node,nid',
      '_format' => 'required|format'
    ]);
    $this->uid = $validatedData['uid'];
    $nids = $validatedData['nid'];
    $client = Helper::checkElasticClient();
    if (!$client) {
      $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    // Fetch node data from elastic.
    $response = FlagModel::fetchMultipleElasticNodeData($nids, $client);
    // Check user flag status.
    $user_activities = $this->userFlagStatus($response, $this->uid);

    return $this->successResponse($user_activities);
  }

  /**
   * Fetch user activity.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User activity.
   */
  public function userActivity(Request $request) {
    $uid = Helper::getJtiToken($request);
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 400);
    }
    $nids = $request->input('nid');
    if (!$nids) {
      return Helper::jsonError('Please provide node id.', 400);
    }
    $client = Helper::checkElasticClient();
    if (!$client) {
      return FALSE;
    }
    // Fetch interactive level status.
    $module_status = ContentModel::getIntereactiveLevelNodeStatus($uid, NULL, serialize($nids));
    // Fetch node data from elastic.
    $response = FlagModel::fetchMultipleElasticNodeData($nids, $client);
    // Check user flag status.
    $user_activities = $this->userFlagStatus($response, $uid, $module_status, 'userActivity');

    return new Response($user_activities, 200);
  }

  /**
   * Fetch global activity.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Global activity.
   */
  public function globalActivity(Request $request) {
    $uid = Helper::getJtiToken($request);
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 400);
    }
    $nids = $request->input('nid');
    if (!$nids) {
      return Helper::jsonError('Please provide node id.', 400);
    }
    $client = Helper::checkElasticClient();
    if (!$client) {
      return FALSE;
    }
    // Fetch node data from elastic.
    $response = FlagModel::fetchMultipleElasticNodeData($nids, $client);
    // Check user flag status.
    $user_activities = $this->userFlagStatus($response, $uid, NULL, 'globalActivity');

    return new Response($user_activities, 200);
  }

  /**
   * Get user activities of levels.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User activities level based.
   */
  public function userActivitiesLevel(Request $request) {
    $uid = Helper::getJtiToken($request);
    $nid = $request->input('id');
    $tid = $request->input('categoryId');
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    elseif (!$tid) {
      return Helper::jsonError('Please provide category id.', 422);
    }
    elseif (!$nid) {
      return Helper::jsonError('Please provide node id.', 422);
    }
    // Check whether elastic connectivity is there.
    $client = Helper::checkElasticClient();
    if (!$client) {
      return FALSE;
    }
    $output = $output['userActivity'] = [];
    // Fetch interactive level status.
    $module_status = ContentModel::getIntereactiveLevelNodeStatus($uid, $tid, serialize($nid));
    // Fetch node data from elastic.
    $response = FlagModel::fetchMultipleElasticNodeData($nid, $client);
    if (!empty(array_filter($nid))) {
      // Check user flag status.
      $user_activities = $this->userFlagStatus($response, $uid, $module_status);
      $output['userActivity'] = $user_activities;
    }
    $complete_count = array_count_values(array_column($output['userActivity'], 'status'));
    $complete_count = (isset($complete_count[1]) && $complete_count[1] > 0) ? $complete_count[1] : (int) 0;
    // Get term name by tid and vid.
    $level_name = ContentModel::getTermName([$tid], 'learning_category');
    $percentage_completed = 0;
    if (count($output['userActivity']) > 0) {
      $percentage_completed = ceil($complete_count / count($output['userActivity']) * 100);
    }
    $lang = UserModel::getUserInfoByUid($uid, 'language');
    $output['levelDetail'] = [
      "name" => (isset($level_name[0])) ? $level_name[0] : '',
      "completedCount" => $complete_count,
      "totalCount" => count($output['userActivity']),
      "totalPoints" => ContentModel::getPointValueByNid($nid, $lang[0]->language),
      "percentageCompleted" => $percentage_completed,
      "userLevelActivity" => [
        "categoryId" => $tid,
        "likes" => 100,
        "bookmarks" => 100,
        "userLikeStatus" => TRUE,
        "userBookmarkStatus" => TRUE,
      ],
    ];

    return new Response($output, 200);
  }

  /**
   * Check user flag status.
   *
   * @param array $response
   *   Elastic node object.
   * @param int $uid
   *   User id.
   * @param mixed $module_status
   *   Status of module.
   * @param string $flag
   *   Flag name.
   *
   * @return json
   *   User activities.
   */
  public static function userFlagStatus(array $response, $uid, $module_status = NULL, $flag = NULL) {
    $user_activities = $status_arr = [];
    foreach ($response['docs'] as $key => $value) {
      // If data found in elastic.
      if ($value['found'] == 1) {
        $like_status = $bookmark_status = FALSE;
        $like_count = $bookmark_count = 0;
        // If key exists in array, update the respective flag status.
        if (array_key_exists('like_by_user', $value['_source'])) {
          $like_count = (int) count($value['_source']['like_by_user']);
          if (in_array($uid, $value['_source']['like_by_user'])) {
            $like_status = TRUE;
          }
        }
        if (array_key_exists('bookmark_by_user', $value['_source'])) {
          $bookmark_count = (int) count($value['_source']['bookmark_by_user']);
          if (in_array($uid, $value['_source']['bookmark_by_user'])) {
            $bookmark_status = TRUE;
          }
        }
        if (!empty($module_status)) {
          $incomplete_status = ['progress', NULL];
          $status[$key][$value['_id']] = (isset($module_status[$value['_id']]) && !in_array($module_status[$value['_id']]->statement_status, $incomplete_status)) ? (int) 1 : (int) 0;
        }
        if ($flag == "userActivity") {
          $user_activities[$key] = [
            "nid" => (int) $value['_id'],
            "userLikeStatus" => $like_status,
            "userBookmarkStatus" => $bookmark_status,
          ];
        }
        elseif ($flag == "globalActivity") {
          $user_activities[$key] = [
            "nid" => (int) $value['_id'],
            "likes" => $like_count,
            "bookmarks" => $bookmark_count,
          ];
        }
        else {
          $user_activities[$key] = [
            "nid" => (int) $value['_id'],
            "likes" => $like_count,
            "bookmarks" => $bookmark_count,
            "userLikeStatus" => $like_status,
            "userBookmarkStatus" => $bookmark_status,
          ];
        }
        if (isset($status) && array_key_exists($user_activities[$key]['nid'], $status[$key])) {
          $user_activities[$key] = $user_activities[$key] + ['status' => $status[$key][$value['_id']]];
        }
      }
      else {
        $user_activities[] = self::userFlagStatusEmptyResponse($value['_id'], $flag);
      }
    }

    return array_values($user_activities);
  }

  /**
   * Fetch user flag empty response
   *
   * @param int $id
   *   Node id.
   * @param string $flag
   *   Flag name.
   *
   * @return json
   *   User activities.
   */
  public static function userFlagStatusEmptyResponse($id, $flag = NULL) {
    $user_activities = [];
    if ($flag == "userActivity") {
      $user_activities = [
        "nid" => (int) $id,
        "userLikeStatus" => false,
        "userBookmarkStatus" => false,
      ];
    }
    elseif ($flag == "globalActivity") {
      $user_activities = [
        "nid" => (int) $id,
        "likes" => 0,
        "bookmarks" => 0,
      ];
    }
    else {
      $user_activities = [
        "nid" => (int) $id,
        "likes" => 0,
        "bookmarks" => 0,
        "userLikeStatus" => false,
        "userBookmarkStatus" => false,
      ];
    }
    return $user_activities;
  }

}
