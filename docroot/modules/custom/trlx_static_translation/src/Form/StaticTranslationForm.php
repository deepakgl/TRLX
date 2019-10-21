<?php

namespace Drupal\trlx_static_translation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Bulk mail form.
 */
class StaticTranslationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trlx_static_translation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Import CSV File'),
      '#description' => $this->t('Select the CSV file to be imported.'),
      '#required' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    $form['static_translation'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Static Translation'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file_name = _trlx_file_name($form_state->getValue('file_upload')[0]);
    if (pathinfo($file_name, PATHINFO_EXTENSION) != 'csv') {
      return $form_state->setErrorByName('file_upload', t('The file extension is not valid, please upload .csv file only.'));
    }
  }

  /**
   * Form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_uri = _trlx_file_uri($form_state->getValue('file_upload')[0]);
    if (($handle = fopen($file_uri, 'r')) !== FALSE) {
      while (($data = fgetcsv($handle)) !== FALSE) {
        $value = [
          'name' => $data[0],
          'string_translation' => $data[1],
          'language' => $data[2],
        ];
        $operations[] = [
          '\Drupal\trlx_static_translation\StaticTranslationsOperations::import',
          [
            $value,
            t('(Operation @operation)', ['@operation' => $value]),
          ],
        ];
      }
      // Set batch to unblock user.
      $batch = [
        'title' => t('Performing bulk operations...'),
        'operations' => $operations,
        'finished' => '\Drupal\trlx_static_translation\StaticTranslationsOperations::finishedCallback',
      ];

      batch_set($batch);
    }
  }
}
