<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Support\Helper;
use App\Model\Elastic\ElasticUserModel;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;

/**
 * Class to get the data from elastic based on query.
 */
class LeaderboardController extends Controller {

  use ApiResponser;

  /**
   * Elastic client.
   *
   * @var elasticClient
   */
  private $elasticClient = NULL;
  /**
   * User id.
   *
   * @var uid
   */
  private $uid = NULL;
  /**
   * Limit.
   *
   * @var limit
   */
  private $limit = NULL;
  /**
   * Offset.
   *
   * @var offset
   */
  private $offset = NULL;
  /**
   * Size.
   *
   * @var size
   */
  private $size = NULL;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct(Request $request) {
    $this->elasticClient = '';
    $this->uid = '';
    // Limit.
    $this->limit = 10;
    // Offset.
    $this->offset = 0;
    // Size.
    $this->size = 10000;
  }

  /**
   * Fetch user leaderboard data.
   *
   * @return json
   *   User leaderboard data.
   */
  public function userLeaderBoard(Request $request) {
    global $_userData;
    $response = [];
    $this->uid = $_userData->userId;
    // Check whether elastic connectivity is there.
    $this->elasticClient = Helper::checkElasticClient();
    // Check whether user elastic index exists.
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->elasticClient);
    if (!$this->elasticClient) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    if (!$exist) {
      return $this->errorResponse('Not authorized.', Response::HTTP_FORBIDDEN);
    }
    $validatedData = $this->validate($request, [
      'limit' => 'sometimes|required|integer|min:0',
      'offset' => 'sometimes|required|integer|min:0',
      '_format' => 'required|format',
      'language' => 'required|languagecode',
      'section' => 'sometimes|required|leaderboardsection',
      'sectionFilter' => 'sometimes|required|regex:/^[0-9]+$/',
    ]);
    $this->limit = isset($validatedData['limit']) ? (int) $validatedData['limit'] : 10;
    $this->offset = isset($validatedData['offset']) ? (int) $validatedData['offset'] : 0;
    $section = isset($validatedData['section']) ? $validatedData['section'] : '';
    $section_filter = isset($validatedData['sectionFilter']) ? $validatedData['sectionFilter'] : 0;
    // Fetch respective user info from elastic.
    $current_user_data = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    // To show user rank in the region on the profile page.
    $profileSection = '';
    if ($section == '') {
      $profileSection = 'profilePage';
      $section = 'region';
    }
    // To show user rank in section on leaderboard page based on selection.
    $user_rank = $this->calculateUserRankByPoints($this->elasticClient, $section);
    $other_users_data = $this->getAllUsersRankInTheSystem($this->elasticClient, $section, $section_filter);
    // Add 1 in the rank of current user.
    $user_rank = !empty($user_rank) ? $user_rank['hits']['total'] : 0;
    $user_selected_section_rank = ($user_rank == 0) ? 1 : ($user_rank + 1);
    $badges_count = !empty(array_filter($current_user_data['_source']['badge'])) ? count(array_filter($current_user_data['_source']['badge'][0])) : 0;
    $total_points = $current_user_data['_source']['total_points'];
    $current_user_ref_id = $current_user_data['_source']['userExternalId'];
    $userData = [
      'uid' => $current_user_ref_id,
      'pointValue' => $total_points,
      'rank' => "#" . $user_selected_section_rank,
      'stamps' => $badges_count,
    ];
    $response['userData'] = $userData;
    $other_users_sliced_array = [];
    if ($profileSection != 'profilePage') {
      $pager = $this->buildPager($other_users_data, $this->limit, $this->offset);
      $other_users_sliced_array = array_slice($other_users_data, $this->offset, $this->limit);
      $response['sectionData'] = $other_users_sliced_array;
    }
    if ($profileSection != 'profilePage') {
      $all_users_data_array = $this->getAllUsersRankInTheSystem($this->elasticClient, $section);
    }
    else {
      $all_users_data_array = $this->getAllUsersRankInTheSystem($this->elasticClient);
    }
    // If multiple user have same number of view points.
    $keys = array_keys(array_column($all_users_data_array, 'pointValue'), $total_points);
    if (!empty($keys) && count($keys) >= 2) {
      foreach ($keys as $key) {
        if ($all_users_data_array[$key]['uid'] == $_userData->uid) {
          $response['userData']['uid'] = $all_users_data_array[$key]['uid'];
          $response['userData']['pointValue'] = $all_users_data_array[$key]['pointValue'];
          $response['userData']['rank'] = $all_users_data_array[$key]['rank'];
          $response['userData']['stamps'] = $badges_count;
        }
      }
    }
    header('Content-language: ' . $validatedData['language']);
    if (!empty($other_users_sliced_array)) {
      return $this->successResponse($response, Response::HTTP_OK, $pager);
    }
    else {
      return $this->successResponse($response, Response::HTTP_OK);
    }

  }

  /**
   * Fetch current user rank based on points.
   *
   * @return array
   *   Users data above the current user points.
   */
  public function calculateUserRankByPoints($elasticClient, $section) {
    global $_userData;
    $uid = $_userData->userId;
    // Check whether elastic connectivity is there.
    $this->elasticClient = Helper::checkElasticClient();
    // Check whether elastic user index is exists.
    $exist = ElasticUserModel::checkElasticUserIndex($uid, $elasticClient);
    if ($exist) {
      // Fetch current user data from elastic.
      $user_data = ElasticUserModel::fetchElasticUserData($uid, $elasticClient);
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
      // Calculate user rank based on comparison.
      $response = $this->calculateUserRank($search_param, $section);
    }

    return $response;
  }

  /**
   * Calculate user rank based on section.
   *
   * @param string $search_param
   *   String to be searched.
   * @param string $section
   *   User rank to be calculated for which section.
   *
   * @return array
   *   User rank in the selected section.
   */
  protected function calculateUserRank($search_param, $section) {
    global $_userData;
    $current_user_data = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    // If no comparison is there & is not equal to world.
    if ($section != 'world') {
      if ($section == 'country') {
        if (!empty($current_user_data['_source']['country'])) {
          // Get user information based on country.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'country' => $current_user_data['_source']['country'],
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'subregion') {
        if (!empty($current_user_data['_source']['subRegion'])) {
          // Get user information based on subregion.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'subRegion' => $current_user_data['_source']['subRegion'],
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'region') {
        if (!empty($current_user_data['_source']['region'])) {
          // Get user information based on region.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'region' => $current_user_data['_source']['region'],
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'location') {
        if (!empty($current_user_data['_source']['locations'])) {
          // Get user information based on country.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'locations' => $current_user_data['_source']['locations'],
          ];
        }
        else {
          return [];
        }
      }
    }
    // Search the data in elastic based on the search params.
    return $this->elasticClient->search($search_param);
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
   *   Pager array.
   */
  protected function buildPager(array $data, $limit, $offset) {
    if (!empty($data)) {
      $total_count = count($data) - $offset;
    }
    else {
      $total_count = 1;
    }
    $pages = ceil($total_count / $limit);
    $response = [
      "count" => ($total_count > 0) ? $total_count : 0,
      "pages" => ($pages > 0) ? (int) $pages : 0,
      "items_per_page" => (int) $limit,
      "current_page" => 0,
      "next_page" => ($pages > 1) ? 1 : 0,
    ];
    return $response;
  }

  /**
   * Fetch user rank and other immediate users rank.
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
    $all_users_data = $this->getAllUsersRankInTheSystem($client);
    if (!empty($all_users_data)) {
      foreach ($all_users_data as $key => $user_info) {
        if ($user_info['uid'] == $_userData->uid) {
          $response['userLeft'] = (Object) [];
          $user_left_key = (($key - 1) >= 0) ? ($key - 1) : -1;
          if ($user_left_key >= 0) {
            $response['userLeft'] = [];
            $response['userLeft']['uid'] = $all_users_data[$user_left_key]['uid'];
            $response['userLeft']['rank'] = $all_users_data[$user_left_key]['rank'];
          }
          $response['userCenter']['uid'] = $all_users_data[$key]['uid'];
          $response['userCenter']['rank'] = $all_users_data[$key]['rank'];
          $response['userRight'] = (Object) [];
          $user_right_key = ($key < (count($all_users_data) - 1)) ? $key + 1 : -1;
          if ($user_right_key > 0) {
            $response['userRight'] = [];
            $response['userRight']['uid'] = $all_users_data[$user_right_key]['uid'];
            $response['userRight']['rank'] = $all_users_data[$user_right_key]['rank'];
          }
        }
      }
    }
    if (!empty($response)) {
      return $this->successResponse([$response], Response::HTTP_OK);
    }
    else {
      return $this->successResponse([], Response::HTTP_OK);
    }

  }

  /**
   * Fetch all users from the system based on applied filters.
   *
   * @return array
   *   All users rank.
   */
  protected function getAllUsersRankInTheSystem($elasticClient, $section = 'region', $section_filter = 0) {
    global $_userData;
    $this->uid = $_userData->userId;
    // Check whether elastic connectivity is there.
    $this->elasticClient = Helper::checkElasticClient();
    $current_user_data = ElasticUserModel::fetchElasticUserData($this->uid, $this->elasticClient);
    $search_param = [
      'index' => getenv("ELASTIC_ENV") . '_user',
      'type' => 'user',
      'size' => $this->size,
      '_source_includes' => ['uid', 'total_points', 'userExternalId'],
      'body' => [
        'sort' => [
          'total_points' => [
            'order' => 'desc',
          ],
          'uid' => [
            'order' => 'desc',
          ],
        ],
      ],
    ];
    // If no comparison is there & is not equal to world.
    if ($section != 'world') {
      if ($section == 'country') {
        if (!empty($current_user_data['_source']['country'])) {
          // Get user information based on country.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $current_user_data['_source']['country'];
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'country' => $filter,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'subregion') {
        if (!empty($current_user_data['_source']['subRegion'])) {
          // Get user information based on subregion.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $current_user_data['_source']['subRegion'];
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'subRegion' => $filter,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'region') {
        if (!empty($current_user_data['_source']['region'])) {
          // Get user information based on region.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $current_user_data['_source']['region'];
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'region' => $filter,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'location') {
        if (!empty($current_user_data['_source']['locations'])) {
          // Get user information based on country.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $current_user_data['_source']['locations'];
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'locations' => $filter,
          ];
        }
        else {
          return [];
        }
      }
    }
    // Search the data in elastic based on the search params.
    $all_users_data = $elasticClient->search($search_param);
    $all_users_data_array = [];
    if (!empty($all_users_data['hits']['hits'])) {
      $position = 1;
      $j = 0;
      foreach ($all_users_data['hits']['hits'] as $value) {
        $all_users_data_array[$j]['uid'] = isset($value['_source']['userExternalId']) ? $value['_source']['userExternalId'] : 0;
        $all_users_data_array[$j]['rank'] = "#" . $position;
        $all_users_data_array[$j]['pointValue'] = isset($value['_source']['total_points']) ? $value['_source']['total_points'] : 0;
        $j++;
        $position++;
      }
    }
    return $all_users_data_array;
  }

}
