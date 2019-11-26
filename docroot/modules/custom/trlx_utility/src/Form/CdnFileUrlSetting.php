<?php

namespace Drupal\trlx_utility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for content related settings.
 */
class CdnFileUrlSetting extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'trlx_cdn_file_url.settings';

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
    return 'trlx_cdn_file_url_config';
  }

  /**
   * Build form for external integration configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load module settings.
    $config = $this->config(static::SETTINGS);
    // Add field to select required site languages.
    $form['cdn_base_url'] = [
      '#type' => 'textfield',
      '#multiple' => TRUE,
      '#title' => $this->t("Please enter cdn base url"),
      '#default_value' => $config->get('cdn_base_url'),
      '#attributes' => ['placeholder' => 'https://localhost:81'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form for content configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load module config for editing.
    $config = $this->configFactory->getEditable(static::SETTINGS);
    // Iterate through form fields.
    $config->set('cdn_base_url', $form_state->getValue('cdn_base_url'));
    // Save config.
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
