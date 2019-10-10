<?php

namespace Drupal\trlx_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\trlx_notification\Utility\NotificationUtility;

/**
 * The notifications controller.
 */
class NotificationController extends ControllerBase {

  const NOTIFICATION_SETTINGS = 'trlx_notification.settings';
  const DATE_FORMAT = 'Y-m-d';

  /**
   * Notifications purge after 30 days of generation of the notification.
   */
  public function purgeNotifications() {
    $client = NotificationUtility::getElasticClient();
    $config = \Drupal::config(self::NOTIFICATION_SETTINGS);
    $indexParams['index'] = $config->get('search_index');
    $exist = $client->indices()->exists($indexParams);
    // If index not exist, create new index.
    if (!$exist) {
      $params['body'] = [];
      $output = NotificationUtility::createElasticNotificationIndex($params, $client);
    }
    $date = strtotime(date(self::DATE_FORMAT, strtotime('-' . $config->get('delete_notifications') . ' day')));
    $params = [
      'index' => $config->get('search_index'),
      'type' => $config->get('search_index_type'),
      'body' => [
        'query' => [
          'range' => [
            self::NOTIFICATION_DATE => ['lt' => $date],
          ],
        ],
      ],
    ];

    $result = $client->deleteByQuery($params);
    if ($result['deleted'] != 0) {
      \Drupal::logger('tracker_help(path, arg)trlx_notification')->info("Deleted @notificationsCount notifications after 30 days of generation.", ['@notificationsCount' => $result['deleted']]);
      return TRUE;
    }
    else {
      \Drupal::logger('trlx_notification')->info("No data to delete.");
      return FALSE;
    }
  }

}
