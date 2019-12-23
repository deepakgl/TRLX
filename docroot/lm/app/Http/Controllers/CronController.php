<?php

namespace App\Http\Controllers;

use App\Model\Mysql\ContentModel;

/**
 * Class for cron controller.
 */
class CronController extends Controller {

  /**
   * Lrs reset queue.
   */
  public function lrsResetQueue() {
    $lrs_records = ContentModel::getLrsQueueRecord();
    if (empty($lrs_records)) {
      return;
    }
    foreach ($lrs_records as $key => $data) {
      $this->processLrsData($data);
    }
  }

  /**
   * Process request.
   *
   * @param mixed $data
   *   cURL object data.
   */
  public function processLrsData($data) {
    $request = unserialize($data['request']);
    ContentModel::updateLrsQueueRecord($data['id']);
    $lrs_agent = new LrsAgentController();
    $lrs_agent->putLrsStatement($request, $data['arg1'], $data['arg2']);
  }

}
