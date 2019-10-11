<?php

namespace Drupal\trlx_security\HttpResponseHeader;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Add custom headers.
 */
class HeaderResponseSubscriber implements EventSubscriberInterface {

  /**
   * Set current user language in header.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->set('Content-language', \Drupal::currentUser()->getPreferredLangcode());
    // Remove x-generator header for WAF complince.
    $response->headers->remove('x-generator');
  }

  /**
   * Get subscribed events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];

    return $events;
  }

}
