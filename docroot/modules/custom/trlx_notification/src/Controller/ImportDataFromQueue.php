<?php

namespace Drupal\trlx_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Elasticsearch\ClientBuilder;

/**
 * The notification queue controller.
 */
class ImportDataFromQueue extends ControllerBase {

  /**
   * To save data in Notification Queue frequently in a day.
   */
  public function getDataToSaveInNotificationQueueFrequently(array $indexValues = []) {

    // Get the queue implementation for import_data_from_notification queue.
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('import_notification_data_frequently');

    // Create new queue item.
    $item = new \stdClass();
    $item->data = $indexValues;
    $queue->createItem($item);
    return [
      '#type' => 'markup',
      '#markup' => t('Queue item is created.'),
    ];
  }

  /**
   * To save data in Notification Queue frequently in a day.
   */
  public function getDataToSaveInNotificationQueueOnce(array $indexValues = []) {

    // Get the queue implementation for import_data_from_notification queue.
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('import_notification_data_once');
    // Create new queue item.
    $item = new \stdClass();
    $item->data = $indexValues;
    $queue->createItem($item);

    return [
      '#type' => 'markup',
      '#markup' => t('Queue item is created.'),
    ];
  }

  /**
   * Get elastic client.
   *
   * @return object
   *   Elastic client.
   */
  public function getElasticClient() {
    $config = \Drupal::config('trlx_notification.settings');
    $hosts = [
      [
        'host' => $config->get('host'),
        'port' => $config->get('port'),
        'scheme' => $config->get('scheme'),
      ],
    ];

    return (ClientBuilder::create()->setHosts($hosts)->build());
  }

}
