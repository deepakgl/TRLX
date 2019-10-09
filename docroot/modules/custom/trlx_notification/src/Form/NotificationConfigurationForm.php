<?php

namespace Drupal\trlx_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures notification settings.
 */
class NotificationConfigurationForm extends ConfigFormBase {

  const NOTIFICATION_SETTINGS = 'trlx_notification.settings';
  const SCHEME = 'scheme';
  const TEXTFIELD = 'textfield';
  const HASH_TYPE = '#type';
  const HASH_TITLE = '#title';
  const HASH_DEFAULT_VALUE = '#default_value';
  const HASH_REQUIRED = '#required';
  const SEARCH_INDEX = 'search_index';
  const SEARCH_INDEX_TYPE = 'search_index_type';
  const DELETE_NOTIFICATIONS = 'delete_notifications';
  const CRON_REQUIREMENT_NOTIFICATIONS = 'cron_requirement_notifications';
  const NODE_TYPE = 'nodetype';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notifications_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::NOTIFICATION_SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::NOTIFICATION_SETTINGS);
    $node_types = node_type_get_types();
    foreach ($node_types as $node_type) {
      $nodes_list[$node_type->get('type')] = $node_type->get('name');
    }
    $form[self::SCHEME] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Elasticsearch scheme'),
      self::HASH_DEFAULT_VALUE => $config->get(self::SCHEME),
      self::HASH_REQUIRED => TRUE,
    ];
    $form['host'] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Elasticsearch Host'),
      self::HASH_DEFAULT_VALUE => $config->get('host'),
      self::HASH_REQUIRED => TRUE,
    ];
    $form['port'] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Elasticsearch Port'),
      self::HASH_DEFAULT_VALUE => $config->get('port'),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::SEARCH_INDEX] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Elasticsearch index'),
      self::HASH_DEFAULT_VALUE => $config->get(self::SEARCH_INDEX),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::SEARCH_INDEX_TYPE] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Elasticsearch index type'),
      self::HASH_DEFAULT_VALUE => $config->get(self::SEARCH_INDEX_TYPE),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::DELETE_NOTIFICATIONS] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Days in which notifications should be purged'),
      self::HASH_DEFAULT_VALUE => $config->get(self::DELETE_NOTIFICATIONS),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::CRON_REQUIREMENT_NOTIFICATIONS] = [
      self::HASH_TYPE => 'checkbox',
      self::HASH_TITLE => $this->t('Check whether to run cron once in a day or not'),
      self::HASH_DEFAULT_VALUE => $config->get(self::CRON_REQUIREMENT_NOTIFICATIONS),
    ];
    $form[self::NODE_TYPE] = [
      self::HASH_TYPE => 'checkboxes',
      self::HASH_TITLE => t('Select Node Types to send notifications'),
      '#options' => $nodes_list,
      '#multiple' => TRUE,
      self::HASH_REQUIRED => TRUE,
      self::HASH_DEFAULT_VALUE => $config->get(self::NODE_TYPE),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search_scheme = $form_state->getValue(self::SCHEME);
    $search_host = $form_state->getValue('host');
    $search_port = $form_state->getValue('port');
    $search_index = $form_state->getValue(self::SEARCH_INDEX);
    $search_index_type = $form_state->getValue(self::SEARCH_INDEX_TYPE);
    $delete_notifications = $form_state->getValue(self::DELETE_NOTIFICATIONS);
    $cron_requirement_notifications = $form_state->getValue(self::CRON_REQUIREMENT_NOTIFICATIONS);
    $notification_nodes = $form_state->getValue(self::NODE_TYPE);
    $this->config(self::NOTIFICATION_SETTINGS)
      ->set(self::SCHEME, $search_scheme)
      ->set('host', $search_host)
      ->set('port', $search_port)
      ->set(self::SEARCH_INDEX, $search_index)
      ->set(self::SEARCH_INDEX_TYPE, $search_index_type)
      ->set(self::DELETE_NOTIFICATIONS, $delete_notifications)
      ->set(self::CRON_REQUIREMENT_NOTIFICATIONS, $cron_requirement_notifications)
      ->set(self::NODE_TYPE, $notification_nodes)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
