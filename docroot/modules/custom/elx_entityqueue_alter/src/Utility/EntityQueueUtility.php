<?php

namespace Drupal\elx_entityqueue_alter\Utility;

/**
 * Purpose of this class is to build queues object.
 */
class EntityQueueUtility {

  /**
   * Fetch overridden status.
   *
   * @param string $queue_name
   *   Queue name.
   *
   * @return bool
   *   True or False.
   */
  public function fetchQueueOverrideFlagStatus($queue_name) {
    $query = \Drupal::database()
      ->select('entity_subqueue__field_queue_override_flag', 'of')
      ->fields('of', ['field_queue_override_flag_value'])
      ->condition('of.entity_id', $queue_name, '=')
      ->execute()->fetchAssoc()['field_queue_override_flag_value'];
    if ($query) {
      return TRUE;
    }

    return FALSE;
  }

}
