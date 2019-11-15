<?php

namespace Drupal\elx_elastic_user\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UpdateElasticUserForm.
 *
 * @package Drupal\elx_user\Form
 */
class UpdateElasticUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_elastic_users';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['elastic_user_updation'] = [
      '#type' => 'submit',
      '#value' => $this->t('Elastic User Updation'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getUserInput()['op'] == 'Elastic User Updation') {
      // Set Limit and offset to update elastic users data in chunks.
      $page = \Drupal::request()->query->get('page') * 5000;
      $limit = \Drupal::request()->query->get('LIMIT') ?
       \Drupal::request()->query->get('LIMIT') : 5000;
      $offset = $page ? $page : 0;
      // Connect with migration database.
      $db = Database::getConnection();
      // Query to get all uid from user_field_data table.
      $result = [];
      try {
        $query = $db->query("SELECT uid from users_field_data LIMIT " . $limit .
         " OFFSET " . $offset);
        $result = $query->fetchAll();
      }
      catch (\Exception $e) {
        return $e->getMessage();
      }
      $operations = [];
      // Pass each user for updation into batch process.
      foreach ($result as $key => $value) {
        $operations[] = [
          '\Drupal\elx_elastic_user\UpdateElasticUser::updateElasticUserStart',
            [
              $value,
              t('(Operation @operation)', ['@operation' => $key]),
            ],
        ];
      }
      // Set batch for points migration.
      $batch = [
        'title' => t('Updating Elastic Users Data...'),
        'operations' => $operations,
        'finished' =>
        '\Drupal\elx_elastic_user\UpdateElasticUser::updationElasticCallback',
      ];
      batch_set($batch);
    }
  }

}
