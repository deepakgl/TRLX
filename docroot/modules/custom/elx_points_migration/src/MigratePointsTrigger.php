<?php

namespace Drupal\elx_points_migration;

use Drupal\node\Entity\Node;
use Elasticsearch\ClientBuilder;
use Drupal\user\Entity\User;

class MigratePointsTrigger {

  /**
   * Batch operation.
   * This is the function that is called on each operation in batch.
   */
  public static function MigratePointsStart($id, $operation_details, &$context) {
    // wait 1/50th of a second as its a long process.
    usleep(20000);
    // Connect with migration database.
    $ext_db = \Drupal\Core\Database\Database::getConnection('default','migrate');

    $result = $results_view_flag = [];

    // Query to get all users badges from legacy database.
    try {
      $query = $ext_db->query("SELECT (flagging_users_34.uid IS NOT NULL) AS first_certification_star, (flagging_users_35.uid IS NOT NULL) AS second_certification_star, (flagging_users_36.uid IS NOT NULL) AS third_certification_star, (flagging_users_37.uid IS NOT NULL) AS fourth_certification_star, (flagging_users_38.uid IS NOT NULL) AS final_beautiful_start_star, (flagging_users_6.uid IS NOT NULL) AS first_1_000_points_badge, (flagging_users_5.uid IS NOT NULL) AS first_5_000_points_badge, (flagging_users_9.uid IS NOT NULL) AS first_10000_points_badge, (flagging_users_10.uid IS NOT NULL) AS welcome_badge, (flagging_users_2.uid IS NOT NULL) AS on_your_way_badge, (flagging_users.uid IS NOT NULL) AS high_five_badge, (flagging_users_1.uid IS NOT NULL) AS perfect_10_badge, (flagging_users_11.uid IS NOT NULL) AS little_brown_bottle_badge, (flagging_users_8.uid IS NOT NULL) AS product_pro_badge, (flagging_users_13.uid IS NOT NULL) AS rare_beauty_badge, (flagging_users_4.uid IS NOT NULL) AS service_star_badge, (flagging_users_16.uid IS NOT NULL) AS we_heard_you_badge, (flagging_users_12.uid IS NOT NULL) AS beauty_queen_badge, (flagging_users_15.uid IS NOT NULL) AS sleeping_beauty_badge, (flagging_users_18.uid IS NOT NULL) AS well_read_25_badge, (flagging_users_17.uid IS NOT NULL) AS well_read_50_badge, (flagging_users_19.uid IS NOT NULL) AS well_read_75_badge, (flagging_users_22.uid IS NOT NULL) AS well_read_100_badge, (flagging_users_3.uid IS NOT NULL) AS one_hundred_percent_club_badge, (flagging_users_7.uid IS NOT NULL) AS we_heart_you_badge, (flagging_users_20.uid IS NOT NULL) AS three_minute_beauty_badge, (flagging_users_23.uid IS NOT NULL) AS early_bird_badge, (flagging_users_21.uid IS NOT NULL) AS fast_learner_badge, (flagging_users_24.uid IS NOT NULL) AS perfect_present, (flagging_users_25.uid IS NOT NULL) AS gift_genius, (flagging_users_39.uid IS NOT NULL) AS events_expert, (flagging_users_40.uid IS NOT NULL) AS fall_preview_2017_, (flagging_users_41.uid IS NOT NULL) AS the_re_nutriv_luxury_experience, (flagging_users_31.uid IS NOT NULL) AS inspiration_experience, (flagging_users_32.uid IS NOT NULL) AS inspiration_leadership, (flagging_users_33.uid IS NOT NULL) AS inspiration_style, (flagging_users_42.uid IS NOT NULL) AS aerin_rose, (flagging_users_43.uid IS NOT NULL) AS the_pink_ribbon_badge, (flagging_users_44.uid IS NOT NULL) AS holiday_success_beauty_advisors, (flagging_users_45.uid IS NOT NULL) AS holiday_success_counter_managers, (flagging_users_46.uid IS NOT NULL) AS spring_preview, (flagging_users_47.uid IS NOT NULL) AS perfectionist_pro_badge, (flagging_users_48.uid IS NOT NULL) AS active_learner_badge, (flagging_users_49.uid IS NOT NULL) AS the_night_ritual_refresh, (flagging_users_50.uid IS NOT NULL) AS fall_preview_2018
      FROM
      {users} users
      LEFT JOIN {flagging} flagging_users ON users.uid = flagging_users.entity_id AND flagging_users.fid = '8'
      LEFT JOIN {flagging} flagging_users_1 ON users.uid = flagging_users_1.entity_id AND flagging_users_1.fid = '13'
      LEFT JOIN {flagging} flagging_users_2 ON users.uid = flagging_users_2.entity_id AND flagging_users_2.fid = '11'
      LEFT JOIN {flagging} flagging_users_3 ON users.uid = flagging_users_3.entity_id AND flagging_users_3.fid = '12'
      LEFT JOIN {flagging} flagging_users_4 ON users.uid = flagging_users_4.entity_id AND flagging_users_4.fid = '17'
      LEFT JOIN {flagging} flagging_users_5 ON users.uid = flagging_users_5.entity_id AND flagging_users_5.fid = '6'
      LEFT JOIN {flagging} flagging_users_6 ON users.uid = flagging_users_6.entity_id AND flagging_users_6.fid = '5'
      LEFT JOIN {flagging} flagging_users_7 ON users.uid = flagging_users_7.entity_id AND flagging_users_7.fid = '21'
      LEFT JOIN {flagging} flagging_users_8 ON users.uid = flagging_users_8.entity_id AND flagging_users_8.fid = '15'
      LEFT JOIN {flagging} flagging_users_9 ON users.uid = flagging_users_9.entity_id AND flagging_users_9.fid = '4'
      LEFT JOIN {flagging} flagging_users_10 ON users.uid = flagging_users_10.entity_id AND flagging_users_10.fid = '22'
      LEFT JOIN {flagging} flagging_users_11 ON users.uid = flagging_users_11.entity_id AND flagging_users_11.fid = '9'
      LEFT JOIN {flagging} flagging_users_12 ON users.uid = flagging_users_12.entity_id AND flagging_users_12.fid = '1'
      LEFT JOIN {flagging} flagging_users_13 ON users.uid = flagging_users_13.entity_id AND flagging_users_13.fid = '16'
      LEFT JOIN {flagging} flagging_users_14 ON users.uid = flagging_users_14.entity_id AND flagging_users_14.fid = '10'
      LEFT JOIN {flagging} flagging_users_15 ON users.uid = flagging_users_15.entity_id AND flagging_users_15.fid = '18'
      LEFT JOIN {flagging} flagging_users_16 ON users.uid = flagging_users_16.entity_id AND flagging_users_16.fid = '20'
      LEFT JOIN {flagging} flagging_users_17 ON users.uid = flagging_users_17.entity_id AND flagging_users_17.fid = '25'
      LEFT JOIN {flagging} flagging_users_18 ON users.uid = flagging_users_18.entity_id AND flagging_users_18.fid = '24'
      LEFT JOIN {flagging} flagging_users_19 ON users.uid = flagging_users_19.entity_id AND flagging_users_19.fid = '26'
      LEFT JOIN {flagging} flagging_users_20 ON users.uid = flagging_users_20.entity_id AND flagging_users_20.fid = '19'
      LEFT JOIN {flagging} flagging_users_21 ON users.uid = flagging_users_21.entity_id AND flagging_users_21.fid = '3'
      LEFT JOIN {flagging} flagging_users_22 ON users.uid = flagging_users_22.entity_id AND flagging_users_22.fid = '23'
      LEFT JOIN {flagging} flagging_users_23 ON users.uid = flagging_users_23.entity_id AND flagging_users_23.fid = '2'
      LEFT JOIN {flagging} flagging_users_24 ON users.uid = flagging_users_24.entity_id AND flagging_users_24.fid = '14'
      LEFT JOIN {flagging} flagging_users_25 ON users.uid = flagging_users_25.entity_id AND flagging_users_25.fid = '7'
      LEFT JOIN {flagging} flagging_users_26 ON users.uid = flagging_users_26.entity_id AND flagging_users_26.fid = '28'
      LEFT JOIN {flagging} flagging_users_27 ON users.uid = flagging_users_27.entity_id AND flagging_users_27.fid = '40'
      LEFT JOIN {flagging} flagging_users_28 ON users.uid = flagging_users_28.entity_id AND flagging_users_28.fid = '41'
      LEFT JOIN {flagging} flagging_users_29 ON users.uid = flagging_users_29.entity_id AND flagging_users_29.fid = '43'
      LEFT JOIN {flagging} flagging_users_30 ON users.uid = flagging_users_30.entity_id AND flagging_users_30.fid = '42'
      LEFT JOIN {flagging} flagging_users_31 ON users.uid = flagging_users_31.entity_id AND flagging_users_31.fid = '44'
      LEFT JOIN {flagging} flagging_users_32 ON users.uid = flagging_users_32.entity_id AND flagging_users_32.fid = '45'
      LEFT JOIN {flagging} flagging_users_33 ON users.uid = flagging_users_33.entity_id AND flagging_users_33.fid = '46'
      LEFT JOIN {flagging} flagging_users_34 ON users.uid = flagging_users_34.entity_id AND flagging_users_34.fid = '47'
      LEFT JOIN {flagging} flagging_users_35 ON users.uid = flagging_users_35.entity_id AND flagging_users_35.fid = '49'
      LEFT JOIN {flagging} flagging_users_36 ON users.uid = flagging_users_36.entity_id AND flagging_users_36.fid = '50'
      LEFT JOIN {flagging} flagging_users_37 ON users.uid = flagging_users_37.entity_id AND flagging_users_37.fid = '48'
      LEFT JOIN {flagging} flagging_users_38 ON users.uid = flagging_users_38.entity_id AND flagging_users_38.fid = '39'
      LEFT JOIN {flagging} flagging_users_39 ON users.uid = flagging_users_39.entity_id AND flagging_users_39.fid = '51'
      LEFT JOIN {flagging} flagging_users_40 ON users.uid = flagging_users_40.entity_id AND flagging_users_40.fid = '52'
      LEFT JOIN {flagging} flagging_users_41 ON users.uid = flagging_users_41.entity_id AND flagging_users_41.fid = '53'
      LEFT JOIN {flagging} flagging_users_42 ON users.uid = flagging_users_42.entity_id AND flagging_users_42.fid = '54'
      LEFT JOIN {flagging} flagging_users_43 ON users.uid = flagging_users_43.entity_id AND flagging_users_43.fid = '55'
      LEFT JOIN {flagging} flagging_users_44 ON users.uid = flagging_users_44.entity_id AND (flagging_users_44.fid = '57' AND flagging_users_44.uid = ".$id->uid.")
      LEFT JOIN {flagging} flagging_users_45 ON users.uid = flagging_users_45.entity_id AND (flagging_users_45.fid = '56' AND flagging_users_45.uid = ".$id->uid.")
      LEFT JOIN {flagging} flagging_users_46 ON users.uid = flagging_users_46.entity_id AND flagging_users_46.fid = '58'
      LEFT JOIN {flagging} flagging_users_47 ON users.uid = flagging_users_47.entity_id AND flagging_users_47.fid = '59'
      LEFT JOIN {flagging} flagging_users_48 ON users.uid = flagging_users_48.entity_id AND flagging_users_48.fid = '61'
      LEFT JOIN {flagging} flagging_users_49 ON users.uid = flagging_users_49.entity_id AND (flagging_users_49.fid = '63' AND flagging_users_49.uid = ".$id->uid.")
      LEFT JOIN {flagging} flagging_users_50 ON users.uid = flagging_users_50.entity_id AND (flagging_users_50.fid = '64' AND flagging_users_50.uid = ".$id->uid.")
      WHERE ((( (users.uid = ".$id->uid.") )))");
      $result = $query->fetchAll();
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }

    try {
      $query_view_flag = $ext_db->select('node', 'n');
      $query_view_flag->addjoin('INNER', 'flagging', 'fl', 'fl.entity_id = n.nid');
      $query_view_flag->fields('n', ['nid', 'type'])
      ->condition('fl.fid', 30)
      ->condition('fl.uid', $id->uid);
      $results_view_flag = $query_view_flag->execute()->fetchAll();
    }
    catch (\Exception $e) {
    }
    $dest_nids_product_detail =
    $dest_nids_best_sellers =
    $legacy_node_type =
    $best_sellers_dest_nid =
    $tools_dest_nid =
    $dest_nids =
    $dest_nids_product_detail_data =
    $dest_nids_best_sellers_data =
    $tools_dest_nid_data =
    $pdf_dest_nid_data =
    $user_view_nid = [];
    foreach ($results_view_flag as $key_view_flag => $value_view_flag) {
      $legacy_node_type[$value_view_flag->type][] = $value_view_flag->nid;
    }
    if ($legacy_node_type['product_detail']) {
      $query = \Drupal::database()->select('migrate_map_custom_products', 'mp');
      $query->fields('mp', ['sourceid1', 'destid1']);
      $query->condition('sourceid1', $legacy_node_type['product_detail'], 'IN');
      $dest_nids_product_detail = $query->execute()->fetchAll();
      foreach ($dest_nids_product_detail as $dest_nid) {
        $dest_nids_product_detail_data[] = $dest_nid->destid1;
      }
    }
    if ($legacy_node_type['best_sellers']) {
      $query = \Drupal::database()->select('migrate_map_custom_best_sellers', 'mp');
      $query->fields('mp', ['sourceid1', 'destid1']);
      $query->condition('sourceid1', $legacy_node_type['best_sellers'], 'IN');
      $dest_nids_best_sellers = $query->execute()->fetchAll();
      foreach ($dest_nids_best_sellers as $dest_nid) {
        $dest_nids_best_sellers_data[] = $dest_nid->destid1;
      }
    }
    if ($legacy_node_type['tools']) {
      $query = \Drupal::database()->select('migrate_map_custom_tools', 'mp');
      $query->fields('mp', ['sourceid1', 'destid1']);
      $query->condition('sourceid1', $legacy_node_type['tools'], 'IN');
      $tools_dest_nid = $query->execute()->fetchAll();
      foreach ($tools_dest_nid as $dest_nid) {
        $tools_dest_nid_data[] = $dest_nid->destid1;
      }

      $query_pdf = \Drupal::database()->select('migrate_map_custom_tools_pdf', 'mp');
      $query_pdf->fields('mp', ['sourceid1', 'destid1']);
      $query_pdf->condition('sourceid1', $legacy_node_type['tools'], 'IN');
      $pdf_dest_nid = $query_pdf->execute()->fetchAll();
      foreach ($pdf_dest_nid as $dest_nid) {
        $pdf_dest_nid_data[] = $dest_nid->destid1;
      }
    }

    // Get mapped uid from legacy uid.
    $des_uid = \Drupal::database()
      ->select('migrate_map_custom_user', 'u')
      ->fields('u', ['destid1'])
      ->condition('u.sourceid1', $id->uid)
      ->execute()
      ->fetchAll();


    // Check for uid existence.
    if (!empty($des_uid)) {
      $user = User::load($des_uid[0]->destid1);
      // Get user markets, store, account.
      $market = !empty($user->get('field_default_market')) ? $user->get('field_default_market')->getValue() : [];
      $store = !empty($user->get('field_door')) ? $user->get('field_door')->getValue() : [];
      $account = !empty($user->get('field_account_name')) ? $user->get('field_account_name')->getValue() : [];
      $markets = array_column($market, 'target_id');
      $stores = array_column($store, 'value');
      $accounts = array_column($account, 'value');
      //$accounts = $stores = $markets = []
      $points = 0;
      if ($id->userpoints_total_points != '') {
        $points = $id->userpoints_total_points;
      }

        // Create elastic connection.
        try {
          $hosts = [
            [
              'host' => \Drupal::config('elx_utility.settings')->get('elastic_host'),
              'port' => \Drupal::config('elx_utility.settings')->get('elastic_port'),
              'scheme' => \Drupal::config('elx_utility.settings')->get('elastic_scheme'),
              'user' => \Drupal::config('elx_utility.settings')
                ->get('elastic_username'),
              'pass' => \Drupal::config('elx_utility.settings')
                ->get('elastic_password'),
            ],
          ];
          $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
          $client = ClientBuilder::create()->setHosts($hosts)->build();
        }
        catch (\Exception $e) {
          return $e->getMessage();
        }

      // Check for index existence previously.
      $indexParams = [
        'index' => $env . '_user',
        'type' => 'user',
        'id' => 'user_' . $des_uid[0]->destid1,
      ];
      $exist = $client->exists($indexParams);
      $badge_result = get_object_vars($result[0]);
      foreach ($badge_result as $key => $value) {
        if (($key = array_search(0, $badge_result)) !== false) {
          unset($badge_result[$key]);
        }
      }
      $query = \Drupal::database()->select('user__field_has_3_0_permission', 'hp');
      $query->fields('hp', ['field_has_3_0_permission_value']);
      $query->condition('entity_id', $des_uid[0]->destid1, '=');
      $query->condition('field_has_3_0_permission_value', 1, '=');
      $results = $query->execute()->fetchAll();
      if (!empty($results[0])) {
        return;
      }
      // If index not exist, create new index.
      if (!$exist) {
        $params = [
          'index' => $env . '_user',
          'type' => 'user',
          'id' => 'user_' . $des_uid[0]->destid1,
        ];
        $exist = $client->exists($indexParams);
        $badge_result = get_object_vars($result[0]);
        foreach ($badge_result as $key => $value) {
          if (($key = array_search(0, $badge_result)) !== false) {
            unset($badge_result[$key]);
          }
        }
        // If index not exist, create new index.
        if (!$exist) {
          $params = [
            'index' => $env . '_user',
            'type' => 'user',
            'id' => 'user_' . $des_uid[0]->destid1,
            'body' => [
              'uid' => $des_uid[0]->destid1,
              'total_points' => $points,
              'badge' => (!empty($badge_result)) ? [ $badge_result ] : [],
              'market' => $markets,
              'store' => $stores,
              'account' => $accounts,
              'node_views_best_sellers' => $dest_nids_best_sellers_data,
              'node_views_level_interactive_content' => [],
              'node_views_product_detail' => $dest_nids_product_detail_data,
              'node_views_stories' => [],
              'node_views_tools' => $tools_dest_nid_data,
              'node_views_t_c' => [],
              'node_views_tools-pdf' => $pdf_dest_nid_data,
              'favorites' => [],
              'bookmarks' => [],
              'downloads' => [],
            ]
          ];
          $response = $client->index($params);
        }
        else {
          // If exist update previous index with updated point values.
          $params = [
            'index' => $env . '_user',
            'type' => 'user',
            'id' => 'user_' . $des_uid[0]->destid1,
            'body' => [
              'doc' => [
                'uid' => $des_uid[0]->destid1,
                'total_points' => $points,
                'badge' =>  (!empty($badge_result)) ? [ $badge_result ] : [],
                'market' => $markets,
                'store' => $stores,
                'account' => $accounts,
                'node_views_product_detail' => $dest_nids_product_detail_data,
                'node_views_tools' => $tools_dest_nid_data,
                'node_views_tools-pdf' => $pdf_dest_nid_data
              ],
              'doc_as_upsert' => true
            ]
          ];
          $response = $client->update($params);
        }
        $context['results'][] = $des_uid[0]->destid1;
        $context['message'] = t('Running Batch "@id" @details',
          ['@id' => $des_uid[0]->destid1, '@details' => $operation_details]
        );
      }
      else {
        $context['results'][] = $id->uid;
        $context['message'] = t('Running Batch "@id" @details',
          ['@id' => $id->uid . ' Not Exist', '@details' => $operation_details]
        );
      }
  }


  /**
   * Batch 'finished' callback.
   */
  function MigratePointsFinishedCallback($success, $results, $operations) {
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
