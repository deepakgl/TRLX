<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Elastic\ElasticUserModel;
use App\Traits\ApiResponser;

/**
 * Purpose of this class is to calculate user rank.
 */
class UserRankController extends Controller {

  use ApiResponser;

  /**
   * Create a new controller instance.
   */
  public function __construct() {}

  /**
   * Fetch user rank.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Object of user rank.
   */
  public function userProfileRank(Request $request) {
    global $_userData;
    $uid = $_userData->userId;
    $response = [];
    // Check whether elastic connectivity is there.
    $client = Helper::checkElasticClient();
    // Check whether user elastic index exists.
    $exist = ElasticUserModel::checkElasticUserIndex($uid, $client);
    if (!$client) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    if (!$exist) {
      return $this->errorResponse('Not authorized.', Response::HTTP_FORBIDDEN);
    }
    $validatedData = $this->validate($request, [
      '_format' => 'required|format',
      'language' => 'required|languagecode',
    ]);
    header('Content-language: ' . $validatedData['language']);
    $current_user_data = ElasticUserModel::fetchElasticUserData($uid, $client);
    // Fetch user left, user right & user centre points and uid.
    list($user_left_uid, $user_left_points, $user_right_uid, $user_right_points,
    $rank_centre) = $this->getUserRank($current_user_data, $client);
    // Fetch user information of left, centre and right user.
    $left_user_data = ElasticUserModel::fetchElasticUserData($user_left_uid, $client);
    $right_user_data = ElasticUserModel::fetchElasticUserData($user_right_uid, $client);
    // Build response of left, centre and right user.
    $response['userLeft'] = [];
    if (!empty($user_left_uid) && $user_left_uid != $uid) {
      $response['userLeft']['uid'] = (int) $user_left_uid;
      $response['userLeft']['rank'] = "#" . $rank_centre;
    }
    $response['userCentre']['uid'] = (int) $uid;
    $response['userCentre']['rank'] = "#" . ($rank_centre + 1);
    $response['userRight'] = [];
    if (!empty($user_right_uid) && $user_right_uid != $uid) {
      $response['userRight']['uid'] = (int) $user_right_uid;
      $response['userRight']['rank'] = "#" . ($rank_centre + 2);
    }
    return $this->successResponse($response, Response::HTTP_OK);
  }

  /**
   * Fetch user rank based on total points.
   *
   * @param array $data
   *   User elastic data.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   User left, right & centre points and uid.
   */
  protected function getUserRank(array $data, $client) {
    global $_userData;
    $points = ($data['_source']['total_points'] == 0) ? 2 : $data['_source']['total_points'];
    $query = [
      'index' => getenv("ELASTIC_ENV") . '_user',
      'type' => 'user',
      'body' => [
        "size" => 0,
        "aggs" => [
          "duplicateCount" => [
            "range" => [
              "field" => "total_points",
              "keyed" => TRUE,
              "ranges" => [
                ["key" => "less", "to" => $points - 1],
                ["key" => "greater", "from" => $points + 1],
              ],
            ],
            "aggs" => [
              "less" => [
                "top_hits" => [
                  "sort" => [
                    "total_points" => "desc",
                  ],
                  "size" => 1,
                ],
              ],
              "greater" => [
                "top_hits" => [
                  "sort" => [
                    "total_points" => "asc",
                  ],
                  "size" => 1,
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    if (!empty($_userData->region)) {
      $query['body']['query']['bool']['filter'][]['terms'] = [
        'region' => $_userData->region,
      ];
    }
    // Search the data in elastic based on the search params.
    $response = $client->search($query);
    return [
      !empty($response['aggregations']['duplicateCount']['buckets']['greater']['greater']['hits']['hits']) ?
      $response['aggregations']['duplicateCount']['buckets']['greater']['greater']['hits']['hits'][0]['_source']['uid'] : FALSE,
      !empty($response['aggregations']['duplicateCount']['buckets']['greater']['greater']['hits']['hits']) ?
      $response['aggregations']['duplicateCount']['buckets']['greater']['greater']['hits']['hits'][0]['_source']['total_points'] : FALSE,
      !empty($response['aggregations']['duplicateCount']['buckets']['less']['less']['hits']['hits'][0]['_source']['uid']) ?
      $response['aggregations']['duplicateCount']['buckets']['less']['less']['hits']['hits'][0]['_source']['uid'] : FALSE,
      !empty($response['aggregations']['duplicateCount']['buckets']['less']['less']['hits']['hits'][0]['_source']['total_points']) ?
      $response['aggregations']['duplicateCount']['buckets']['less']['less']['hits']['hits'][0]['_source']['total_points'] : FALSE,
      $response['aggregations']['duplicateCount']['buckets']['greater']['greater']['hits']['total'],
    ];
  }

}
