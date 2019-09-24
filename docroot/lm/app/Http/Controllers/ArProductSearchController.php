<?php

namespace App\Http\Controllers;

use App\Support\Helper;
use App\Model\Mysql\UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Purpose of building this class is to get the data from elastic based on the.
 *
 * Searched keyword.
 */
class ArProductSearchController extends Controller {
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
    $this->market = [];
    $this->query = [];
    $this->search = '';
    $this->elasticSearchIndex = getenv('ELASTIC_SEARCH_AR_INDEX');
    $this->elasticSearchType = getenv('ELASTIC_SEARCH_AR_TYPE');
  }

  /**
   * Product search data from elastic.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Product object.
   */
  public function productSearch(Request $request) {
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
    // Fetch the search response from elastic.
    $data = $client->search($this->query);
    if (!isset($data['hits']['hits'][0])) {
      return new Response(NULL, Response::HTTP_NO_CONTENT);
    }
    $response = [];
    /* @todo start
        Need to add image style once we get the dimension. Please do not remove this code.
        Build array of node id with image id.
        foreach ($data['hits']['hits'] as $key => $value) {
          $image_id = !empty($value['_source']['product_image']) ? $value['_source']['product_image'][0] : '';
          $fids[] = [
            'nid' => $value['_source']['nid'][0],
            'imageId' => $image_id,
          ];
        }
        Fetch image styles.
        $image_uris = Helper::getUriByMediaId(array_column($fids, "imageId"));
        $result = Helper::buildImageStyles($fids, $image_uris);
     @todo end */
    // Prepare the search response.
    foreach ($data['hits']['hits'] as $key => $value) {
      $nid = (int) $value['_source']['nid'][0];
      $product_video = !empty($value['_source']['product_video']) ? $value['_source']['product_video'][0] : '';
      // $image_style = Helper::buildImageResponse($result, $nid);
      $image_uri = !empty($value['_source']['product_image_uri']) ? $value['_source']['product_image_uri'][0] : '' ;
      $response['results'][] = [
        'imageLarge' => $image_uri,
        'imageMedium' => $image_uri,
        'imageSmall' => $image_uri,
        'title' => $value['_source']['display_title'][0],
        'subTitle' => isset($value['_source']['subtitle'][0]) ? $value['_source']['subtitle'][0] : '',
        'nid' => $nid,
        'videoURL' => $product_video
      ];
    }

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
    // Fetch current user market & language.
    $user_info = UserModel::getUserInfoByUid($uid, ['market']);
    $this->market = [
      'match' => ['market' => $user_info[0]->market],
    ];
    $this->search = $request->input('searchTerm');
  }

  /**
   * Build elastic query filters.
   */
  protected function buildFilter() {
    $this->query = [
      'index' => $this->elasticSearchIndex,
      'type' => $this->elasticSearchType,
      'size' => 8, // Limit.
      'body' => [
        'query' => [
          'bool' => [
            'must' => [
              0 => [
                'match' => ['aggregated_field' => $this->search]
              ],
              1 => $this->market,
              2 => [
                'match' => ['ar_search' => '1']
              ],
              3 => [
                'match' => ['status' => '1'],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
