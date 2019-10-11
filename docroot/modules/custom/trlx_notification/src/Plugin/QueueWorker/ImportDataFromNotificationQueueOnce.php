<?php

namespace Drupal\trlx_notification\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Queue worker to save data in custom notification queue once in a day.
 *
 * @QueueWorker(
 *   id = "import_notification_data_once",
 *   title = @Translation("Import Data From Notification Once in a Day"),
 *   cron = {"time" = 200}
 * )
 */
class ImportDataFromNotificationQueueOnce extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // Get a queued item.
    if ($item) {
      // Process it.
      ImportDataFromNotificationQueue::saveIndexes($item->data);
    }
  }

}
