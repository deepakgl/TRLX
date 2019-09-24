<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Elastic\ElasticQuizModel;
use App\Model\Mysql\ContentModel;

/**
 * Purpose of this class is to maintain the quiz submissions.
 */
class QuizController extends Controller {

  /**
   * Create a new controller instance.
   */
  public function __construct() {}

  /**
   * Prepare last submission of quiz.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   */
  public function quiz(Request $request) {
    $uid = Helper::getJtiToken($request);
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    $client = Helper::checkElasticClient();
    if (!$client) {
      return Helper::jsonError('Elastic client not exists.', 404);
    }
    $quiz_id = $request->input('id');
    $type = ContentModel::getTypeByNid($quiz_id);
    if (empty($quiz_id)) {
      return Helper::jsonError('Please provide quiz id.', 422);
    }
    elseif (!is_int($quiz_id)) {
      return Helper::jsonError('Quiz id must be numeric.', 422);
    }
    elseif(empty($type->type) || $type->type != 'quiz') {
      return Helper::jsonError('Quiz id is not valid.', 422);
    }
    $elastic_params = [
      'quizId' => $quiz_id,
      'uid' => $uid,
      'client' => $client,
      'request_params' => [
        'quizId' => $quiz_id,
        'uid' => $uid,
        'attemptStartTime' => $request->input('attemptStartTime'),
        'attemptFinishTime' => $request->input('attemptFinishTime'),
        'userActivity' => $request->input('userActivity'),
      ],
    ];
    $message['message'] = 'Unable to save the quiz!';
    $status = 422;
    if (!ElasticQuizModel::checkElasticQuizIndex($elastic_params)) {
      $params['body'] = $elastic_params['request_params'];
      $reponse = ElasticQuizModel::createElasticQuizIndex($params,
      $elastic_params);
    }
    else {
      $params['body'] = [
        'doc' => $elastic_params['request_params'],
        'doc_as_upsert' => TRUE,
      ];
      $reponse = ElasticQuizModel::updateElasticQuizData($params,
      $elastic_params);
    }
    if ($reponse) {
      $quiz_summary = ContentModel::quizAttemptSummary($quiz_id, $uid);
      $status = 200;
      $message['message'] = 'Quiz Saved Successfully!';
    }

    return new Response($message, $status);
  }

}
