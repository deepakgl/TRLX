<?php

namespace App\Model\Elastic;

/**
 * Purpose of this class is to check, fetch and update elastic user index.
 */
class ElasticUserModel {

  /**
   * Fetch user data from elastic.
   *
   * @param int $uid
   *   User id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   Elastic user data.
   */
  public static function fetchElasticUserData($uid, $client) {
    $params = [
      'index' => getenv("ELASTIC_ENV") . '_user',
      'type' => 'user',
      'id' => 'user_' . $uid,
    ];
    try {
      $response = $client->get($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Update user data in elastic.
   *
   * @param array $params
   *   Elastic params.
   * @param int $uid
   *   User id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function updateElasticUserData(array $params, $uid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_user';
    $params['type'] = 'user';
    $params['id'] = 'user_' . $uid;
    try {
      $response = $client->update($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Delete user data from elastic.
   *
   * @param int $uid
   *   User id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function deleteElasticUserData($uid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_user';
    $params['type'] = 'user';
    $params['id'] = 'user_' . $uid;
    try {
      $response = $client->delete($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Create user data in elastic.
   *
   * @param array $params
   *   Elastic params.
   * @param int $uid
   *   User id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   Elastic client.
   */
  public static function createElasticUserIndex(array $params, $uid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_user';
    $params['type'] = 'user';
    $params['id'] = 'user_' . $uid;
    try {
      $response = $client->index($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Check whether elastic user index exists.
   *
   * @param int $uid
   *   User id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function checkElasticUserIndex($uid, $client) {
    $params = [
      'index' => getenv("ELASTIC_ENV") . '_user',
      'type' => 'user',
      'id' => 'user_' . $uid,
    ];
    try {
      $exists = $client->exists($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $exists;
  }

}
