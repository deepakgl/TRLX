<?php

namespace Drupal\elx_bulk_update_access_by_role\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Bulk Update Field Access By Role form.
 */
class OperationsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elx_bulk_update_access_by_role';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $type = self::getTypes();
    $form['elx_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Content Type'),
      '#required' => TRUE,
      '#description' => t('Select Content Type that needs to be updated for access by role.'),
      '#options' => $type,
    ];
    $form['elx_content_type_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Batch Process'),
      '#button_type' => 'primary',
    ];

    return $form;
  }


  /**
   * Form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getValue('elx_content_type');
    $nids = db_select('node', 'n')
      ->fields('n', array('nid'))
      ->condition('type', $user_input, '=')
      ->execute()
      ->fetchAll();
    foreach ($nids as $key => $nid) {
      $operations[] = [
        '\Drupal\elx_bulk_update_access_by_role\UpdateFieldOperations::bulkUpdate',
        [
          $nid->nid,
          t('(Operation @operation)', ['@operation' => $key]),
        ],
      ];
    }
    // Set batch to update access by role.
    $batch = [
      'title' => t('Performing bulk operations...'),
      'operations' => $operations,
      'finished' => '\Drupal\elx_bulk_update_access_by_role\UpdateFieldOperations::finishedCallback',
    ];

    batch_set($batch);
  }

  /**
   * Get Market Tid.
   *
   * @return array
   *   Array of Market ids.
   */
  public static function getTypes() {
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    $type_machine_name = array_keys($types);
    $data = array_combine($type_machine_name, $type_machine_name);

    return $data;
  }

}
