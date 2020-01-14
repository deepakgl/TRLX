<?php

namespace Drupal\trlx_utility\AuditLog;

use Drupal\trlx_audit_log\AuditLog\ApiResponseTime;
use Drupal\trlx_audit_log\AuditEventLogger;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Defines the storage handler class for nodes.
 *
 * This extends the base storage class, adding required special handling for
 * node entities.
 */
class ApiResponseTimeDecorator extends ApiResponseTime {

  public function getUserID() {
  	global $_userdata;
    if (isset($_userdata->uid)) {
    	return $_userdata->uid;
    }
  }
}