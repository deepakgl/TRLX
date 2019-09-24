<?php

namespace Drupal\elx_points_migration;

use Drupal\node\Entity\Node;
use Elasticsearch\ClientBuilder;
use Drupal\user\Entity\User;
use Drupal\elx_utility\Utility\CommonUtility;

class MigrateFavoritesTrigger {

  /**
   * Batch operation.
   * This is the function that is called on each operation in batch.
   */
  public static function MigrateFavoritesByUser($id, $operation_details, &$context) {
    $common_utility = new CommonUtility();
    // wait 1/50th of a second as its a long process.
    usleep(20000);
    // // Connect with migration database.
    $ext_db = \Drupal\Core\Database\Database::getConnection('default','migrate');
    // Query to get all users favorites from legacy database.
    $result = [];
    try {
      $query = $ext_db->query("SELECT node.nid AS nid
      FROM
      {node} node
      INNER JOIN {flagging} flagging_node ON node.nid = flagging_node.entity_id AND (flagging_node.fid = 27 AND flagging_node.uid = $id->uid)
      WHERE (( (flagging_node.uid = $id->uid ) )AND(( (node.status = 1) AND (flagging_node.uid IS NOT NULL ) )))");

      $result = array_column($query->fetchAll(), 'nid');
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
    // Check for uid existence.
    if (!empty($result)) {
      $query_view_flag = $ext_db->select('node', 'n');
      $query_view_flag->addjoin('INNER', 'flagging', 'fl', 'fl.entity_id = n.nid');
      $query_view_flag->fields('n', ['nid', 'type'])
      ->condition('fl.fid', 27)
      ->condition('fl.uid', $id->uid);
      $results_view_flag = $query_view_flag->execute()->fetchAll();
      $legacy_node_type = $best_sellers_dest_nid = $tools_dest_nid = $dest_nids = $user_view_nid = [];
      foreach ($results_view_flag as $key_view_flag => $value_view_flag) {
        $legacy_node_type[$value_view_flag->type][] = $value_view_flag->nid;
      }
      if ($legacy_node_type['product_detail']) {
        $query = \Drupal::database()->select('migrate_map_custom_products', 'mp');
        $query->fields('mp', ['sourceid1', 'destid1']);
        $query->condition('sourceid1', $legacy_node_type['product_detail'], 'IN');
        $dest_nids = $query->execute()->fetchAll();
      }
      if ($legacy_node_type['best_sellers']) {
        $query = \Drupal::database()->select('migrate_map_custom_best_sellers', 'mp');
        $query->fields('mp', ['sourceid1', 'destid1']);
        $query->condition('sourceid1', $legacy_node_type['best_sellers'], 'IN');
        $best_sellers_dest_nid = $query->execute()->fetchAll();
        $dest_nids = array_merge($best_sellers_dest_nid, $dest_nids);
      }
      if ($legacy_node_type['tools']) {
        $query = \Drupal::database()->select('migrate_map_custom_tools', 'mp');
        $query->fields('mp', ['sourceid1', 'destid1']);
        $query->condition('sourceid1', $legacy_node_type['tools'], 'IN');
        $tools_dest_nid = $query->execute()->fetchAll();
        $dest_nids = array_merge($dest_nids, $tools_dest_nid);
      }
      foreach ($dest_nids as $dest_nid) {
        $user_view_nid[] = $dest_nid->destid1;
      }
      // Get mapped uid from lagacy uid.
      $des_uid_query = \Drupal::database()
      ->select('migrate_map_custom_user', 'u')
      ->fields('u', ['destid1'])
      ->condition('u.sourceid1', $id->uid)
      ->execute();
      $des_uid = $des_uid_query->fetchAll();
      // Check for uid existence.
      if (!empty($des_uid)) {
        $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
        // Create elastic connection.
        $client = $common_utility->setElasticConnectivity();
        // Check for index existence previously.
        $indexParams = [
          'index' => $env . '_user',
          'type' => 'user',
          'id' => 'user_' . $des_uid[0]->destid1,
        ];
        $exist = $client->exists($indexParams);
        // If index not exist, create new index.
        if (!$exist) {
          $params = [
            'index' => $env . '_user',
            'type' => 'user',
            'id' => 'user_' . $des_uid[0]->destid1,
            'body' => [
              'uid' => $des_uid[0]->destid1,
              'total_points' => 0,
              'badge' => [],
              'market' => [],
              'store' => [],
              'account' => [],
              'node_views_best_sellers' => [],
              'node_views_level_interactive_content' => [],
              'node_views_product_detail' => [],
              'node_views_stories' => [],
              'node_views_tools' => [],
              'node_views_t_c' => [],
              'node_views_tools-pdf' => [],
              'favorites' => $user_view_nid,
              'bookmarks' => [],
              'downloads' => [],
            ]
          ];


          $response = $client->index($params);
        }
        else {
          $elastic_response = $client->get($indexParams);
          $match = array_diff($user_view_nid, $elastic_response['_source']['favorites']);
          if (!empty(array_filter($match))) {
            $elastic_response['_source']['favorites'][] = implode(",", $match);
            $params = [
              'index' => $env . '_user',
              'type' => 'user',
              'id' => 'user_' . $des_uid[0]->destid1,
              'body' => [
                'doc' => [
                  'favorites' => $user_view_nid,
                  'bookmarks' => [],
                ],
                'doc_as_upsert' => true
              ]
            ];
            $response = $client->update($params);
          }
        }
        $context['results'][] = $des_uid[0]->destid1;
        $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $des_uid[0]->destid1, '@details' => $operation_details]
        );
      }
      else {
        $context['results'][] = $id->uid;
        $context['message'] = t('Running Batch "@id" "@type" @details',
          ['@id' => $id->uid . ' Not Exist', '@details' => $operation_details]
        );
      }
    }
    else {
      $context['results'][] = $id->uid;
      $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $id->uid . ' Not Exist', '@details' => $operation_details]
      );
    }
  }

  /**
   * Batch operation.
   * This is the function that is called on each operation in batch.
   */
  public static function MigrateFavoritesByNode($id, $operation_details, &$context) {
    $common_utility = new CommonUtility();
    // Connect with migration database.
    $ext_db = \Drupal\Core\Database\Database::getConnection('default','migrate');
    // wait 1/50th of a second as its a long process.
    usleep(20000);
    // Query to get all users favorites from legacy database.
    $flag_node_uid = [];
    try {
      $query = $ext_db->query("SELECT flagging_node.uid AS flagging_node_uid
        FROM
        {node} node
        INNER JOIN {flagging} flagging_node ON node.nid = flagging_node.entity_id AND flagging_node.fid = '27'
        WHERE (( (node.nid = $id->nid ) )AND(( (node.status = 1) )))");
        $flag_node_uid = array_column($query->fetchAll(), 'flagging_node_uid');
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
    // Check for uid existence.
    if (!empty($flag_node_uid)) {
      // Get mapped uid from lagacy uid.
      $des_uid = \Drupal::database()
        ->select('migrate_map_custom_user', 'u')
        ->fields('u', ['destid1'])
        ->condition('u.sourceid1', $flag_node_uid, 'IN')
        ->execute()
        ->fetchAll();
      $des_uid = array_column($des_uid, 'destid1');
      // Check for uid existence.
      if (!empty($des_uid)) {
        $query_view_flag = $ext_db->select('node', 'n');
        $query_view_flag->addjoin('INNER', 'flagging', 'fl', 'fl.entity_id = n.nid');
        $query_view_flag->fields('n', ['nid', 'type'])
          ->condition('fl.fid', 27)
          ->condition('fl.entity_id', $id->nid);
        $results = $query_view_flag->execute()->fetchAll();
        $legacy_node_type = $best_sellers_dest_nid = $tools_dest_nid = $dest_nids = $user_view_nid = [];
        foreach ($results as $key => $result) {
          $legacy_node_type[$result->type][] = $result->nid;
        }
        if ($legacy_node_type['product_detail']) {
          $query = \Drupal::database()->select('migrate_map_custom_products', 'mp');
          $query->fields('mp', ['sourceid1', 'destid1']);
          $query->condition('sourceid1', $legacy_node_type['product_detail'], 'IN');
          $dest_nids = $query->execute()->fetchAll();
        }
        if ($legacy_node_type['best_sellers']) {
          $query = \Drupal::database()->select('migrate_map_custom_best_sellers', 'mp');
          $query->fields('mp', ['sourceid1', 'destid1']);
          $query->condition('sourceid1', $legacy_node_type['best_sellers'], 'IN');
          $best_sellers_dest_nid = $query->execute()->fetchAll();
          $dest_nids = array_merge($best_sellers_dest_nid, $dest_nids);
        }
        if ($legacy_node_type['tools']) {
          $query = \Drupal::database()->select('migrate_map_custom_tools', 'mp');
          $query->fields('mp', ['sourceid1', 'destid1']);
          $query->condition('sourceid1', $legacy_node_type['tools'], 'IN');
          $tools_dest_nid = $query->execute()->fetchAll();
          $dest_nids = array_merge($dest_nids, $tools_dest_nid);

          $query = \Drupal::database()->select('migrate_map_custom_tools_pdf', 'mpt');
          $query->fields('mpt', ['sourceid1', 'destid1']);
          $query->condition('sourceid1', $legacy_node_type['tools'], 'IN');
          $tools_pdf_dest_nid = $query->execute()->fetchAll();
          $dest_nids = array_merge($dest_nids, $tools_pdf_dest_nid);
        }
        // Check for nid existence.
        if (!empty($dest_nids)) {
          $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
          // Create elastic connection.
          $client = $common_utility->setElasticConnectivity();
          // Check for index existence previously.
          $indexParams = [
            'index' => $env . '_node_data',
            'type' => 'node',
            'id' => $dest_nids[0]->destid1,
          ];
          $exist = $client->exists($indexParams);
          // If index not exist, create new index.
          if (!$exist) {
            $params = [
              'index' => $env . '_node_data',
              'type' => 'node',
              'id' => $dest_nids[0]->destid1,
              'body' => [
                'favorites_by_user' => $des_uid,
                'bookmarks_by_user' => [],
                'downloads_by_user' => []
              ],
            ];
            $response = $client->index($params);
          }
          else {
            $elastic_response = $client->get($indexParams);
            $match = array_diff($des_uid, $elastic_response['_source']['favorites_by_user']);
            if (!empty(array_filter($match))) {
              $elastic_response['_source']['favorites_by_user'][] = implode(",", $match);
              // If exist update previous index with updated point values.
              $params = [
                'index' => $env . '_node_data',
                'type' => 'node',
                'id' => $dest_nids[0]->destid1,
                'body' => [
                  'doc' => [
                    'favorites_by_user' => $des_uid,
                    'bookmarks_by_user' => [],
                    'downloads_by_user' => []
                  ],
                  'doc_as_upsert' => true
                ]
              ];
              $response = $client->update($params);
            }
          }
          $context['results'][] = $dest_nids[0]->destid1;
          $context['message'] = t('Running Batch "@id" @details',
            ['@id' => $dest_nids[0]->destid1, '@details' => $operation_details]
          );
        }
        else {
          $context['results'][] = $id->nid;
          $context['message'] = t('Running Batch "@id" @details',
            ['@id' => $id->nid . ' Not Exist', '@details' => $operation_details]
          );
        }
      }
      else {
        $context['results'][] = $id->nid;
        $context['message'] = t('Running Batch "@id" "@type" @details',
          ['@id' => $id->nid . ' Not Exist', '@details' => $operation_details]
        );
      }
    }
    else {
      $context['results'][] = $id->nid;
      $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $id->nid . ' Not Exist', '@details' => $operation_details]
      );
    }
  }


  /**
   * Batch 'finished' callback.
   */
  function MigrateFavoritesFinishedCallback($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('@count results processed.', ['@count' => count($results)]));
      $messenger->addMessage(t('The final result was "%final"', ['%final' => end($results)]));
    }
    else {
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
