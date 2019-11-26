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
      'TRLX Site Url' => 'elx_site_url',
      'TRLX Front End URL' => 'elx_front_end_url',
      'Middleware LB Name' => 'middleware_lb_name',
    ];

    foreach ($external_fields as $key => $value) {
      $form[$value] = [
        '#type' => 'textfield',
        '#title' => $key,
        '#default_value' => $config->get($value),
      ];
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
