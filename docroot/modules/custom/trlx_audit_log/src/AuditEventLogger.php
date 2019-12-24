<?php

namespace Drupal\trlx_audit_log;

/**
 * Class to create the corresponding events for the entities.
 *
 * @package Drupal\audit_log
 */
class AuditEventLogger {

  const LOGGER_TYPE = 'audit_log';

  /**
   * Get the type of the logger.
   */
  public function getLoggerType() {
    return self::LOGGER_TYPE;
  }

  /**
   * Gets the label of the entity being accessed.
   *
   * @param object $entity
   *   The object of the entity.
   *
   * @return string
   *   The label of the entity.
   */
  public function getEntityLabel($entity) {
    $label = '';
    $entity_id = $entity->getEntityTypeId();
    switch ($entity_id) {
      case 'node':
        $label = $entity->getTitle();
        break;

      case 'taxonomy_vocabulary':
        $label = $entity->get('name');
        break;

      case 'taxonomy_term':
        $label = $entity->getName();
        break;

      case 'user':
        $label = $entity->getUsername();
        break;

      case 'image_style':
        $label = $entity->label();
        break;

      case 'view':
        $label = $entity->label();
        break;
    }

    return $label;
  }

  /**
   * Sets audit log message for the entity events.
   *
   * @param object $entity
   *   The object of the entity.
   * @param object $event
   *   The entity event accessed by the user.
   *
   * @return string
   *   The audit log message.
   */
  public function getAuditLogMessage($entity, $event) {

    $entity_label = $this->getEntityLabel($entity);
    $entity_id = $entity->getEntityTypeId();

    // Create the.
    $message = $entity_id . ' : ' . $event . ' ' . $entity_label;
    return $message;
  }

  /**
   * Get the Request url for the event.
   *
   * Logs the url from where the user has requested for the event on entity.
   */
  public function getRequestUri() {

    global $base_url;
    return $base_url . \Drupal::request()->getRequestUri();
  }

}
