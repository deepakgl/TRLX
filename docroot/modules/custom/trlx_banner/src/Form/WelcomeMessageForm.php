<?php

namespace Drupal\trlx_banner\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for content related settings.
 */
class WelcomeMessageForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'trlx_banner.welcome_message.settings';

  /**
   * Get editable config names of external integration.
   */
  protected function getEditableConfigNames() {
    return [static::SETTINGS];
  }

  /**
   * Returns the form’s unique ID.
   */
  public function getFormId() {
    return 'welcome_message_admin_settings';
  }

  /**
   * Build form for external integration configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load module settings.
    $config = $this->config(static::SETTINGS);
    $site_languages = \Drupal::config('trlx_utility.settings');
    $languages = \Drupal::service('language_manager')->getStandardLanguageList();

    foreach ($site_languages->get('site_languages') as $key => $value) {
      $message = 'message_' . $value;
      $form[$message] = [
        '#type' => 'textfield',
        '#title' => 'Enter Welcome Message (' . $languages[$value][0] . ')',
        '#default_value' => !empty($config->get($message)) ? $config->get($message) : '',
      ];

      // Add field to select required site languages.
      $message_langcode = 'lang_code_' . $value;
      $form[$message_langcode] = [
        '#type' => 'hidden',
        '#title' => $value . ' Langcode',
        '#default_value' => $value,
        '#attribute' => array('hidden' => TRUE),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form for content configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Iterate through form fields.
    // Load module config for editing.
    $config = $this->config(static::SETTINGS);
    foreach ($form_state->getValues() as $key => $value) {
      // Set config value.
     $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
