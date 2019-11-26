<?php

namespace Drupal\trlx_utility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for content related settings.
 */
class DisableBrandMaForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'trlx_disable_brand_ma.settings';

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
    return 'trlx_disable_brand_ma_config';
  }

  /**
   * Build form for external integration configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load module settings.
    $config = $this->config(static::SETTINGS);
    // Add field to select required site languages.
    $form['brand_key'] = [
      '#type' => 'textarea',
      '#multiple' => TRUE,
      '#title' => $this->t("Please enter brand keys"),
      '#default_value' => $config->get('brand_key'),
      '#attributes' => ['placeholder' => 'brand_key1, brand_key2, brand_key3'],
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
    $config->set('brand_key', $form_state->getValue('brand_key'));
    // Save config.
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
