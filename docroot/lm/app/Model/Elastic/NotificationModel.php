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

  /**
   * TRLX push notifications logic.
   */
  public static function trlxPushNotifications($data) {
    // Push notification middleware API url.
    $push_notification_url = getenv("PUSH_NOTIFICATION_ENDPOINT");
    // Initializes a new cURL session.
    $curl = curl_init($push_notification_url);
    // 1. Set the CURLOPT_RETURNTRANSFER option to true.
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    // 2. Set the CURLOPT_POST option to true for POST request.
    curl_setopt($curl, CURLOPT_POST, TRUE);
    // 3. Set the request data as JSON using json_encode function.
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    // 4. Set custom headers for RapidAPI Auth and Content-Type header.
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'X-TRACE-OPERATION': "PUSH-NOTIFICATION",
      'X-TRACE-REQUESTID': time() . mt_rand(1000, 9999),
    ]);
    // Execute cURL request with all previous settings.
    $response = curl_exec($curl);
    // Close cURL session.
    curl_close($curl);
  }

}
