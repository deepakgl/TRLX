<?php

namespace Drupal\elx_entityqueue_alter\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Auto complete route subscriber.
 */
class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  /**
   * Routes alter.
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\elx_entityqueue_alter\Controller\EntityAutocompleteController::handleAutocomplete');
    }
  }

}
