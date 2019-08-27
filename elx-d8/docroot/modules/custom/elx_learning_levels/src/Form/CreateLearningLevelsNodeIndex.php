<?php

namespace Drupal\elx_learning_levels\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Class TranslatedContentMigrationForm.
 *
 * @package Drupal\elx_points_migration\Form
 * TODO: This class will be removed once we create the learning level node.
 * Index in elastic.
 */
class CreateLearningLevelsNodeIndex extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_learning_levels_node_index';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['create_learning_levels_submit']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Learning Levels Node Index'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = db_select('node_field_data', 'n')
      ->distinct('nid')
      ->fields('n', ['nid'])
      ->condition('n.type', 'level_interactive_content')
      ->execute();
    $results = $query->fetchAll();

    $operations = [];
    // Pass each users data to batch process.
    foreach ($results as $key => $value) {
      $operations[] = [
        '\Drupal\elx_learning_levels\Form\CreateLearningLevelsNodeIndex::start',
        [
          $value,
          t('(Operation @operation)', ['@operation' => $key]),
        ],
      ];
    }
    // Set batch for content migration.
    $batch = [
      'title' => t('Creating Learning Level Elastic Index...'),
      'operations' => $operations,
      'finished' => '\Drupal\elx_learning_levels\Form\CreateLearningLevelsNodeIndex::finished',
    ];
    batch_set($batch);
  }

  /**
   * Batch 'start' callback.
   */
  public function start($id, $operation_details, &$context) {
    $context['results']['total'][] = $id;
    $common_utility = new CommonUtility();
    $client = $common_utility->setElasticConnectivity();
    if (!$client) {
      return FALSE;
    }
    $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
    // Check for index existence previously.
    $params = [
      'index' => $env . '_node_data',
      'type' => 'node',
      'id' => $id->nid,
    ];
    $node_exist = $client->exists($params);
    // If index not exist, create new index.
    if (!$node_exist) {
      $params['body'] = [
        'favorites_by_user' => [],
        'downloads_by_user' => [],
        'bookmarks_by_user' => [],
      ];
      $response = $client->index($params);
      $context['results']['processed'][] = $id->nid;
      $context['message'] = t('Running Batch "@id" @details',
      ['@id' => $id->nid, '@details' => $operation_details]);
    }
  }

  /**
   * Batch 'finished' callback.
   */
  public function finished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('Total results @count', ['@count' => count($results['total'])]));
      $messenger->addMessage(t('@count results processed.', ['@count' => count($results['processed'])]));
      $messenger->addMessage(t('The final result was "%final"', ['%final' => end($results)]));
    }
    else {
      $error_operation = reset($operations);
      $messenger->addMessage(
      t('An error occurred while processing @operation with arguments : @args',
        [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
        ]
      )
      );
    }
  }

}
