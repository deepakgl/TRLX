<?php

namespace Drupal\elx_points_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MigratePointsForm.
 *
 * @package Drupal\elx_points_migration\Form
 */

class MigratePointsForm extends FormBase {
  /**
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'points_migration_form';
  }
  /**
   * {@inheritdoc}
   */



  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['points_migration'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Migrate Points'),
    );
    $form['badge_master_migration'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Migrate Badge Master'),
    );
    return $form;
  }

    /**
   * {@inheritdoc}
   */

    public function submitForm(array &$form, FormStateInterface $form_state) {
      if ($form_state->getUserInput()['op'] == 'Migrate Points') {
        $migration_mail = \Drupal::config('elx_utility.settings')->get('migration_mail');
        $migration_mail = "($migration_mail)";
        // Set Limit and offset to run migration in chunks.
        $limit = \Drupal::request()->query->get('LIMIT') ? \Drupal::request()->query->get('LIMIT') : 1000;
        $offset = \Drupal::request()->query->get('OFFSET') ? \Drupal::request()->query->get('OFFSET') : 0;

        // Connect with migration database.
        $ext_db = \Drupal\Core\Database\Database::getConnection();
        $ext_db = \Drupal\Core\Database\Database::getConnection('default','migrate');

        // Query to get all users points from legacy database.
        $result = $operations = [];
        try {
          $query = $ext_db->query("SELECT DISTINCT users.uid AS uid, userpoints_total.points AS userpoints_total_points
            FROM {users} users LEFT JOIN {userpoints_total} userpoints_total ON users.uid = userpoints_total.uid WHERE users.mail IN  $migration_mail LIMIT " . $limit . " OFFSET " . $offset);
            $result = $query->fetchAll();
        }
        catch (\Exception $e) {
          return $e->getMessage();
        }

        // Pass each users points to batch process.
        foreach ($result as $key => $value) {
          $operations[] = [
            '\Drupal\elx_points_migration\MigratePointsTrigger::MigratePointsStart',
            [
              $value,
              t('(Operation @operation)', ['@operation' => $key]),
            ],
          ];
        }
        // Set batch for points migration.
        $batch = array(
          'title' => t('Migrating Points...'),
          'operations' => $operations,
          'finished' => '\Drupal\elx_points_migration\MigratePointsTrigger::MigratePointsFinishedCallback',
        );
        batch_set($batch);
      }
      elseif ($form_state->getUserInput()['op'] == 'Migrate Badge Master') {
        migrate_badge_master();
      }
    }
}
