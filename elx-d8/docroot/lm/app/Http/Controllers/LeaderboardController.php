<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Support\Helper;
use App\Model\Mysql\UserModel;
use App\Model\Mysql\ContentModel;
use App\Model\Elastic\ElasticUserModel;
use Illuminate\Http\Request;
use App\Model\Elastic\BadgeModel;
use App\Http\Controllers\BadgesController;

/**
 *
 */
class LeaderboardController extends Controller {

  /**
   * Elastic client.
   */
  private $elasticClient = NULL;
  /**
   * User id.
   */
  private $uid = NULL;
  /**
   * Limit.
   */
  private $limit = NULL;
  /**
   * Offset.
   */
  private $offset = NULL;
  /**
   * Leaderboard comparison.
   */
  private $compare = NULL;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct(Request $request) {
    $this->elasticClient = '';
    $this->uid = '';
    // Limit.
    $this->limit = !empty($request->input('limit')) ? $request->input('limit') : 10;
    // Offset.
    $this->offset = !empty($request->input('offset')) ? $request->input('offset') : 0;
    // Compare Type.
    $this->compare = $request->input('compare');
  }

  /**
   * Fetch total points of user.
   *
   * @return json
   */
  protected function getAllUsersRankByPoints() {
    $search_param = [
      'index' => getenv("ELASTIC_ENV") . '_user',
      'type' => 'user',
    // Offset.
      'from' => $this->offset,
    // Limit.
      'size' => $this->limit,
      'body' => [
        'sort' => [
          'total_points' => [
            'order' => 'desc',
          ],
        ],
      ],
    ];
    $response = $this->calculateUserRank($search_param);

    return $response;
  }

  /**
   * Fetch current user rank based on points.
   *
   * @return json
   */
  public function calculateUserRankByPoints($key = NULL) {
    // Check whether elastic connectivity is there.
    $this->elasticClient = Helper::checkElasticClient();
    // Check whether elastic user index is exists.
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->elasticClient);
    if ($exist) {
      // Fetch current user data from elastic.
      $user_data = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
      // Fetch total points of user.
      $user_points = $user_data['_source']['total_points'];
      $search_param = [
        'index' => getenv("ELASTIC_ENV") . '_user',
        'type' => 'user',
        'body' => [
          'query' => [
            'bool' => [
              'filter' => [
                [
                  'range' => [
                    'total_points' => [
                      'gt' => (int) $user_points,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
      if (!empty($key)) {
        $this->compare[0] == $key;
      }
      // Calculate user rank based on comparison.
      $response = $this->calculateUserRank($search_param);
    }

    return $response;
  }

  /**
   * Calculate user rank based on comparison.
   *
   * @param string $search_param
   *   string to be searched.
   *
   * @return json
   */
  protected function calculateUserRank($search_param) {
    $filter = [];
    // If no comparison is there & is not equal to world.
    if (!empty($this->compare) && $this->compare[0] != 'world') {
      if ($this->compare[0] == 'store') {
        // Get user information based on store.
        $store = UserModel::getUserInfoByUid($this->uid, $this->compare[0]);
        if (isset($store[0]->store) && !empty($store[0]->store)) {
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['term'] = [
             'store.keyword' => ['value' => $store[0]->store]];
        }
      }
      elseif ($this->compare[0] == 'market') {
        // Get user information based on market.
        $markets = UserModel::getUserInfoByUid($this->uid, $this->compare[0]);
        if (isset($markets[0]->market) && !empty($markets[0]->market)) {
          // Create the query filters.
          $search_param['body']['query']['bool']['must']['match'] = [
             $this->compare[0] => $markets[0]->market];
        }
      }
      elseif ($this->compare[0] == 'retailer') {
        // Get user information based on retailer.
        $account = UserModel::getUserInfoByUid($this->uid, $this->compare[0]);
        if (isset($account[0]->retailer)  && !empty($account[0]->retailer)) {
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['term'] = [
             'account.keyword' => ['value' => $account[0]->retailer]];
        }
      }
    }
    /*
     * Search and Ignore Srijan & Test users in elastic.
     * Search and Ignore blocked users in elastic.
    */
    $search_param['body']['query']['bool']['must_not'] =
    [
      ['match' => ['ignore' => 1]],
      ['match' => ['status' => 0]]
    ];
    // Search the data in elastic based on the search params.
    $response = $this->elasticClient->search($search_param);

    return Helper::jsonSuccess($response);
  }

  /**
   * Get currentUserRank.
   *
   * @param $request
   *   Rest resource query parameters.
   *
   * @return json
   */
  public function currentUserRank(Request $request) {
    $this->uid = $request->input('uid');
    if (!$this->uid) {
      return Helper::jsonError('Invalid user id.', 402);
    }
    // Fetch current user rank based on points.
    $response = $this->calculateUserRankByPoints('store');

    return $response;
  }

  /**
   * Fetch user leaderboard.
   *
   * @return json
   */
  public function userLeaderBoard(Request $request) {
    // Fetch user id based on Oauth token.
    $this->uid = Helper::getJtiToken($request);
    if (!$this->uid) {
      return jsonError('Please provide user id.', 422);
    }
    // Check whether elastic connectivity is there.
    $this->elasticClient = Helper::checkElasticClient();
    // Check whether user elastic index exists.
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->elasticClient);
    if (!$this->elasticClient || !$exist) {
      return FALSE;
    }
    if (!empty($this->compare)) {
      $this->compare = ContentModel::getTermName([$this->compare], 'leaderboard_comparison');
      if (empty($this->compare)) {
        return new Response('Compare ID is not valid.', 400);
      }
    }
    $response = [];
    // Fetch current user rank based on points.
    $current_user_rank = json_decode($this->calculateUserRankByPoints()->getContent(), TRUE);
    // Add 1 in the rank of current user.
    $current_user_rank = !empty($current_user_rank['data']) ? $current_user_rank['data']['hits']['total'] : 0;
    $current_user_rank = $current_user_rank + 1;
    // Fetch respective user info from elastic.
    $current_user_data = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    // Fetch user name, image & language.
    $user_info = UserModel::getUserInfoByUid($this->uid, ['name', 'image', 'language']);
    $current_user_market_name = $other_user_market_name = "";
    // Get current user market name.
    if (isset($current_user_data['_source']['market'])) {
      // Get term name by tid and vid.
      $current_user_market = ContentModel::getMarketNameByLang($current_user_data['_source']['market'], $user_info[0]->language);
      $current_user_market_name = implode(", ", $current_user_market);
    }
    $exist_master = BadgeModel::checkBadgeMasterIndex($this->elasticClient);
    $badge_image = "";
    if ($exist_master) {
      $badge_data = BadgeModel::fetchBadgeMasterData($user_info[0]->language, $this->elasticClient);
      if (!empty($current_user_data['_source']['badge'])) {
        $current_user_badges = $current_user_data['_source']['badge'][0];
        $badge_image = BadgesController::starBadge($current_user_badges, $badge_data);
      }
    }
    $response['userData'][] = $this->buildResponse(
      $this->uid,
      $current_user_rank,
      $current_user_data,
      $user_info,
      $badge_image,
      $current_user_market_name
    );
    // Get all users ranks on the basis of total points.
    $other_user_data = json_decode($this->getAllUsersRankByPoints()->getContent(), TRUE);
    if (!empty($other_user_data['data']['hits']['hits'])) {
      $i = 1 + $this->offset;
      foreach ($other_user_data['data']['hits']['hits'] as $key => $value) {
        $other_uid = $value['_source']['uid'];
        $other_user_info = UserModel::getUserInfoByUid($other_uid, ['name', 'image', 'language']);
        if (!empty($other_user_info[0])) {
          // Get other user market name.
          if (isset($value['_source']['market'])) {
            $other_user_market[$other_uid] = ContentModel::getMarketNameByLang($value['_source']['market'], $other_user_info[0]->language);
            $other_user_market_name = implode(", ", $other_user_market[$other_uid]);
          }
          $other_badge_image = "";
          if ($exist_master) {
            if (isset($value['_source']['badge'][0])) {
              $other_user_badges = $value['_source']['badge'][0];
              $other_badge_image = BadgesController::starBadge($other_user_badges, $badge_data);
            }
          }
          $response['allLeaderboard'][] = $this->buildResponse(
            $other_uid,
            $i,
            $value,
            $other_user_info,
            $other_badge_image,
            $other_user_market_name
          );
          $i++;
      }
    }
      $response['pager'] = $this->buildPager($other_user_data, $this->limit, $this->offset);
    }

    header('Content-language: ' . $user_info[0]->language);

    return new Response($response, 200);
  }

  /**
   * Build api response based on the params.
   *
   * @param int $uid
   *   User id.
   * @param int $rank
   *   User rank.
   * @param array $data
   *   User elastic data.
   * @param array $info
   *   User info.
   * @param string $badge_image
   *   Badge image url.
   * @param string $market_name
   *   Assigned user market name.
   *
   * @return array
   */
  protected function buildResponse($uid, $rank, $data, $info, $badge_image, $market_name) {
    $image_url = "";
    if (!empty($info[0]->image)) {
      $image_url = str_replace("public://", getenv("SITE_IMAGE_URL"), $info[0]->image);
    }
    $response = [
      "uid" => $uid,
      "rank" => $rank,
      "userPoints" => $data['_source']['total_points'],
      "userName" => !empty($info[0]->firstname) ? $info[0]->firstname . " " . $info[0]->lastname : '',
      "userImage" => $image_url,
      "star" => $badge_image,
      "userMarket" => $market_name,
    ];

    return $response;
  }

  /**
   * Build pager based on limits and offset.
   *
   * @param array $data
   *   User elastic data.
   * @param int $limit
   *   Limit.
   * @param int $offset
   *   Offset.
   *
   * @return array
   */
  protected function buildPager($data, $limit, $offset) {
    $total_count = $data['data']['hits']['total'] - $offset;
    $pages = ceil($total_count / $limit);
    $response = [
      "count" => ($total_count > 0) ? $total_count : 0,
      "pages" => ($pages > 0) ? $pages : 0,
      "items_per_page" => $limit,
      "current_page" => 0,
      "next_page" => 1,
    ];

    return $response;
  }

}
