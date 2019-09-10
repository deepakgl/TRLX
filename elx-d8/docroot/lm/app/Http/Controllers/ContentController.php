<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Mysql\ContentModel;
use App\Model\Elastic\ElasticUserModel;
use App\Support\Helper;
use App\Model\Elastic\FlagModel;

/**
 * Purpose of building this class is to alter the content, users and terms data.
 */
class ContentController extends Controller {

  /**
   * Create a new controller instance.
   */
  public function __construct() {
    
  }

  /**
   * Set levels data based on nid.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   True.
   */
  public function setTermsNodeData(Request $request) {
    $nid = $request->input('nid'); // Node id.
    $tid = $request->input('tid'); // Term id.
    $client = Helper::checkElasticClient();
    $node_elastic_exists = FlagModel::checkElasticNodeIndex($nid, $client);
    if (!$node_elastic_exists && $client) {
      $params['body'] = [
        'favorites_by_user' => [],
        'downloads_by_user' => [],
        'bookmarks_by_user' => [],
      ];
      $create_node_index = FlagModel::createElasticNodeIndex($params, $nid, $client);
    }
    // Update LRS table on the basis of node id and term id.
    $set_user_terms_node = ContentModel::setTermsNodeData($nid, $tid);

    return Helper::jsonSuccess($set_user_terms_node);
  }

  /**
   * Get Interactive level terms status.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Interactive level status.
   */
  public function intereactiveLevelTermStatus(Request $request) {
    // Node id.
    $nid = $request->input('nid');
    // Get term status of particular node.
    $levels_status = ContentModel::getIntereactiveLevelTermStatus($nid);

    return jsonSuccess($levels_status);
  }

  /**
   * Purge user elastic data.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   True or false.
   */
  public function purgeElasticUser(Request $request) {
    // User id.
    $uid = $request->input('uid');
    if (!$uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    try {
      // Check whether elastic connectivity is there.
      $client = Helper::checkElasticClient();
    }
    catch (\Exception $e) {
      return Helper::jsonError($e->getMessage(), 400);
    }
    // Check for index existence previously.
    $exist = ElasticUserModel::checkElasticUserIndex($uid, $client);
    if (!$exist) {
      return Helper::jsonError('No data to delete.', 422);
    }
    // Purge respective user data from elastic.
    $response = ElasticUserModel::deleteElasticUserData($uid, $client);
    return Helper::jsonSuccess($response);
  }

  /**
   * Purge node elastic data.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   True or false.
   */
  public function purgeElasticNodeData(Request $request) {
    // Node id.
    $nid = $request->input('nid');
    if (!$nid) {
      return Helper::jsonError('Please provide node id.', 422);
    }
    try {
      // Check whether elastic connectivity is there.
      $client = Helper::checkElasticClient();
    }
    catch (\Exception $e) {
      return Helper::jsonError($e->getMessage(), 400);
    }
    // Check for index existence previously.
    $exist = FlagModel::checkElasticNodeIndex($nid, $client);
    if (!$exist) {
      return Helper::jsonError('No data to delete.', 422);
    }
    // Purge respective node data from elastic.
    $response = FlagModel::deleteElasticNodeData($nid, $client);
    return Helper::jsonSuccess($response);
  }

  /**
   * Delete levels data based on nid & tid.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   True or false.
   */
  public function deleteTermsNodeData(Request $request) {
    // Node id.
    $nid = $request->input('nid');
    // Term id.
    $tid = $request->input('tid');
    // Delete levels data based on nid & tid.
    $delete_terms_node = ContentModel::deleteTermsNodeData($nid, $tid);
    return Helper::jsonSuccess($delete_terms_node);
  }

}
