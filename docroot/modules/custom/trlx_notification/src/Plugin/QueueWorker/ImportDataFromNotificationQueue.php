<?php

namespace Drupal\trlx_notification\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\trlx_notification\Utility\NotificationUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker to save data in custom notification queue.
 *
 * @QueueWorker(
 *   id = "import_notification_data_frequently",
 *   title = @Translation("Import Data From Notification Frequently Throughout The Day"),
 *   cron = {"time" = 200}
 * )
 */
class ImportDataFromNotificationQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // Get a queued item.
    if ($item) {
      // Process it.
      $this->saveIndexes($item->data);
    }
  }

  /**
   * Save data in elastic search index from queue.
   */
  public function saveIndexes(array $indexValues = []) {
    $client = NotificationUtility::getElasticClient();
    $config = \Drupal::config('trlx_notification.settings');
    $indexParams['index'] = $config->get('search_index');
    $exist = $client->indices()->exists($indexParams);
    // If index not exist, create new index.
    if (!$exist) {
      $params['body'] = [];
      $output = NotificationUtility::createElasticNotificationIndex($params, $client);
    }
    try {
      $params = [
        'index' => $config->get('search_index'),
        'type' => $config->get('search_index_type'),
        'id' => time() . mt_rand(1000, 9999),
        'body' => [
          'notificationType' => $indexValues['notificationType'],
          'userId' => $indexValues['userId'],
          'notificationHeading' => $indexValues['notificationHeading'],
          'notificationText' => $indexValues['notificationText'],
          'notificationDate' => $indexValues['notificationDate'],
          'notificationLink' => $indexValues['notificationLink'],
          'notificationLinkType' => $indexValues['notificationLinkType'],
          'notificationBrandKey' => $indexValues['notificationBrandKey'],
          'notificationBrandName' => $indexValues['notificationBrandName'],
          'notificationFlag' => '0',
          'notificationLanguage' => $indexValues['notificationLanguage'],
        ],
      ];
      $client->index($params);
    }
    catch (Exception $exp) {
      watchdog_exception('trlx_notification', $exp);
    }
  }

}
