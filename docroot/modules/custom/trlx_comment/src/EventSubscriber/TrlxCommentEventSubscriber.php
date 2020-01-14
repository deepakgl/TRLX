<?php

namespace Drupal\trlx_comment\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\views_bulk_operations\ViewsBulkOperationsEvent;


/**
 * Event subscriber class.
 */
class TrlxCommentEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    // The next line prevents hard dependency on VBO module.
    if (class_exists(ViewsBulkOperationsEvent::class)) {
      $events['views_bulk_operations.view_data'][] = ['provideViewData', 0];
    }
    return $events;
  }

  /**
   * Provide entity type data and entity getter to VBO.
   *
   * @param \Drupal\views_bulk_operations\ViewsBulkOperationsEvent $event
   *   The event object.
   */
  public function provideViewData(ViewsBulkOperationsEvent $event) {
    $view_data = $event->getViewData();
    if ($event->getProvider() === 'view_custom_table') {
      $event->setEntityTypeIds(['trlx_comment']);
      $event->setEntityGetter([
        'file' => drupal_get_path('module', 'trlx_comment') . '/src/trlxComment.php',
        'callable' => '\Drupal\trlx_comment\trlxComment::getEntityFromRow',
      ]);
    }
  }

}
