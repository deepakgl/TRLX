<?php

namespace Drupal\elx_utility\Utility;

/**
 * Purpose of this class is to build common object.
 */
class CommonUtility {

  /**
   * Validate if client_id is valid.
   *
   * @param string $client_id
   *   Client id.
   *
   * @return string
   *   The consumer UUID for the OAuth Client.
   */
  public function isValidClientId($client_id) {
    if (\Drupal::moduleHandler()->moduleExists('simple_oauth') && db_table_exists('consumer')) {
      $query = db_select('consumer', 'oc')
        ->fields('oc', ['uuid'])
        ->fields('oc', ['id'])
        ->condition('uuid', $client_id);
      $consumer = $query->execute()->fetch();

      return ($consumer->uuid) ? $consumer->uuid : NULL;
    }

    return FALSE;
  }

}
