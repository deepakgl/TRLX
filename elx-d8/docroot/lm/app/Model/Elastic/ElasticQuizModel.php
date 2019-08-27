<?php

namespace App\Model\Elastic;

/**
 * Purpose of this class is to check, fetch and update elastic quiz index.
 */
class ElasticQuizModel {

  /**
   * Create quiz data in elastic.
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
  public static function createElasticQuizIndex(array $params, $elastic_params) {
    $params['index'] = getenv("ELASTIC_ENV") . '_quiz_attempt';
    $params['type'] = 'attempt';
    $params['id'] = $elastic_params['quizId'] . '_' . $elastic_params['uid'];
    try {
      $response = $elastic_params['client']->index($params);
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }

    return $response;
  }

  /**
   * Update user data in elastic.
   *
   * @param array $params
   *   Elastic params.
   * @param int $quiz_id
   *   Quiz id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function updateElasticQuizData(array $params, $elastic_params) {
    $params['index'] = getenv("ELASTIC_ENV") . '_quiz_attempt';
    $params['type'] = 'attempt';
    $params['id'] = $elastic_params['quizId'] . '_' . $elastic_params['uid'];
    try {
      $response = $elastic_params['client']->update($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $response;
  }


  /**
   * Check whether elastic quiz index exists.
   *
   * @param int $quiz_id
   *   Quiz id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return bool
   *   True or false.
   */
  public static function checkElasticQuizIndex($elastic_params) {
    $params = [
      'index' => getenv("ELASTIC_ENV") . '_quiz_attempt',
      'type' => 'attempt',
      'id' => $elastic_params['quizId'] . '_' . $elastic_params['uid'],
    ];
    try {
      $exists = $elastic_params['client']->exists($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $exists;
  }

}
