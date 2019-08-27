<?php

namespace Drupal\elx_custom_service\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * ElxCustomServiceEventsSubscriber Class for User Import Feeds.
 */
class ElxCustomServiceEventsSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Modify events after existing parser.
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParse(ParseEvent $event) {
    if ($event->getFeed()->bundle() == 'user_bulk_import') {
      $result = $event->getParserResult();
      for ($i = 0; $i < $result->count(); $i++) {
        $item = $result->offsetGet($i);
        if (empty($item->get('active_learner_groups'))) {
          $msg = $item->get('email') . " has no role";
          \Drupal::messenger()->addMessage($msg, 'error');
          $result->offsetUnset($i);
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    return $events;
  }

}
