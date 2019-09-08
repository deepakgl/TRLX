<?php

namespace App\Model\Elastic;

/**
 * Purpose of this class is to alter node flag data in elastic.
 */
class FlagModel {

  /**
   * Fetch node data from elastic.
   *
   * @param int $nid
   *   Node id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   Elastic node data.
   */
  public static function fetchElasticNodeData($nid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_node_data';
    $params['type'] = 'node';
    $params['id'] = $nid;
    try {
      $response = $client->get($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Fetch node data from elastic.
   *
   * @param array $nids
   *   Node id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   Elastic node data.
   */
  public static function fetchMultipleElasticNodeData($nids, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_node_data';
    $params['type'] = 'node';
    $params['body'] = ['ids' => $nids];
    try {
      $response = $client->mget($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Update node data in elastic.
   *
   * @param array $params
   *   Elastic params.
   * @param int $nid
   *   Node id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function updateElasticNodeData(array $params, $nid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_node_data';
    $params['type'] = 'node';
    $params['id'] = $nid;
    try {
      $response = $client->update($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Check whether elastic node index exists.
   *
   * @param int $nid
   *   Node id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function checkElasticNodeIndex($nid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_node_data';
    $params['type'] = 'node';
    $params['id'] = $nid;
    try {
      $exists = $client->exists($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $exists;
  }

  /**
   * Create node index in elastic.
   *
   * @param mixed $params
   *   Elastic index params.
   * @param int $nid
   *   Node id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   Elastic client.
   */
  public static function createElasticNodeIndex($params, $nid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_node_data';
    $params['type'] = 'node';
    $params['id'] = $nid;
    try {
      $response = $client->index($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }
  
  /**
   * Delete node data from elastic.
   *
   * @param int $nid
   *   Node id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function deleteElasticNodeData($nid, $client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_node_data';
    $params['type'] = 'node';
    $params['id'] = $nid;
    try {
      $response = $client->delete($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }  

}
