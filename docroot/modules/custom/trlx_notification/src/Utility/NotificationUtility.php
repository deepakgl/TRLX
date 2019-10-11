<?php

namespace Drupal\trlx_notification\Utility;

use Elasticsearch\ClientBuilder;
use Drupal\trlx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Response;

/**
 * Purpose of this class is to build common object.
 */
class NotificationUtility {

  /**
   * Search string.
   *
   * @var query
   */
  private $query = NULL;
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
   * Create a new controller instance.
   */
  public function __construct() {
    $this->query = [];
    $this->search = '';
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
   * Create user data in elastic.
   *
   * @param array $params
   *   Elastic params.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   Elastic client.
   */
  public static function createElasticNotificationIndex(array $params, $client) {
    $config = \Drupal::config('trlx_notification.settings');
    $params['index'] = $config->get('search_index');
    $params['type'] = $config->get('search_index_type');
    $params['id'] = time() . mt_rand(1000, 9999);
    try {
      $response = $client->index($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Get elastic client.
   *
   * @return object
   *   Elastic client.
   */
  public static function getElasticClient() {
    $config = \Drupal::config('trlx_notification.settings');
    $hosts = [
      [
        'host' => $config->get('host'),
        'port' => $config->get('port'),
        'scheme' => $config->get('scheme'),
      ],
    ];

    return (ClientBuilder::create()->setHosts($hosts)->build());
  }

  /**
   * Get elastic client.
   *
   * @return object
   *   Elastic client.
   */
  public static function getUsersBasedOnBrandsAndMarkets($markets, $brand_key = []) {
    $commonUtility = new CommonUtility();
    $config = \Drupal::config('trlx_notification.settings');
    $client = NotificationUtility::getElasticClient();
    if (!$client) {
      return $commonUtility->errorResponse(t('No alive nodes found in the cluster.'), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $query = NotificationUtility::buildFilter($config, $markets, $brand_key);
    // Fetch the search response from elastic.
    $data = $client->search($query);
    if (!isset($data['hits']['hits'][0])) {
      return [];
    }
    else {
      $uid = [];
      foreach ($data['hits']['hits'] as $key => $value) {
        $uid[] = $value['_source']['uid'];
      }
    }
    return $uid;
  }

  /**
   * Build elastic query filters.
   */
  public static function buildFilter($config, $markets, $brand_key) {
    $query = [
      'index' => $config->get('user_index'),
      'type' => $config->get('user_index_type'),
      'size' => 10000,
      'body' => [
        'query' => [
          'bool' => [
            'must' => [
              0 => [
                'terms' => ['market' => $markets],
              ],
            ],
          ],
        ],
      ],
    ];
    // Alter the elastic filter based on brand keys array.
    if (!empty($brand_key)) {
      $filter = [];
      $operator = 'must';
      $filter[] = [
        'terms' => ['brands' => $brand_key],
      ];
    }
    // Alter the query params based on operator and filter.
    if (isset($operator) && $operator == 'must') {
      $query['body']['query']['bool'][$operator][] = $filter;
    }
    return $query;
  }

}
