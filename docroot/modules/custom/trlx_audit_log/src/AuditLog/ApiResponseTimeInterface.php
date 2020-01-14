<?php

namespace Drupal\trlx_audit_log\AuditLog;

/**
 * Defines an interface for node entity storage classes.
 */
interface ApiResponseTimeInterface {

  /**
   * Gets a list of node revision IDs for a specific node.
   *
   * @param $diff
   *   The time Diff.
   * 
   * @param $api_path
   *   The api request path
   *
   */
  public function logResponseTime($diff, $api_path);


  /**
   * @return int
   *   User ID.
   */
  public function getUserID();

}
