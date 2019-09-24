<?php

namespace Drupal\elx_points_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MigratePointsForm.
 *
 * @package Drupal\elx_points_migration\Form
 */

class MigrateFavoritesForm extends FormBase {
  /**
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'favorites_migration_form';
  }
  /**
   * {@inheritdoc}
   */



  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['favorites_user_migration'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Migrate User Favorites'),
    );
    $form['favorites_node_migration'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Migrate Node Favorites'),
    );
    return $form;
  }

    /**
   * {@inheritdoc}
   */

    public function submitForm(array &$form, FormStateInterface $form_state) {
      // Connect with migration database.
      $ext_db = \Drupal\Core\Database\Database::getConnection('default','migrate');
      // Set Limit and offset to run migration in chunks.
      $limit = \Drupal::request()->query->get('LIMIT') ? \Drupal::request()->query->get('LIMIT') : 1000;
      $offset = \Drupal::request()->query->get('OFFSET') ? \Drupal::request()->query->get('OFFSET') : 0;

      if ($form_state->getUserInput()['op'] == 'Migrate Node Favorites') {
        // Query to get all users points from legacy database.
        $result = $operations = [];
        try {
          $query = $ext_db->query("SELECT DISTINCT node.nid AS nid FROM {node}  WHERE node.status = 1 LIMIT " . $limit . " OFFSET " . $offset);
          $result = $query->fetchAll();
        }
        catch (\Exception $e) {
          return $e->getMessage();
        }
        // Pass each users points to batch process.
        foreach ($result as $key => $value) {
          $operations[] = [
            '\Drupal\elx_points_migration\MigrateFavoritesTrigger::MigrateFavoritesByNode',
            [
              $value,
              t('(Operation @operation)', ['@operation' => $key]),
            ],
          ];
        }
        // Set batch for points migration.
        $batch = array(
          'title' => t('Migrating Favorites...'),
          'operations' => $operations,
          'finished' => '\Drupal\elx_points_migration\MigrateFavoritesTrigger::MigrateFavoritesFinishedCallback',
        );
        batch_set($batch);
      }
      if ($form_state->getUserInput()['op'] == 'Migrate User Favorites') {
        // Query to get all users points from legacy database.
        $result = $operations = [];
        try {
          $query = $ext_db->query("SELECT DISTINCT users.uid AS uid FROM {users} LIMIT " . $limit . " OFFSET " . $offset);
          $result = $query->fetchAll();
        }
        catch (\Exception $e) {
          return $e->getMessage();
        }
        // Pass each users points to batch process.
        foreach ($result as $key => $value) {
          $operations[] = [
            '\Drupal\elx_points_migration\MigrateFavoritesTrigger::MigrateFavoritesByUser',
            [
              $value,
              t('(Operation @operation)', ['@operation' => $key]),
            ],
          ];
        }
        // Set batch for points migration.
        $batch = array(
          'title' => t('Migrating Favorites...'),
          'operations' => $operations,
          'finished' => '\Drupal\elx_points_migration\MigrateFavoritesTrigger::MigrateFavoritesFinishedCallback',
        );
        batch_set($batch);
      }
    }
}
