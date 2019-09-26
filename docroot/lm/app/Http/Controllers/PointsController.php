<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Support\Helper;
use App\Model\Elastic\ElasticUserModel;
use App\Traits\ApiResponser;

/**
 * Purpose of building this class is to fetch user points.
 */
class PointsController extends Controller {

  use ApiResponser;

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
    global $_userData;
    $validatedData = $this->validate($request, [
      '_format' => 'required|format',
    ]);
    $uid = $_userData->userId;
    // Check whether elastic connectivity exists.
    $client = Helper::checkElasticClient();
    // Check whether use elastic index exists.
    $exist = ElasticUserModel::checkElasticUserIndex($uid, $client);
    if (!$client || !$exist) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $response = ElasticUserModel::fetchElasticUserData($uid, $client);

    return $this->successResponse($response['_source']['total_points'], Response::HTTP_OK);
  }

}
