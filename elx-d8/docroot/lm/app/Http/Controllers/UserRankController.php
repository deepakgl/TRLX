<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Mysql\UserModel;;
use App\Model\Elastic\ElasticUserModel;

/**
 * Purpose of this class is to calculate user rank.
 */
class UserRankController extends Controller {

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
    $uid = Helper::getJtiToken($request);
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    $client = Helper::checkElasticClient();
    $lang = UserModel::getUserInfoByUid($uid, 'language');
    header('Content-language: ' . $lang[0]->language);
    $exist = ElasticUserModel::checkElasticUserIndex($uid, $client);
    if (!$exist) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    $current_user_data = ElasticUserModel::fetchElasticUserData($uid, $client);
    // Fetch user left, user right & user centre points and uid.
    list($user_left_uid, $user_left_points, $user_right_uid, $user_right_points,
    $rank_centre) = $this->getUserRank($current_user_data, $client);
    // Fetch user information of left, centre and right user.
    $user_left = UserModel::getUserInfoByUid($user_left_uid, ['name', 'image',
      'state',
    ]);
    $user_centre = UserModel::getUserInfoByUid($uid, ['name', 'image',
      'state',
    ]);
    $user_right = UserModel::getUserInfoByUid($user_right_uid, ['name',
      'image', 'state',
    ]);
    // Build response of left, centre and right user.
    $response['userLeft'] = [];
    if (!empty($user_left_uid) && $user_left_uid != $uid) {
      $response['userLeft'][] = $this->responseElement($user_left_uid,
       $user_left, $rank_centre, $user_left_points);
    }
    $response['userCentre'][] = $this->responseElement($uid, $user_centre,
     $rank_centre + 1, $current_user_data['_source']['total_points']);

    $response['userRight'][] = $this->responseElement($user_right_uid,
     $user_right, $rank_centre + 2, $user_right_points);

    return new Response($response, 200);
  }

  /**
   * Prepare rank response.
   *
   * @param int $uid
   *   User uid.
   * @param array $user
   *   User object.
   * @param int $rank
   *   User rank.
   * @param int $points
   *   User points.
   *
   * @return array
   *   Object of user status.
   */
  protected function responseElement($uid, $user, $rank, $points) {
    $image_url = "";
    if (!empty($user[0]->image)) {
      $image_url = str_replace("public://", getenv("SITE_IMAGE_URL"),
       $user[0]->image);
    }
    $data = [
      "uid" => $uid,
      "userName" => $user[0]->firstname . " " . $user[0]->lastname,
      "userImage" => $image_url,
      "rank" => $rank,
      "pointValue" => $points,
      "state" => $user[0]->state,
    ];
    return $data;
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
  protected function getUserRank($data, $client) {
    $points = $data['_source']['total_points'] == 0 ? 2 :
     $data['_source']['total_points'];
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
    $response = $client->search($query);

    return [
      !empty($response['aggregations']['duplicateCount']['buckets']['greater']
      ['greater']['hits']['hits']) ?
      $response['aggregations']['duplicateCount']['buckets']['greater']
      ['greater']['hits']['hits'][0]['_source']['uid'] : FALSE,
      !empty($response['aggregations']['duplicateCount']['buckets']['greater']
      ['greater']['hits']['hits']) ?
      $response['aggregations']['duplicateCount']['buckets']['greater']
      ['greater']['hits']['hits'][0]['_source']['total_points'] : FALSE,
      $response['aggregations']['duplicateCount']['buckets']['less']['less']
      ['hits']['hits'][0]['_source']['uid'],
      $response['aggregations']['duplicateCount']['buckets']['less']['less']
      ['hits']['hits'][0]['_source']['total_points'],
      $response['aggregations']['duplicateCount']['buckets']['greater']
      ['greater']['hits']['total'],
    ];
  }

}
