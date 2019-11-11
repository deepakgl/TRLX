<?php

namespace App\Http\Controllers;

use App\Support\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\Mysql\ContentModel;
use App\Model\Mysql\UserModel;

/**
 * Purpose of building this class is to get the data from elastic based on the.
 *
 * Searched keyword.
 */
class SearchController extends Controller {
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
    $this->limit = 10;
    $this->offset = 0;
    $this->market = [];
    $this->userLanguage = '';
    $this->query = [];
    $this->search = '';
    $this->elasticSearchIndex = getenv('ELASTIC_SEARCH_INDEX');
    $this->elasticSearchType = getenv('ELASTIC_SEARCH_TYPE');
    $this->searchFields = [
      'name',
      'field_sub_title_1',
      'field_display_title',
      'field_subtitle',
      'field_sub_title',
      'field_headline',
      'field_question',
      'body',
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

    $validatedData = $this->validate($request, [
      '_format' => 'required|format',
      'language' => 'required|languagecode',
    ]);
    // Check elastic client.
    $client = Helper::checkElasticClient();
    if (!$client) {
      return FALSE;
    }
    $lang = $validatedData['language'];
    $this->buildQuery($request, $lang);
    if (empty($this->search)) {
      return Helper::jsonError('Please enter search keyword.', 400);
    }
    if (strlen(trim($this->search)) < 3) {
      return Helper::jsonError('You must include at least one positive keyword with 3 characters or more.', 422);
    }
    // Fetch the search response from elastic.
    $data = $client->search($this->query);
    if (!isset($data['hits']['hits'][0])) {
      return new Response(NULL, Response::HTTP_NO_CONTENT);
    }
    $result = $response = [];
    // Build array of node id with image id.
    $content_type = '';
    foreach ($data['hits']['hits'] as $key => $value) {
      if (!empty($value['_source']['vid'][0])) {
        $image_id = !empty($value['_source']['field_image']) ? $value['_source']['field_image'][0] : '';
      }
      else {
        if ($value['_source']['type'][0] == 'tools') {
          $image_id = !empty($value['_source']['field_tool_thumbnail']) ? $value['_source']['field_tool_thumbnail'][0] : '';
        }
        elseif ($value['_source']['type'][0] == 'product_detail') {
          $image_id = !empty($value['_source']['field_field_product_image']) ? $value['_source']['field_field_product_image'][0] : '';
        }
        elseif ($value['_source']['type'][0] == 'brand_story') {
          $image_id = !empty($value['_source']['field_featured_image']) ? $value['_source']['field_featured_image'][0] : '';
        }
        else {
          $image_id = !empty($value['_source']['field_hero_image']) ? $value['_source']['field_hero_image'][0] : '';
        }
      }
      $tid = isset($value['_source']['tid'][0]) ? $value['_source']['tid'][0] : '';
      $nid = isset($value['_source']['nid'][0]) ? $value['_source']['nid'][0] : '';
      $fids[] = [
        'nid' => !empty($nid) ? $nid : $tid,
        'imageId' => $image_id,
      ];
    }
    // Fetch image styles.
    $image_uris = Helper::getUriByMediaId(array_column($fids, "imageId"));
    $result = Helper::buildImageStyles($fids, $image_uris);

    // Prepare the search response.
    // Get created date.
    $res = [];
    foreach ($data['hits']['hits'] as $value) {
      if (isset($value['_source']['type'][0]) && $value['_source']['type'][0] == 'brand_story') {
        $res[] = $value['_source']['created'];
      }
    }
    $created = [];
    foreach ($res as $key => $value) {
      if (is_array($value)) {
        $created = array_merge($created, array_flatten($value));
      }
      else {
        $created[$key] = $value;
      }
    }
    foreach ($data['hits']['hits'] as $key => $value) {
      $nid = isset($value['_source']['nid'][0]) ? $value['_source']['nid'][0] : '';
      $tid = isset($value['_source']['tid'][0]) ? $value['_source']['tid'][0] : '';
      $img = !empty($nid) ? $nid : $tid;
      $image_style = Helper::buildImageResponse($result, $img);
      $type = isset($value['_source']['type'][0]) ? $value['_source']['type'][0] : '';
      $category_key = 'faqhelp';
      $category_value = $category_name = 'Help FAQ';
      // Get displaytitle on based on content type.
      if (!empty($value['_source']['vid'][0])) {
        $display_title = isset($value['_source']['name'][0]) ? $value['_source']['name'][0] : '';
        $content_type = 'Level';
        $sub_title = isset($value['_source']['field_sub_title_1'][0]) ? $value['_source']['field_sub_title_1'][0] : '';
        $type = $value['_source']['vid'][0];
      }
      elseif ($value['_source']['type'][0] == 'level_interactive_content') {
        $display_title = isset($value['_source']['field_headline'][0]) ? $value['_source']['field_headline'][0] : '';
        $lesson_brand_key = ContentModel::getLessonBrandKeyByTid($value['_source']['field_learning_category'][0]);
        list($cs_value, $cs_key) = ContentModel::getLessonSectionKeyByTid($value['_source']['field_learning_category'][0]);
        $category_key = !empty($lesson_brand_key) ? 'brands' : $cs_key;
        $category_value = !empty($lesson_brand_key) ? 'Brands' : $cs_value;
        $category_name = 'Lesson';
        $sub_title = isset($value['_source']['field_subtitle'][0]) ? $value['_source']['field_subtitle'][0] : '';
      }
      elseif ($value['_source']['type'][0] == 'faq') {
        $display_title = isset($value['_source']['field_question'][0]) ? $value['_source']['field_question'][0] : '';
        $content_type = 'Brand question';
        $sub_title = isset($value['_source']['field_subtitle'][0]) ? $value['_source']['field_subtitle'][0] : '';
      }
      elseif ($value['_source']['type'][0] == 'tools') {
        $display_title = isset($value['_source']['field_display_title'][0]) ? $value['_source']['field_display_title'][0] : '';
        $content_type = 'Videos';
        $sub_title = isset($value['_source']['field_subtitle'][0]) ? $value['_source']['field_subtitle'][0] : '';
      }
      elseif ($value['_source']['type'][0] == 'product_detail') {
        $display_title = isset($value['_source']['field_display_title'][0]) ? $value['_source']['field_display_title'][0] : '';
        $content_type = 'Factsheets';
        $sub_title = isset($value['_source']['field_subtitle'][0]) ? $value['_source']['field_subtitle'][0] : '';
      }
      elseif ($value['_source']['type'][0] == 'brand_story') {
        $display_title = isset($value['_source']['field_display_title'][0]) ? $value['_source']['field_display_title'][0] : '';
        $content_type = 'Brand story';
        $sub_title = isset($value['_source']['field_subtitle'][0]) ? $value['_source']['field_subtitle'][0] : '';
      }
      elseif ($value['_source']['type'][0] == 'stories') {
        $display_title = isset($value['_source']['field_display_title'][0]) ? $value['_source']['field_display_title'][0] : '';
        $content_type = 'Story';
        $sub_title = isset($value['_source']['field_sub_title'][0]) ? $value['_source']['field_sub_title'][0] : '';
      }
      else {
        $display_title = isset($value['_source']['field_display_title'][0]) ? $value['_source']['field_display_title'][0] : '';
        $sub_title = isset($value['_source']['field_subtitle'][0]) ? $value['_source']['field_subtitle'][0] : '';
      }

      // Get category on based on brand and content section.
      $category = [];
      $brand_key = 0;
      $brandinfo = ContentModel::getBrandTermIds();
      if (isset($value['_source']['field_brands'][0])) {
        $category_key = 'brands';
        $category_value = 'Brands';
        $category_name = $content_type;
        foreach ($brandinfo as $key => $brand) {
          if ($brand['entity_id'] == $value['_source']['field_brands'][0]) {
            $brand_key = (int) $brand['field_brand_key_value'];
          }
        }
      }
      elseif (isset($value['_source']['field_content_section'][0])) {
        $category_name = ContentModel::getTermName([$value['_source']['field_content_section'][0]]);
        $category_key = ContentModel::getContentSectionKeyByTid($value['_source']['field_content_section'][0]);
        $category_value = implode(" ", $category_name);
        $category_name = $content_type;
      }
      elseif (isset($value['_source']['field_brands_1'][0])) {
        $category_key = 'brands';
        $category_value = 'Brands';
        $category_name = $content_type;
        foreach ($brandinfo as $key => $brand) {
          if ($brand['entity_id'] == $value['_source']['field_brands_1'][0]) {
            $brand_key = (int) $brand['field_brand_key_value'];
          }
        }
      }
      elseif (isset($value['_source']['field_content_section_1'][0])) {
        $category_name = ContentModel::getTermName([$value['_source']['field_content_section_1'][0]]);
        $category_key = ContentModel::getContentSectionKeyByTid($value['_source']['field_content_section_1'][0]);
        $category_value = implode(" ", $category_name);
        $category_name = $content_type;
      }

      if (isset($value['_source']['type'][0]) && $value['_source']['type'][0] == 'brand_story') {
        if ($value['_source']['created'][0] == max($created)) {
          $response[] = [
            'nid' => isset($nid) ? $nid : '',
            'tid' => $tid,
            'imageLarge' => $image_style['imageLarge'],
            'imageMedium' => $image_style['imageMedium'],
            'imageSmall' => $image_style['imageSmall'],
            'displayTitle' => $display_title,
            'subTitle' => $sub_title,
            'brandKey' => $brand_key,
            'type' => $type,
            'pointValue' => isset($value['_source']['field_point_value'][0]) ? (int) $value['_source']['field_point_value'][0] : '',
            'categoryKey' => $category_key,
            'categoryValue' => $category_name,
            'categoryName' => $category_value,
          ];
        }
      }
      else {
        $response[] = [
          'nid' => isset($nid) ? $nid : '',
          'tid' => $tid,
          'imageLarge' => $image_style['imageLarge'],
          'imageMedium' => $image_style['imageMedium'],
          'imageSmall' => $image_style['imageSmall'],
          'displayTitle' => $display_title,
          'subTitle' => $sub_title,
          'brandKey' => $brand_key,
          'type' => $type,
          'pointValue' => isset($value['_source']['field_point_value'][0]) ? (int) $value['_source']['field_point_value'][0] : '',
          'categoryKey' => $category_key,
          'categoryValue' => $category_name,
          'categoryName' => $category_value,
        ];
      }
    }
    foreach ($response as $key => $element) {
      $alter_data[$element['categoryKey']]['response'][] = $element;
    }
    $results = array_values($alter_data);
    // Show response search category wise.
    $s_response['results'] = [];
    foreach ($results as $key => $value) {
      $s_response['results'][$key]['categoryKey'] = $value['response'][0]['categoryKey'];
      $s_response['results'][$key]['categoryValue'] = $value['response'][0]['categoryName'];
      foreach ($value['response'] as $key_unset => $value_unset) {
        unset($value['response'][$key_unset]['categoryName']);
      }
      $s_response['results'][$key]['response'] = $value['response'];
    }
    $brand_story_count = [];
    foreach ($data['hits']['hits'] as $brand_key => $brand_value) {
      $type_name = isset($brand_value['_source']['type'][0]) ? $brand_value['_source']['type'][0] : $brand_value['_source']['vid'][0];
      if ($type_name == 'brand_story') {
        $brand_story_count[] = 1;
      }
    }
    $brand_count = 0;
    if (!empty(count($brand_story_count))) {
      $brand_count = count($brand_story_count) - 1;
    }
    $total_count = ($data['hits']['total'] - $brand_count) - $this->offset;
    // Build pagination.
    $s_response['pager'] = [
      "count" => ($total_count > 0) ? $total_count : 0,
      "pages" => ceil($total_count / $this->limit),
      "items_per_page" => $this->limit,
      "current_page" => 0,
      "next_page" => 1,
    ];

    return new Response($s_response, 200);
  }

  /**
   * Build elastic query.
   *
   * @param mixed $request
   *   Rest resource query parameters.
   * @param int $lang
   *   User language.
   */
  protected function buildQuery($request, $lang) {
    // Fetch search filter parameters.
    $this->getSearchParams($request, $lang);
    // Build elastic query filters.
    $this->buildFilter();
  }

  /**
   * Fetch search filter parameters.
   *
   * @param mixed $request
   *   Rest resource query parameters.
   * @param int $lang
   *   User langcode.
   */
  protected function getSearchParams($request, $lang) {
    global $_userData;
    $region = UserModel::getMarketByUserData();
    foreach ($region as $value) {
      $this->market[] = [
        'match' => ['field_markets' => $value],
      ];
    }
    $user_brand = $_userData->brands;
    $brandinfo = ContentModel::getBrandTermIds();
    $brand_data = [];
    foreach ($brandinfo as $key => $value) {
      if (in_array($value['field_brand_key_value'], $user_brand)) {
        array_push($brand_data, $value['entity_id']);
      }
    }
    $this->field_brands_1 = $this->field_brands = [];
    foreach ($brand_data as $value) {
      $this->field_brands_1[] = [
        'match' => ['field_brands_1' => $value],
      ];
      $this->field_brands[] = [
        'match' => ['field_brands' => $value],
      ];
    }

    $this->userLanguage = $lang;
    $this->search = $request->input('searchTerm');
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
                  'type' => 'phrase',
                ],
              ],
              1 => [
                'match' => ['lang' => $this->userLanguage],
              ],
              2 => [
                'match' => ['statuscode' => "1"],
              ],
              3 => [
                'bool' => [
                  'should' => [
                    0 => [
                      'bool' => [
                        'should' => $this->market,
                      ],
                    ],
                    1 => [
                      'bool' => [
                        'must_not' => [
                          'exists' => ['field' => 'field_markets'],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              4 => [
                'bool' => [
                  'should' => [
                    0 => [
                      'bool' => [
                        'should' => $this->field_brands,
                      ],
                    ],
                    1 => [
                      'bool' => [
                        'must_not' => [
                          'exists' => ['field' => 'field_brands'],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              5 => [
                'bool' => [
                  'should' => [
                    0 => [
                      'bool' => [
                        'should' => $this->field_brands_1,
                      ],
                    ],
                    1 => [
                      'bool' => [
                        'must_not' => [
                          'exists' => ['field' => 'field_brands_1'],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
