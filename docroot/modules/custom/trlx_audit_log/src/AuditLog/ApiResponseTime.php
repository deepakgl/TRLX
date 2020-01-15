<?php

namespace Drupal\trlx_audit_log\AuditLog;

use Drupal\trlx_audit_log\AuditLog\ApiResponseTimeInterface;
use Drupal\trlx_audit_log\AuditEventLogger;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Defines the storage handler class for nodes.
 *
 * This extends the base storage class, adding required special handling for
 * node entities.
 */
class ApiResponseTime implements ApiResponseTimeInterface {

  /**
   * {@inheritdoc}
   */
  public function logResponseTime($diff, $api_path) {
    // AuditEventLogger object to access functions.
    $logger_obj = new AuditEventLogger();
    $base = \Drupal::service('trlx_audit_log.api_response_time');
    $uid = $base->getUserID();
    $context = [];
    $context['request_uri'] = $api_path;
   // $context['user'] = $uid;
    $message = "[CMS_API_RESPONSE_TIME] Api path: " . $api_path . " Time: " . $diff;
    if ($uid) {
      $message .= ' UID:' . $uid;
    }
    // Log the audit log  for insert in ELK.
    \Drupal::service('logger.stdout')->log(RfcLogLevel::INFO, $message, $context);
  }


   /**
    * Extend this method using service decorators in any utility
    * module and provide userId.
    */
  public function getUserID() {
    return FALSE;
  }
}