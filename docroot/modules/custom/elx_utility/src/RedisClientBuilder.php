<?php

namespace Drupal\elx_utility;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Builds the redis client.
 */
class RedisClientBuilder {

  /**
   * Returns the redis client object.
   */
  public static function getRedisClientObject($key) {
    $client = [];
    // Get the redis settings variable for the site.
    $settings = \Drupal::config('elx_utility.settings')->getRawData();
    try {
      // Pass the configuration variable to redis server for connection.
      $client = new PRedisCache($settings['redis_host'],
      $settings['redis_port'], $settings['redis_base'],
      $settings['redis_password']);
      if (!$client->client) {
        throw new \Exception('Redis Exception');
      }
      if ($client->getConnectionInfo() && $key != 'check') {
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
          'user' => \Drupal::currentUser(),
          'request_uri' => $request_uri,
          'data' => $e->getMessage(),
        ]);
      throw new \Exception('Redis Exception');
    }

    return $client;
  }

}
