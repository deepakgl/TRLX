<?php

namespace Drupal\trlx_audit_log\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\views_bulk_operations\ViewsBulkOperationsEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Event subscriber class.
 */
class TrlxAuditLogEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onApiRequest'];
    $events[KernelEvents::RESPONSE][] = ['onApiResponse'];
    return $events;
  }

  /**
   * Provide entity type data and entity getter to VBO.
   */
  public function onApiRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $route_path = $request->get('_route_object')->getPath();
    if (strpos($route_path, '/api') !== false) {
      $request->headers->set('api-request-time', microtime(TRUE));
    }
  }

  /**
   * Provide entity type data and entity getter to VBO.
   */
  public function onApiResponse(FilterResponseEvent $event) {
   $request = $event->getRequest();
   $route_path = $request->get('_route_object')->getPath();
   if (strpos($route_path, '/api') !== false) {
      $requestTime = $request->headers->get('api-request-time');
      $responseTime = microtime(TRUE);
      $diff = $responseTime - $requestTime;
      trlx_audit_log_response_time($diff, $route_path);
    }
  }
}