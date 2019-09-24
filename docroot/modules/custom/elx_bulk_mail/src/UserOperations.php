<?php

namespace Drupal\elx_bulk_mail;

use Drupal\node\Entity\Node;

/**
 * Bulk mail to user.
 */
class UserOperations {

  /**
   * Batch operation.
   *
   * This is the function that is called on each operation in batch.
   */
  public static function bulkMail($mail, $operation_details, &$context) {
    $user = user_load_by_mail($mail);
    if ($user && $user->isActive()) {
      $first_name = $user->get('field_first_name')->getValue()[0]['value'];
      $login_url = \Drupal::config('elx_utility.settings')
        ->get('elx_front_end_url') . '/login';
      $message['subject'] = \Drupal::config('elx_utility.settings')
        ->get('elx_mail_subject');
      $message['body'] = \Drupal::config('elx_utility.settings')
        ->get('elx_mail_body');
      $message['body'] = str_replace("@name", $first_name, $message['body']);
      \Drupal::service('plugin.manager.mail')->mail('elx_bulk_mail', 'send_bulk_mail', $mail, 'en', $message, NULL, TRUE);
      $context['results'][] = $mail;
      $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $mail, '@details' => $operation_details]);
    }
    else {
      // Logs a notice.
      \Drupal::logger('elx_bulk_mail')->notice($mail);
    }
  }

  /**
   * Batch operation.
   *
   * This is the function that is called on each operation in batch.
   */
  public static function unblockUser($id, $operation_details, &$context) {
    $user_status = user_load_by_mail($id);
    if ($user_status) {
      $user_status->activate();
      $user_status->save();
      $context['results'][] = $id;
      $context['message'] = t('Running Batch "@id" @details',
      ['@id' => $id, '@details' => $operation_details]);
    }
  }

  /**
   * Batch operation.
   *
   * This is the function that is called on each operation in batch.
   */
  public static function bulkUpload($data, $operation_details, &$context) {
    $node = Node::load($data['nid']);
    if ($node && $node->get('langcode')->value == $data['lang']) {
      $market_id[] = $data['market_id'];
      // Get the market ids for current node.
      $markets = $node->get('field_markets')->getValue();
      foreach ($markets as $value) {
        $market_id[] = $value['target_id'];
      }
      // Set the market id for current node.
      $node->set('field_markets', $market_id);
      // Save node.
      $node->save();
      $context['results']['added'][] = t("Added @market to @node node NID: @nid.",
        [
          '@market' => $data['market_name'],
          '@node' => $node->getTitle(),
          '@nid' => $data['nid'],
        ]
      );
    }
    else {
      $context['results']['failed'][] = t("Failed to add @market for @node nid.",
      ['@market' => $data['market_name'], '@node' => $data['nid']]);
    }
    $context['message'] = t('Running Batch "@id" @details',
    ['@id' => $mail, '@details' => $operation_details]);
  }

  /**
   * Batch 'finished' callback.
   */
  public function finishedCallback($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['failed'])) {
        foreach ($results['failed'] as $message) {
          $messenger->addMessage($message, 'error');
        }
      }
      if (!empty($results['added'])) {
        foreach ($results['added'] as $message) {
          $messenger->addMessage($message, 'status');
        }
      }
      if (!isset($results['failed']) && !isset($results['added'])) {
        $messenger->addMessage(t('@count results processed.', ['@count' => count($results)]));
        $messenger->addMessage(t('The final result was "@final"', ['@final' => end($results)]));
      }
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

  /**
   * Batch operation.
   *
   * This is the function that is called on each operation in batch.
   */
  public static function blockUser($mail, $operation_details, &$context) {
    $user_status = user_load_by_mail($mail);
    if ($user_status) {
      $user_status->set("status", 0);
      $user_status->save();
      $context['results'][] = $mail;
      $context['message'] = t('Running Batch "@id" @details',
      ['@id' => $id, '@details' => $operation_details]);
    }
  }
}
