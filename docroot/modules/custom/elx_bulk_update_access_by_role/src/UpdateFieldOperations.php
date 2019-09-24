<?php

namespace Drupal\elx_bulk_update_access_by_role;

use Drupal\node\Entity\Node;

/**
 * Bulk Update Field Access By Role.
 */
class UpdateFieldOperations {


  /**
   * Batch operation.
   *
   * This is the function that is called on each operation in batch.
   */
  public static function bulkUpdate($nid, $operation_details, &$context) {
    // Get all application roles.
    $roles_array = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $roles = array_keys($roles_array);
    $node = Node::load($nid);
    if ($node) {
      // Set the access by as all roles.
      $node->set('field_access_by_role', $roles);
      $node->set('field_translation', 'ready_for_translation');
      // Save node.
      $node->save();
      $context['results']['added'][] = t("Added field value to node NID: @nid.",
        [
          '@nid' => $nid,
        ]
      );
    }
    else {
      $context['results']['failed'][] = t("Failed to add value for @node nid.",
      ['@node' => $nid]);
    }
    $context['message'] = t('Running Batch for nid "@id" ',
    ['@id' => $nid]);
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

}
