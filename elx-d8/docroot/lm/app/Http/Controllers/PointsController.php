<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Elastic\ElasticUserModel;

/**
 * Purpose of building this class is to fetch user points.
 */
class PointsController extends Controller {

  /**
   * Create a new controller instance.
   */
  public function __construct() {

  }

  /**
   * Get User Points by UID.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User total points.
   */
  public function getUserPoints(Request $request) {
    $uid = $request->input('uid');
    if (!$uid) {
      return Helper::jsonError('Invalid user id.', 422);
    }
    // Check whether elastic connectivity exists.
    $client = Helper::checkElasticClient();
    // Check whether use elastic index exists.
    $exist = ElasticUserModel::checkElasticUserIndex($uid, $client);
    if (!$client || !$exist) {
      return FALSE;
    }
    $response = ElasticUserModel::fetchElasticUserData($uid, $client);

    return Helper::jsonSuccess($response['_source']['total_points']);
  }

}
