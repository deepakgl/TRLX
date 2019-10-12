<?php

namespace App\Model\Elastic;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Exception;
use Illuminate\Http\Response;

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

	public static function getElasticSearchParam($fieldName, $fieldVal, $size, $from)
	{
		if ($fieldVal) {
			$query = [
				'match_phrase_prefix' => [
					'name' => [
						"query" => $fieldVal
					]
				]
			];
		} else {
			$query = [
				'query_string' => [
					'query' => $fieldName . ":*"
				]
			];
		}
		return $params = [
			'index' => getenv("ELASTIC_ENV") . '_user',
			'type' => 'user',
			'body' => [
				'sort' => [
					'_score'
				],
				'query' => $query,
				'size' => $size,
				'from' => $from
			]
		];
	}

	public static function search($client, $params) {
		try {
			$items = $client->search($params);
			return $items;
		}
		catch (Exception $e) {
			return ['success' => false, 'error' => $e->getMessage(), 'code' => self::getExceptionStatusCode($e)];
		}
	}

	private static function getExceptionStatusCode($exception) {
		$code = Response::HTTP_NO_CONTENT;
		if ($exception instanceof NoNodesAvailableException) {
			$code = Response::HTTP_INTERNAL_SERVER_ERROR;
		}
		return $code;
	}

}
