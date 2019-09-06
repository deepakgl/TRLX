<?php

namespace Drupal\trlx_utility;

use Predis\Client;
use Predis\Connection\ConnectionException;

/**
 * Caching functionality for redis.
 *
 * Provides helper function :
 *
 * Set()              : Saves the cache data in redis
 * getExpiration()    : Returns the expiration time.
 * getKey()           : Returns the key for given cid and bin.
 * deleteMultiple()   : Deletes cached data given cache keys.
 * deleteKeyPattern() : Deletes cached data given patterns.
 * getMultiple()      : Returns the data for given cid value.
 */
class PRedisCache {

  /**
   * Redis default host.
   */
  const REDIS_DEFAULT_HOST = "127.0.0.1";

  /**
   * Redis default port.
   */
  const REDIS_DEFAULT_PORT = 6379;

  /**
   * Redis default database: will select none (Database 0).
   */
  const REDIS_DEFAULT_BASE = NULL;

  /**
   * Redis default password: will not authenticate.
   */
  const REDIS_DEFAULT_PASSWORD = NULL;

  /**
   * Default lifetime for permanent items.Approximatively 1 year.
   */
  const LIFETIME_PERM_DEFAULT = 31536000;

  /**
   * Constructor to initialize the redis client object.
   */
  public function __construct(
    $host = self::REDIS_DEFAULT_HOST,
    $port = self::REDIS_DEFAULT_PORT,
    $base = self::REDIS_DEFAULT_BASE,
    $password = NULL) {
    $this->client = $this->getClientInstance($host, $port, $base, $password);
  }

  /**
   * Create cache entry.
   *
   * @param mixed $data
   *   The data to be cached.
   * @param string $cid
   *   The cid value of the key.
   * @param string $bin
   *   The bin value of the key.
   * @param string $id
   *   The id value of the key.
   * @param int $expire
   *   The expiration time of the key.
   * @param string[] $tags
   *   Tags.
   *
   * @return array
   *   The hash value for the key.
   */
  public function createEntryHash($data, $cid, $bin, $id, $expire = NULL, array $tags = []) {
    $hash = [
      'bin' => $bin,
      'cid' => $cid,
      'id' => $id,
      'created' => round(microtime(TRUE), 3),
      'expire' => $expire,
      'tags' => implode(' ', $tags),
      'valid' => 1,
    ];
    // Let redis handle the data types itself.
    if (!is_string($data)) {
      $hash['data'] = base64_encode(serialize($data));
      $hash['serialized'] = 1;
    }
    else {
      $hash['data'] = $data;
      $hash['serialized'] = 0;
    }

    return $hash;
  }

  /**
   * {@inheritdoc}
   */
  public function set($data, $cid = NULL, $bin = NULL, $id = NULL, $expire = NULL, array $tags = []) {
    $key = $this->getKey([$cid, $bin, $id]);
    // Build the cache item and save it as a hash array.
    $expire = !empty($expire) ? $expire : self::LIFETIME_PERM_DEFAULT;
    $entry = $this->createEntryHash($data, $cid, $bin, $id, $expire, $tags);
    if ($this->client) {
      // Setting the cache values.
      $pipe = $this->client;
      $pipe->multi();
      $pipe->hMset($key, $entry);
      $pipe->expire($key, $expire);
      $pipe->exec();
    }
    else {
      // Return the FALSE value is connection is not set up.
      return $this->client;
    }
  }

  /**
   * Return the key for the given cache data.
   */
  public function getKey(array $key_components) {
    return implode(array_filter($key_components), ':');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    if (!empty($cids)) {
      $keys = array_map([$this, 'getKey'], $cids);
      $this->client->del($keys);
    }
  }

  /**
   * Delete the cached data on the given pattern.
   *
   * @param string $patterns
   *   The pattern of the cached key to be deleted.
   */
  public function deleteKeyPattern($patterns) {
    foreach ($patterns as $pattern) {
      $keys = $this->client->keys($pattern);
      if (!empty(array_filter($keys))) {
        foreach ($keys as $key) {
          $this->client->del($key);
        }
      }
    }
  }

  /**
   * Get the connected client instance.
   *
   * @param string $host
   *   The host value of redis settings.
   * @param int $port
   *   The port value of redis settings.
   * @param string $base
   *   The base value of redis settings.
   * @param string $password
   *   The password value.
   *
   * @return object
   *   The client object of redis.
   */
  public function getClientInstance($host = NULL, $port = NULL, $base = NULL, $password = NULL) {
    // Set the default value for connection parameters.
    $password = empty($password) ? self::REDIS_DEFAULT_PASSWORD : $password;
    $client = new Client([
      'host'   => $host,
      'port'   => $port,
      'scheme' => 'tls',
      'password' => $password,
    ]);
    try {
      $client->connect($host, $port);
      if (isset($password)) {
        $client->auth($password);
      }
      if (isset($base) && !empty($base)) {
        $client->select($base);
      }
    }
    catch (ConnectionException $e) {
      // Redis connection error.
      // \Drupal::logger('elx_utility')->info("Connection to Redis unsuccessful.
      // Error : @error", ['@error' => $e->getMessage()]);
      return FALSE;
    }

    return $client ? $client : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple($cids, $pattern = FALSE) {
    // Avoid an error when there are no cache ids.
    if (empty($cids)) {
      return [];
    }
    $return = [];
    // Build the list of keys to fetch.
    $keys = $pattern ? $this->client->keys($cids) : [$cids];
    // Optimize for the common case when only a single cache entry needs to
    // be fetched, no pipeline is needed then.
    if (count($keys) > 1) {
      $pipe = $this->client->pipeline();
      foreach ($keys as $key) {
        $pipe->hgetall($key);
      }
      $result = $pipe->exec();
    }
    else {
      $result = [$this->client->hGetAll(reset($keys))];
    }
    // Decode the data value for the serialized data.
    foreach ($result as $key => $value) {
      if (!empty($value['serialized']) && $value['serialized'] == "1") {
        $result[$key]['data'] = unserialize(base64_decode($value['data']));
      }
    }
    // Remove fetched cids from the list.
    $cids = array_diff($cids, array_keys($result));

    return $result;
  }

  /**
   * Helper function to check the redis connection.
   */
  public function getConnectionInfo() {
    return $this->client->isConnected();
  }

  /**
   * Helper function to get the redis connection host.
   */
  public function getConnectionHost() {
    return $this->client->getHost();
  }

}
