<?php

namespace Drupal\elx_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TranslatedContentMigrationForm.
 *
 * @package Drupal\elx_points_migration\Form
 */

class TranslatedContentMigrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'translated_content_migration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    $node_types_options = [];
    foreach ($node_types as $node_type) {
      $node_types_options[$node_type->id()] = $node_type->label();
    }
    $form['translate_content']['content_type'] = [
      '#title' => 'Content type',
      '#type' => 'select',
      '#options' => $node_types_options
    ];
    $form['translate_content']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate Translated Content')
    ];

    return $form;
  }

   /**
    * {@inheritdoc}
    */
   public function submitForm(array &$form, FormStateInterface $form_state) {
     $type = $form_state->getValue('content_type');
     if ($type == 'tools-pdf') {
       $type = 'tools';
     }
    // Connect with migration database.
    $ext_db = \Drupal\Core\Database\Database::getConnection('default','migrate');
    $query = $ext_db->select('node', 'n')
    ->fields('n')
    ->condition('n.type', $type, '=')
    ->condition('n.language', 'en', '!=');
    $results = $query->execute()->fetchAll();
    switch($results[0]->type) {
      case 'tools':
        $op_callback = '\ToolsTrigger::start';
        $finished_callback = '\ToolsTrigger::finished';
      break;
      case 'product_detail':
        $op_callback = '\ProductsTrigger::start';
        $finished_callback = '\ProductsTrigger::finished';
      break;

      default:
      break;
    }
    $operations = [];
    // Pass each users data to batch process.
    foreach ($results as $key => $value) {
      $value->legacy_content_type = $form_state->getValue('content_type');
      if ($value->language == 'zhhans') {
        $value->language = 'zh-hans';
      }
      elseif ($value->language == 'zhhant') {
        $value->language = 'zh-hant';
      }
      $operations[] = [
        '\Drupal\elx_migration' . $op_callback,
        [
          $value,
          t('(Operation @operation)', ['@operation' => $key]),
        ],
      ];
    }
    // Set batch for content migration.
    $batch = array(
      'title' => t('Migrating Content...'),
      'operations' => $operations,
      'finished' => '\Drupal\elx_migration' . $finished_callback,
    );
    batch_set($batch);
    }
}
