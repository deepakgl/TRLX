<?php

namespace Drupal\trlx_utility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for content related settings.
 */
class ImageStyleGenerateConfig extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'trlx_image_style.settings';

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
    return 'trlx_image_style_generate_config';
  }

  /**
   * Build form for external integration configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load module settings.
    $config = $this->config(static::SETTINGS);
    // Add field to select required site languages.
    $form['scheme_url'] = [
      '#type' => 'textfield',
      '#multiple' => TRUE,
      '#title' => $this->t("Please enter url"),
      '#default_value' => $config->get('scheme_url'),
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
    $config->set('scheme_url', $form_state->getValue('scheme_url'));
    // Save config.
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
