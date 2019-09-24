<?php

namespace Drupal\elx_quiz\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for quiz.
 */
class QuizConfigForm extends ConfigFormBase {

  /**
   * Get editable config names of quiz.
   */
  protected function getEditableConfigNames() {
    return ['elx_quiz.settings'];
  }

  /**
   * Returns the form’s unique ID.
   */
  public function getFormId() {
    return 'elx_quiz_configurations';
  }

  /**
   * Build form for quiz configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('elx_quiz.settings');
    $quiz_config_fields = [
      'Enable redis cache' => 'enable_redis_cache',
      'Environment' => 'environment',
      'Redis host' => 'redis_host',
      'Redis port' => 'redis_port',
      'Redis password' => 'redis_password',
      'Redis database' => 'redis_base',
    ];
    foreach ($quiz_config_fields as $key => $value) {
      $form[$value] = [
        '#type' => ($value != 'enable_redis_cache') ? 'textfield' : 'checkbox',
        '#title' => $key,
        '#default_value' => $config->get($value),
        '#states' => ($value != 'enable_redis_cache') ? [ 'visible' => [
          ':input[name=enable_redis_cache]' => ['checked' => TRUE ]]] : []
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form for quiz configurations.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('elx_quiz.settings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
