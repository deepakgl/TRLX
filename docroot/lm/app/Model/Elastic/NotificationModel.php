<?php

namespace App\Model\Elastic;

use Elasticsearch\ClientBuilder;

/**
 * Purpose of this class is to check, fetch and update notification.
 */
class NotificationModel {

  /**
   * Get elastic client.
   *
   * @return object
   *   Returns the elastic client.
   */
  public static function getElasticClient() {
    $hosts = [
      [
        'host' => getenv("ELASTIC_URL"),
        'port' => getenv("ELASTIC_PORT"),
        'scheme' => getenv("ELASTIC_SCHEME"),
      ],
    ];

    return (ClientBuilder::create()->setHosts($hosts)->build());
  }

  /**
   * Save data in elastic search index from queue.
   */
  public static function saveIndexes(array $indexValues = []) {
    $client = self::getElasticClient();
    $indexParams['index'] = getenv("ELASTIC_SEARCH_NOTIFICATION_INDEX");
    $exist = $client->indices()->exists($indexParams);
    // If index not exist, create new index.
    if (!$exist) {
      $params['body'] = [];
      $output = self::createElasticNotificationIndex($params, $client);
    }

    try {
      $params = [
        'index' => getenv("ELASTIC_SEARCH_NOTIFICATION_INDEX"),
        'type' => getenv("ELASTIC_SEARCH_NOTIFICATION_TYPE"),
        'id' => time() . mt_rand(1000, 9999),
        'body' => [
          'notificationType' => $indexValues['notificationType'],
          'userId' => $indexValues['userId'],
          'notificationHeading' => $indexValues['notificationHeading'],
          'notificationText' => $indexValues['notificationText'],
          'notificationDate' => $indexValues['notificationDate'],
          'notificationLink' => $indexValues['notificationLink'],
          'notificationLinkType' => $indexValues['notificationLinkType'],
          'notificationBrandKey' => $indexValues['notificationBrandKey'],
          'notificationBrandName' => $indexValues['notificationBrandName'],
          'notificationFlag' => 0,
          'notificationLanguage' => $indexValues['notificationLanguage'],
        ],
      ];
      $client->index($params);
    }
    catch (Exception $exp) {
      watchdog_exception('trlx_notification', $exp);
    }
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
    $params['index'] = getenv("ELASTIC_SEARCH_NOTIFICATION_INDEX");
    $params['type'] = getenv("ELASTIC_SEARCH_NOTIFICATION_TYPE");
    $params['id'] = time() . mt_rand(1000, 9999);
    try {
      $response = $client->index($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

}
