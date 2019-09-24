<?php

namespace Drupal\elx_elastic_user;

use Drupal\user\Entity\User;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Class for Elastic User.
 */
class UpdateElasticUser {

  /**
   * Batch operation. This function called for updating users in elastic.
   */
  public static function updateElasticUserStart($id, $operation_details, &$context) {
    // Wait 1/50th of a second as its a long process.
    usleep(20000);
    $user = User::load($id->uid);
    // Get user markets, store, account.
    $market = $user->get('field_default_market')->getValue();
    $store = $user->get('field_door')->getValue();
    $account = $user->get('field_account_name')->getValue();
    $markets = array_column($market, 'target_id');
    $stores = array_column($store, 'value');
    $accounts = array_column($account, 'value');
    $email = $user->get('mail')->value;
    $access = (int) $user->get('field_has_3_0_permission')->value;
    // Check user is blocked.
    $status = 1;
    if ($user->isBlocked() === TRUE) {
      $status = 0;
    }
    // Check user is having srijan or test in its mail id.
    $ignore = 0;
    if (preg_match('/(srijan|test|mailinator|mindstix)/', $email)) {
      $ignore = 1;
    }
    // Create elastic connection.
    $elastic_obj = new CommonUtility();
    $client = $elastic_obj->setElasticConnectivity();
    $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
    $index_params = [
      'index' => $env . '_user',
      'type' => 'user',
      'id' => 'user_' . $id->uid,
    ];
    // Check for index existence previously.
    $exist = $client->exists($index_params);
    // If index not exist, create new index.
    if (!$exist) {
      $params = [
        'index' => $env . '_user',
        'type' => 'user',
        'id' => 'user_' . $id->uid,
        'body' => [
          'uid' => $id->uid,
          'total_points' => 0,
          'badge' => [],
          'market' => $markets,
          'store' => $stores,
          'account' => $accounts,
          'node_views_best_sellers' => [],
          'node_views_level_interactive_content' => [],
          'node_views_product_detail' => [],
          'node_views_stories' => [],
          'node_views_tools' => [],
          'node_views_t_c' => [],
          'node_views_tools-pdf' => [],
          'like' => [],
          'bookmark' => [],
          'status' => $status,
          'email' => $email,
          'access_permission' => $access,
          'ignore' => $ignore,
        ],
      ];
      $response = $client->index($params);
    }
    else {
      // If exist update previous index with updated point values.
      $params = [
        'index' => $env . '_user',
        'type' => 'user',
        'id' => 'user_' . $id->uid,
        'body' => [
          'doc' => [
            'uid' => $id->uid,
            'market' => $markets,
            'store' => $stores,
            'account' => $accounts,
            'status' => $status,
            'email' => $email,
            'access_permission' => $access,
            'ignore' => $ignore,
          ],
          'doc_as_upsert' => TRUE,
        ],
      ];
      $response = $client->update($params);
    }
    $context['results'][] = $id->uid;
    $context['message'] = t('Running Batch "@id" @details',
          ['@id' => $id->uid . $msg, '@details' => $operation_details]
        );

  }

  /**
   * Batch 'finished' callback.
   */
  public function updationElasticCallback($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('@count results processed.', [
        '@count' =>
        count($results),
      ]));
      $messenger->addMessage(t('The final result was "%final"', [
        '%final' =>
        end($results),
      ]));
    }
    else {
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments :
           @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
