<?php

namespace App\Http\Controllers;

use App\Support\Helper;
use App\Model\Mysql\UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Purpose of building this class is to get the data from elastic based on the.
 *
 * Searched keyword.
 */
class SearchController extends Controller {
  /**
   * Product category.
   *
   * @var category
   */
  private $category = NULL;
  /**
   * Content type.
   *
   * @var mixed
   */
  private $type = NULL;
  /**
   * Elastic search limit.
   *
   * @var limit
   */
  private $limit = NULL;
  /**
   * Elastic search offset.
   *
   * @var offset
   */
  private $offset = NULL;
  /**
   * User market.
   *
   * @var market
   */
  private $market = NULL;
  /**
   * Fields to be searched.
   *
   * @var searchFields
   */
  private $searchFields = NULL;
  /**
   * Search string.
   *
   * @var search
   */
  private $search = NULL;
  /**
   * Search string.
   *
   * @var query
   */
  private $query = NULL;
  /**
   * User language.
   *
   * @var userLanguage
   */
  private $userLanguage = NULL;
  /**
   * Elastic search index name.
   *
   * @var elasticSearchIndex
   */
  private $elasticSearchIndex = NULL;
  /**
   * Elastic search index type.
   *
   * @var elasticSearchType
   */
  private $elasticSearchType = NULL;

  /**
   * Create a new controller instance.
   */
  public function __construct() {
    $this->category = '';
    $this->type = 'product_detail';
    $this->limit = 10;
    $this->offset = 0;
    $this->market = [];
    $this->userLanguage = '';
    $this->query = [];
    $this->search = '';
    $this->elasticSearchIndex = getenv('ELASTIC_SEARCH_INDEX');
    $this->elasticSearchType = getenv('ELASTIC_SEARCH_TYPE');
    $this->searchFields = [
      'title',
      'field_display_title',
      'field_markets_name',
      'field_point_value',
      'field_price',
      'field_product_categories_name',
      'field_season_name',
      'field_story',
      'field_subtitle',
      'field_tags_keywords_name',
      'field_why_there_s_only_one',
    ];
  }

  /**
   * Search data from elastic.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User activity status.
   */
  public function search(Request $request) {
    // Fetch user id from oauth token.
    $uid = Helper::getJtiToken($request);
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    // Check elastic client.
    $client = Helper::checkElasticClient();
    if (!$client) {
      return FALSE;
    }
    $this->buildQuery($request, $uid);
    if (empty($this->search)) {
      return Helper::jsonError('Please enter search keyword.', 400);
    }
    if (strlen($this->search) < 3) {
      return Helper::jsonError('You must include at least one positive keyword with 3 characters or more.', 400);
    }
    // Fetch the search response from elastic.
    $data = $client->search($this->query);
    if (!isset($data['hits']['hits'][0])) {
      return new Response(NULL, Response::HTTP_NO_CONTENT);
    }
    $nid_user_activity = $result = $response = [];
    // Build array of node id with image id.
    foreach ($data['hits']['hits'] as $key => $value) {
      $image_id = !empty($value['_source']['field_field_product_image']) ? $value['_source']['field_field_product_image'][0] : '';
      $fids[] = [
        'nid' => $value['_source']['nid'][0],
        'imageId' => $image_id,
      ];
    }
    // Fetch image styles.
    $image_uris = Helper::getUriByMediaId(array_column($fids, "imageId"));
    $result = Helper::buildImageStyles($fids, $image_uris);
    // Prepare the search response.
    foreach ($data['hits']['hits'] as $key => $value) {
      $nid_user_activity[] = $value['_source']['nid'][0];
      $nid = $value['_source']['nid'][0];
      $image_style = Helper::buildImageResponse($result, $nid);
      $response['results'][] = [
        'nid' => $nid,
        'imageLarge' => $image_style['imageLarge'],
        'imageMedium' => $image_style['imageMedium'],
        'imageSmall' => $image_style['imageSmall'],
        'title' => $value['_source']['field_display_title'][0],
        'subTitle' => isset($value['_source']['field_subtitle'][0]) ? $value['_source']['field_subtitle'][0] : '',
        'description' => isset($value['_source']['field_description'][0]) ? $value['_source']['field_description'][0] : '',
        'type' => $value['_source']['type'][0],
        'pointValue' => isset($value['_source']['field_point_value'][0]) ? $value['_source']['field_point_value'][0] : '',
      ];
    }
    // Fetch user flag activities.
    $user_activity_result = Helper::getUserFlagActivities(serialize($nid_user_activity), $uid);
    $total_count = $data['hits']['total'] - $this->offset;
    // Build pagination.
    $response['pager'] = [
      "count" => ($total_count > 0) ? $total_count : 0,
      "pages" => ceil($total_count / $this->limit),
      "items_per_page" => $this->limit,
      "current_page" => 0,
      "next_page" => 1,
    ];
    $response['userActivity'] = json_decode($user_activity_result->getContent(), TRUE);
    $response['userActivity'] = $response['userActivity']['data']['userActivities'];

    return new Response($response, 200);
  }

  /**
   * Build elastic query.
   *
   * @param mixed $request
   *   Rest resource query parameters.
   * @param int $uid
   *   User uid.
   */
  protected function buildQuery($request, $uid) {
    // Fetch search filter parameters.
    $this->getSearchParams($request, $uid);
    // Build elastic query filters.
    $this->buildFilter();
  }

  /**
   * Fetch search filter parameters.
   *
   * @param mixed $request
   *   Rest resource query parameters.
   * @param int $uid
   *   User uid.
   */
  protected function getSearchParams($request, $uid) {
    $market_filter = [];
    // Fetch current user market & language.
    $user_info = UserModel::getUserInfoByUid($uid, ['market', 'language']);
    $this->userLanguage = $user_info[0]->language;
    foreach ($user_info as $value) {
      $this->market[] = [
        'match' => ['field_markets' => $value->market],
      ];
    }
    $this->search = $request->input('searchTerm');
    $this->category = !empty($request->input('categoryId')) ? $request->input('categoryId') : $this->category;
    $this->type = !empty($request->input('type')) ? $request->input('type') : $this->type;
    $this->limit = !empty($request->input('limit')) ? $request->input('limit') : $this->limit;
    $this->offset = !empty($request->input('offset')) ? $request->input('offset') : $this->offset;
  }

  /**
   * Build elastic query filters.
   */
  protected function buildFilter() {
    $this->query = [
      'index' => $this->elasticSearchIndex,
      'type' => $this->elasticSearchType,
      // Results starting from.
      'from' => $this->offset,
      // Limit of results.
      'size' => $this->limit,
      'body' => [
        'query' => [
          'bool' => [
            'must' => [
              0 => [
                'multi_match' => [
                  'query' => $this->search,
                  'fields' => $this->searchFields,
                ],
              ],
              1 => [
                'match' => ['langcode' => $this->userLanguage],
              ],
              2 => [
                'match' => ['status' => "1"],
              ],
            ],
          ],
        ],
      ],
    ];
    // Alter the elastic filter based on category & type.
    if (!empty($this->category) || !empty($this->type)) {
      $filter = [];
      $operator = 'must';
      if (!empty($this->category)) {
        $filter[] = [
          'match' => ['field_product_categories' => $this->category],
        ];
      }
      if (!empty($this->type)) {
        $filter[] = [
          'match' => ['type' => $this->type],
        ];
      }
    }
    // Alter the query params based on operator and filter.
    if (isset($operator) && $operator == 'must') {
      $this->query['body']['query']['bool'][$operator][] = $filter;
      $this->query['body']['query']['bool']['should'][] = $this->market;
    }
  }

}
