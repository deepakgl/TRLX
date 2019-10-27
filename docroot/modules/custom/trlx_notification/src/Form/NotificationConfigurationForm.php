<?php

namespace Drupal\trlx_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\trlx_utility\Utility\CommonUtility;

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
  const USER_INDEX = 'user_index';
  const USER_INDEX_TYPE = 'user_index_type';
  const DELETE_NOTIFICATIONS = 'delete_notifications';
  const NODE_TYPE = 'nodetype';
  const BRAND_STORY_HEADING = 'brand_story_heading';
  const FACTSHEET_HEADING = 'factsheet_heading';
  const VIDEO_HEADING = 'video_heading';
  const TREND_HEADING = 'trend_heading';
  const INSIDER_CORNER_HEADING = 'insider_corner_heading';
  const SELLING_TIPS_HEADING = 'selling_tips_heading';
  const CONSUMER_HEADING = 'consumer_heading';
  const BRAND_LEVEL_HEADING = 'brand_level_heading';
  const SELLING_TIPS_LEVEL_HEADING = 'selling_tips_level_heading';
  const CONSUMER_LEVEL_HEADING = 'consumer_level_heading';
  const COMMENT_TAGS_HEADING = 'comment_tags_heading';
  const STAMPS_HEADING = 'stamps_heading';

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
    $form[self::USER_INDEX] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('User elasticsearch index'),
      self::HASH_DEFAULT_VALUE => $config->get(self::USER_INDEX),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::USER_INDEX_TYPE] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('User elasticsearch index type'),
      self::HASH_DEFAULT_VALUE => $config->get(self::USER_INDEX_TYPE),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::DELETE_NOTIFICATIONS] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Days in which notifications should be purged'),
      self::HASH_DEFAULT_VALUE => $config->get(self::DELETE_NOTIFICATIONS),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::NODE_TYPE] = [
      self::HASH_TYPE => 'select',
      self::HASH_TITLE => t('Select Node Types to send notifications'),
      '#options' => $nodes_list,
      '#multiple' => TRUE,
      self::HASH_REQUIRED => TRUE,
      self::HASH_DEFAULT_VALUE => $config->get(self::NODE_TYPE),
    ];
    $form[self::BRAND_STORY_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Brand story heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::BRAND_STORY_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::FACTSHEET_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Factsheet heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::FACTSHEET_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::VIDEO_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Video heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::VIDEO_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::TREND_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('TR trend story heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::TREND_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::INSIDER_CORNER_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Insider corner story heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::INSIDER_CORNER_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::SELLING_TIPS_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Selling tips story heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::SELLING_TIPS_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::CONSUMER_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Consumer heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::CONSUMER_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::BRAND_LEVEL_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Brand level heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::BRAND_LEVEL_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::SELLING_TIPS_LEVEL_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Selling tips level heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::SELLING_TIPS_LEVEL_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::CONSUMER_LEVEL_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Consumer level heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::CONSUMER_LEVEL_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::COMMENT_TAGS_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Comment tags heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::COMMENT_TAGS_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    $form[self::STAMPS_HEADING] = [
      self::HASH_TYPE => self::TEXTFIELD,
      self::HASH_TITLE => $this->t('Stamps heading.'),
      self::HASH_DEFAULT_VALUE => $config->get(self::STAMPS_HEADING),
      self::HASH_REQUIRED => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $commonUtility = new CommonUtility();
    // Load module config for editing.
    $config = $this->configFactory->getEditable(self::NOTIFICATION_SETTINGS);
    $commonUtility->getNotificationTranslation($config->get('stamps_heading'), 'en');
    // Iterate through form fields.
    foreach ($form_state->getValues() as $key => $value) {
      // Set config value.
      $config->set($key, $value);
    }
    // Save config.
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
