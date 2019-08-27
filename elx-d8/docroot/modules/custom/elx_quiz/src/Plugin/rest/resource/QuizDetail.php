<?php

namespace Drupal\elx_quiz\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\RedisClientBuilder;
use Drupal\Component\Serialization\Json;

/**
 * Provides a quiz detail resource.
 *
 * @RestResource(
 *   id = "quiz_detail",
 *   label = @Translation("Quiz Detail"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/quiz"
 *   }
 * )
 */
class QuizDetail extends ResourceBase {

  /**
   * Rest resource for quiz detail.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $quiz_id = $request->query->get('quizId');
    if (empty($quiz_id)) {
      return new JsonResponse('Following params required: quizId', 400);
    }
    $lang = \Drupal::currentUser()->getPreferredLangcode();
    if (!$this->isValidQuizNid($quiz_id, $lang)) {
      return new JsonResponse('Node is either unpublished or not exist.', 422, [], FALSE);
    }
    $enable_redis = \Drupal::config('elx_quiz.settings')->get('enable_redis_cache');
    $uid = \Drupal::currentUser()->id();
    $user_utility = new UserUtility();
    $roles = $user_utility->getUserRoles($uid);
    $output = $this->quizJsonById($quiz_id);
    if ($enable_redis == 1 && !$roles) {
      $env = \Drupal::config('elx_quiz.settings')->get('environment');
      // Get user market by user id.
      $user_market = $user_utility->getMarketByUserId($uid);
      // Prepare redis key.
      $key = $env . ':quizDetails:' . $user_market . '_' . $roles[0] . '_' . $quiz_id . '_' . $lang;
      try {
        // Creating Redis connection object.
        list($cached_data, $redis_client) =
        RedisClientBuilder::getRedisClientObject($key);
        // Get the data from the redis cache with key value.
        if (!empty($cached_data)) {
          return new JsonResponse($cached_data, 200, [], TRUE);
        }
      }
      catch (\Exception $e) {
        $output = $this->quizJsonById($quiz_id);

        return new JsonResponse($output, 200, [], TRUE);
      }
      if (!empty(array_filter(JSON::decode($output, TRUE)))) {
        $redis_key = explode(':', $key);
        $redis_client->set($output, $redis_key[0], $redis_key[1], $redis_key[2]);
      }
    }

    return new JsonResponse($output, 200, [], TRUE);
  }

  /**
   * Fetch quiz data via id.
   *
   * @param int $nid
   *   Quiz node id.
   *
   * @return array
   *   Quiz data.
   */
  protected function quizJsonById($nid) {
    $lang = \Drupal::currentUser()->getPreferredLangcode();
    $query = \Drupal::database()->select('node__field_quiz_json', 'qj')
      ->fields('qj', ['field_quiz_json_value'])
      ->condition('qj.bundle', 'quiz', '=')
      ->condition('qj.langcode', $lang, '=')
      ->condition('qj.entity_id', $nid, '=');
    $result = $query->execute()->fetchAssoc();

    return $result['field_quiz_json_value'];
  }

  /**
   * Check if node id exists.
   *
   * @param int $nid
   *   Node id.
   * @param  string $lang
   *   Language code.
   *
   * @return bool
   *   True or false.
   */
  public function isValidQuizNid($nid, $lang) {
    $query = \Drupal::database()->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'quiz', '=')
      ->condition('n.nid', $nid, '=')
      ->condition('n.langcode', $lang, '=')
      ->condition('n.status', 1, '=')
      ->range(0, 1);
    $result = $query->execute()->fetchAssoc();
    if (empty($result)) {
      return FALSE;
    }

    return TRUE;
  }

}
