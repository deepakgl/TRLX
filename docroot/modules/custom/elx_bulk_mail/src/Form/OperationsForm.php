<?php

namespace Drupal\elx_bulk_mail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Bulk mail form.
 */
class OperationsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elx_bulk_mail';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_path = \Drupal::service('path.current')->getPath();
    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Import CSV File'),
      '#description' => $this->t('Select the CSV file to be imported.'),
      '#required' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    if ($current_path == '/admin/config/people/elx_bulk_mail') {
      $form['bulk_mail'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send Bulk Mail'),
        '#button_type' => 'primary',
      ];
    }
    elseif ($current_path == '/admin/config/people/elx_user_unblock') {
      $form['user_unblock'] = [
        '#type' => 'submit',
        '#value' => $this->t('Unblock user'),
        '#button_type' => 'primary',
      ];
      $form['user_block'] = [
        '#type' => 'submit',
        '#value' => $this->t('Block user'),
        '#button_type' => 'primary',
      ];
    }
    elseif ($current_path = '/admin/config/people/elx_bulk_map_market') {
      $markets = self::getMarkets();
      $form['elx_market_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Market Id'),
        '#required' => TRUE,
        '#description' => t('Select market that needs to be added.'),
        '#options' => $markets,
      ];
      $form['content_market_mapping'] = [
        '#type' => 'submit',
        '#value' => $this->t('Map Market'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file_name = _elx_file_name($form_state->getValue('file_upload')[0]);
    if (pathinfo($file_name, PATHINFO_EXTENSION) != 'csv') {
      return $form_state->setErrorByName('file_upload', t('The file extension is not valid, please upload .csv file only.'));
    }
  }

  /**
   * Form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_uri = _elx_file_uri($form_state->getValue('file_upload')[0]);
    if (($handle = fopen($file_uri, "r")) !== FALSE) {
      $user_input = $form_state->getUserInput()['op'];
      if ($user_input == 'Send Bulk Mail') {
        $op_callback = 'bulkMail';
      }
      elseif ($user_input == 'Unblock user') {
        $op_callback = 'unblockUser';
      }
      elseif ($user_input == 'Map Market') {
        $op_callback = 'bulkUpload';
        $markets = self::getMarkets();
        $operations = [];
        $market_id = $form_state->getUserInput()['elx_market_id'];
        $value = [
          'nid' => '',
          'lang' => 'en',
          'market_id' => '',
          'market_name' => '',
        ];
      }
      elseif ($user_input == 'Block user') {
        $op_callback = 'blockUser';
      }
      while (($data = fgetcsv($handle)) !== FALSE) {
        if ($user_input == 'Map Market') {
          $value['nid'] = $data[0];
          $value['lang'] = !empty($data[1]) ? $data[1] : 'en';
          $value['market_id'] = $market_id;
          $value['market_name'] = $markets[$market_id];
        }
        else {
          $value = $data[0];
        }
        $operations[] = [
          '\Drupal\elx_bulk_mail\UserOperations::' . $op_callback,
          [
            $value,
            t('(Operation @operation)', ['@operation' => $data[0]]),
          ],
        ];
      }
      // Set batch to unblock user.
      $batch = [
        'title' => t('Performing bulk operations...'),
        'operations' => $operations,
        'finished' => '\Drupal\elx_bulk_mail\UserOperations::finishedCallback',
      ];

      batch_set($batch);
    }
  }

  /**
   * Get Market Tid.
   *
   * @return array
   *   Array of Market ids.
   */
  public static function getMarkets() {
    $markets = [];
    // Get Term id Market Taxonomy.
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('markets');
    foreach ($terms as $term) {
      if ($term->parents['0'] != '0') {
        $markets[$term->tid] = $term->name;
      }
    }

    return $markets;
  }

}
