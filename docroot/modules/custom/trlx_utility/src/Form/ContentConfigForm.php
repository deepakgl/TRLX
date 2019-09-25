<?php

namespace Drupal\trlx_utility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for content related settings.
 */
class ContentConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'trlx_utility.settings';

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
    return 'trlx_content_config';
  }

  /**
   * Build form for external integration configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load module settings.
    $config = $this->config(static::SETTINGS);

    // Entity Type Manager obj.
    $entityTypeManager = \Drupal::entityTypeManager();

    // Load all Section taxonomy terms.
    $sectionTerms = $entityTypeManager->getStorage('taxonomy_term')->loadTree('trlx_content_sections', 0, NULL, TRUE);

    if (!empty($sectionTerms)) {
      // Fieldset for point value.
      $form['stories_point_value'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Story Section(s) Point Value'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $sections = [];

      // Iterate through section taxonomy terms.
      foreach ($sectionTerms as $tid => $term) {
        // Section key.
        $sectionKey = $term->get('field_content_section_key')->getValue()[0]['value'];
        // Section title.
        $sectionTitle = $term->get('name')->getValue()[0]['value'];

        // Set sections array for content type mapping.
        $sections[$sectionKey] = $sectionTitle;

        // Set point value form field variables.
        $pointValueField = "point_value_" . $sectionKey;
        $pointValueTitle = 'Point Value ( ' . $sectionTitle . ' )';

        // Add number field for point value.
        $form['stories_point_value'][$pointValueField] = [
          '#type' => 'number',
          '#title' => $pointValueTitle,
          '#default_value' => $config->get($pointValueField),
        ];
      }

      // Load all content types.
      $contentTypes = $entityTypeManager->getStorage('node_type')->loadMultiple();

      if (!empty($contentTypes)) {
        // Fieldset for content type to section mapping.
        $form['content_type_section_mapping'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Content Type To Section(s) Mapping'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];

        // Iterate through all content types for section mapping.
        foreach ($contentTypes as $machineName => $contentType) {
          if ('stories' == $machineName) {
            // Content Type Section Mapping Field/Key.
            $contentTypeSectionMap = $machineName . '_sections';

            // Field to select sections for each content type.
            $form['content_type_section_mapping'][$contentTypeSectionMap] = [
              '#type' => 'select',
              '#multiple' => TRUE,
              '#title' => $this->t("Select Section(s) for content type: @contentType", ['@contentType' => $contentType->get('name')]),
              '#options' => $sections,
              '#default_value' => $config->get($contentTypeSectionMap),
            ];
          }
        }
      }
    }

    // Fetch all fields for "stories" bundle.
    $storiesFields = \Drupal::entityManager()->getFieldDefinitions('node', 'stories');

    $insiderCornerReqFields = [];
    if (!empty($storiesFields)) {

      // Iterate through all fields.
      foreach ($storiesFields as $fieldName => $fieldDefinition) {
        $label = $fieldDefinition->getLabel();
        // Skip base fields.
        if ($fieldDefinition->getFieldStorageDefinition()->isBaseField() == FALSE) {
          $insiderCornerReqFields[$fieldName] = $label;
        }
      }

      if (!empty($insiderCornerReqFields)) {
        // Sort fields.
        asort($insiderCornerReqFields);

        // Add field to select required fields for Insider Corner section.
        $form['insider_corner_req_fields'] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#title' => $this->t("Select required fields for Insider Corner section."),
          '#options' => $insiderCornerReqFields,
          '#default_value' => $config->get('insider_corner_req_fields'),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form for content configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load module config for editing.
    $config = $this->configFactory->getEditable(static::SETTINGS);

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
