<?php

namespace Drupal\elx_utility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure form for external integration configurations.
 */
class ExternalIntegrationConfigForm extends ConfigFormBase {

  /**
   * Get editable config names of external integration.
   */
  protected function getEditableConfigNames() {
    return ['elx_utility.settings'];
  }

  /**
   * Returns the form’s unique ID.
   */
  public function getFormId() {
    return 'elx_external_integration';
  }

  /**
   * Build form for external integration configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('elx_utility.settings');
    $external_fields = [
      'Elastic host' => 'elastic_host',
      'Elastic port' => 'elastic_port',
      'Elastic scheme' => 'elastic_scheme',
      'Elastic Username' => 'elastic_username',
      'Elastic Password' => 'elastic_password',
      'ELastic Environment' => 'elx_environment',
      'LRS statement id' => 'lrs_statement_id',
      'Lumen Url' => 'lumen_url',
      'Redis Host' => 'redis_host',
      'Redis Port' => 'redis_port',
      'Redis Password' => 'redis_password',
      'Redis Database' => 'redis_base',
      'ELX Site Url' => 'elx_site_url',
      'ELX Front End URL' => 'elx_front_end_url',
      'ELX Get Started Level Id' => 'elx_get_started_level_id',
      'Migration Limit' => 'migration_limit',
      'Migration Offset' => 'migration_offset',
      'Email subject' => 'elx_mail_subject',
      'Email body' => 'elx_mail_body',
      'Migration Mails' => 'migration_mail',
    ];

    foreach ($external_fields as $key => $value) {
      $type = 'textfield';
      if ($value == 'elx_mail_body' || $value == 'elx_mail_subject') {
        $type = 'textarea';
      }
      $form[$value] = [
        '#type' => $type,
        '#title' => $key,
        '#default_value' => $config->get($value),
      ];
      if ($value == 'elx_users_list_access_on_site_down') {
        $form[$value]['#description'] = t('Multiple User Ids should be comma separated');
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form for external integration config.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('elx_utility.settings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
