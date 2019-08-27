<?php

namespace App\Model\Elastic;

use App\Support\Helper;
use App\Model\Mysql\ContentModel;
use App\Model\Mysql\UserModel;

/**
 * Purpose of this class is to allocate points on particular action.
 */
class PointsModel {

  /**
   * Set Points on level completed.
   *
   * @param array $params
   *   Query parameters.
   */
  public static function allocatePointsOnLevelComplete(array $params) {
    $uid = $params['uid'];
    // Check whether elastic client exists.
    $client = Helper::checkElasticClient();
    // Check whether user elastic index exists.
    $exist = ElasticUserModel::checkElasticUserIndex($uid, $client);
    if (!$client || !$exist) {
      return FALSE;
    }
    // Fetch user data from elastic index.
    $response = ElasticUserModel::fetchElasticUserData($uid, $client);
    $node_ids = [];
    // Check whether key exists in user index.
    if (array_key_exists('completed_level_interactive_content', $response['_source'])) {
      $node_ids = $response['_source']['completed_level_interactive_content'];
    }
    // If node is not exists previously.
    if (!in_array($params['nid'], $node_ids)) {
      $lang = UserModel::getUserInfoByUid($uid, 'language');
      // Get point value by node id.
      $point_value = ContentModel::getPointValueByNid($params['nid'], $lang[0]->language);
      $node_ids[] = $params['nid'];
      // Prepare the elastic params and update the user index.
      $elastic_params['body'] = [
        'doc' => [
          'total_points' => isset($response['_source']['total_points']) ? $response['_source']['total_points'] : 0 + $point_value,
          'completed_level_interactive_content' => $node_ids,
        ],
        'doc_as_upsert' => TRUE,
      ];
      $output = ElasticUserModel::updateElasticUserData($elastic_params, $uid, $client);
    }
  }

}
