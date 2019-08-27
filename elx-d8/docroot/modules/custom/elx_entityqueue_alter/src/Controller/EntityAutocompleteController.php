<?php

namespace Drupal\elx_entityqueue_alter\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\elx_entityqueue_alter\EntityAutocompleteMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity auto complete controller.
 */
class EntityAutocompleteController extends \Drupal\system\Controller\EntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   *
   * @var matcher
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('elx_entityqueue_alter.autocomplete_matcher'),
        $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
