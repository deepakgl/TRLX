<?php

namespace Drupal\trlx_utility;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Component\Serialization\Json;

/**
 * Builds the redis client.
 */
class RedisClientBuilder {

  /**
   * Returns the redis client object.
   */
  public static function getRedisClientObject($key) {
    global $_userData;
    $client = [];
    return $client;
    // Get the redis settings variable for the site.
    $settings = \Drupal::config('elx_utility.settings')->getRawData();
    try {
      // Pass the configuration variable to redis server for connection.
      $client = new PRedisCache($settings['redis_host'],
      $settings['redis_port'], $settings['redis_base'],
      $settings['redis_password']);
      // if (!$client->client) {
      //   throw new \Exception('Redis Exception');
      // }
      if ($client->client && $client->getConnectionInfo() && $key != 'check') {
        $cached_data = $client->getMultiple($key);
        $decode_cached_data = JSON::decode($cached_data[0]['data'], TRUE);
        if (!empty($decode_cached_data)) {
          $data = new JsonResponse($cached_data[0]['data'], 200, [], TRUE);
          if (is_object($data)) {
            $data = $data->getContent();
          }
          return [$data, $client];
        }
        return [$decode_cached_data, $client];
      }
    }
    catch (\Exception $e) {
      global $base_url;
      $request_uri = $base_url . \Drupal::request()->getRequestUri();
      \Drupal::service('logger.stdout')->log(RfcLogLevel::ERROR, $e
        ->getMessage(), [
          'user' => $_userData,
          'request_uri' => $request_uri,
          'data' => $e->getMessage(),
        ]);
      throw new \Exception('Redis Exception');
    }

    return $client;
  }

}
