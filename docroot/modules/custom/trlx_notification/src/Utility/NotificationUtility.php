<?php

namespace Drupal\trlx_notification\Utility;

use Elasticsearch\ClientBuilder;

/**
 * Purpose of this class is to build common object.
 */
class NotificationUtility {

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

}
