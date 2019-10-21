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
    $profileSection = $section;
    if ($section == '') {
      $section = 'region';
    }
    // To show user rank in section on leaderboard page based on selection.
    $user_rank = $this->calculateUserRankByPoints($this->elasticClient, $section);
    $other_users_data = $this->getAllUsersRankByPoints($this->elasticClient, $section, $section_filter);
    // Add 1 in the rank of current user.
    $user_rank = !empty($user_rank) ? $user_rank['hits']['total'] : 0;
    $user_selected_section_rank = ($user_rank == 0) ? 1 : ($user_rank + 1);
    $badges_count = 3;
    $total_points = $current_user_data['_source']['total_points'];
    $userData = [
      'uid' => $this->uid,
      'pointValue' => $total_points,
      'rank' => "#" . $user_selected_section_rank,
      'stamps' => $badges_count,
    ];
    $response['userData'] = $userData;
    $sectionData = [];
    if (!empty($other_users_data['hits']['hits'])) {
      $rank = 1;
      $i = 0;
      foreach ($other_users_data['hits']['hits'] as $value) {
        $sectionData[$i]['uid'] = (int) $value['_source']['uid'];
        $sectionData[$i]['rank'] = "#" . $rank;
        $sectionData[$i]['pointValue'] = isset($value['_source']['total_points']) ? $value['_source']['total_points'] : 0;
        $i++;
        $rank++;
      }
      if ($profileSection != '') {
        $pager = $this->buildPager($other_users_data, $this->limit, $this->offset);
        $response['sectionData'] = $sectionData;
      }
    }
    // If multiple user have same number of view points.
    $keys = array_keys(array_column($sectionData, 'pointValue'), $total_points);
    if (!empty($keys) && count($keys) >= 2) {
      foreach ($keys as $key) {
        if ($sectionData[$key]['uid'] == $this->uid) {
          $response['userData'] = $sectionData[$key];
        }
      }
    }
    header('Content-language: ' . $validatedData['language']);
    if (!empty($sectionData) && ($profileSection != '')) {
      return $this->successResponse($response, Response::HTTP_OK, $pager);
    }
    else {
      return $this->successResponse($response, Response::HTTP_OK);
    }

  }

  /**
   * Fetch total points of user.
   *
   * @return json
   *   User rank by total view points.
   */
  protected function getAllUsersRankByPoints($elasticClient, $section, $section_filter) {
    global $_userData;
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
          'uid' => [
            'order' => 'desc',
          ],
        ],
      ],
    ];
    // If no comparison is there & is not equal to world.
    if ($section != 'world') {
      if ($section == 'country') {
        if (!empty($_userData->country)) {
          // Get user information based on country.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $_userData->country;
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'country' => $filter,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'subregion') {
        if (!empty($_userData->subregion)) {
          // Get user information based on subregion.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $_userData->subregion;
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'subRegion' => $filter,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'region') {
        if (!empty($_userData->region)) {
          // Get user information based on region.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $_userData->region;
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'region' => $filter,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'location') {
        if (!empty($_userData->location)) {
          // Get user information based on country.
          // Create the query filters.
          $filter = ($section_filter != 0) ? [$section_filter] : $_userData->location;
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'location' => $filter,
          ];
        }
        else {
          return [];
        }
      }
    }
    // Search the data in elastic based on the search params.
    return $elasticClient->search($search_param);
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
    // If no comparison is there & is not equal to world.
    if ($section != 'world') {
      if ($section == 'country') {
        if (!empty($_userData->country)) {
          // Get user information based on country.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'country' => $_userData->country,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'subregion') {
        if (!empty($_userData->subregion)) {
          // Get user information based on subregion.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'subRegion' => $_userData->subregion,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'region') {
        if (!empty($_userData->region)) {
          // Get user information based on region.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'region' => $_userData->region,
          ];
        }
        else {
          return [];
        }
      }
      if ($section == 'location') {
        if (!empty($_userData->location)) {
          // Get user information based on country.
          // Create the query filters.
          $search_param['body']['query']['bool']['filter'][]['terms'] = [
            'location' => $_userData->location,
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
    if (isset($data['hits']['total'])) {
      $total_count = $data['hits']['total'] - $offset;
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
      "next_page" => 0,
    ];
    return $response;
  }

}
