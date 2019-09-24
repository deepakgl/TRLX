<?php

namespace Drupal\elx_reports\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Class UpdateElasticUserForm.
 *
 * @package Drupal\elx_reports\Form
 */
class LearnerSummaryReportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'learner_summary_report';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $common_utility = new CommonUtility();
    $form['language_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Language'),
      '#options' => $common_utility->getCompleteLanguages(),
    ];
    $form['market_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Market'),
      '#options' => $this->getMarkets(),
    ];
    $form['learner_summary_report'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export Learner Summary'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getUserInput()['op'] == 'Export Learner Summary') {
      $lang = $form_state->getUserInput()['language_list'];
      $market = $form_state->getUserInput()['market_list'];
      $result = [];
      try {
        // Connect with database.
        $db = Database::getConnection();
        // Query to get all uid from user_field_data table.
        $query = $db->query("SELECT ufd.uid,tm.name from users_field_data as ufd
          JOIN user__field_default_market as um ON um.entity_id = ufd.uid
          JOIN taxonomy_term_field_data as tm ON
          tm.tid = um.field_default_market_target_id
          WHERE ufd.status = 1
          AND ufd.langcode = :lang  AND
          um.field_default_market_target_id = :market", [
            ':lang' => $lang,
            ':market' => $market,
          ]);
        $result = $query->fetchAll();
      }
      catch (\Exception $e) {
        return $e->getMessage();
      }
      $market_name = $result[0]->name;
      $total_count = count($result);
      $file_name = $market_name . "_" . $lang .
      "learner_summary_report" . date('ymd') . ".csv";
      $download_folder = 'public://learners_export';
      $file_path = 'public://learners_export/' . $file_name;

      if (!is_dir($download_folder)) {
        mkdir($download_folder, 0777, TRUE);
      }
      if ($total_count >= 1000) {
        $limit = 50;
      }
      else {
        $limit = 100;
      }
      $data_chunks = array_chunk($result, $limit);
      $operations = [];
      // Check result is not empty.
      if (!empty($result)) {
        // Pass each uid & market name under batch process.
        foreach ($data_chunks as $key => $value) {
          $operations[] = [
            '\Drupal\elx_reports\LearnerSummaryReport::getLearnerSummary',
              [
                $value,
                $key,
                $file_path,
                $lang,
              ],
          ];
        }
        // Callback trigger after fetching the learner summary.
        $batch = [
          'title' => t('Exporting Learner Users Summary...'),
          'operations' => $operations,
          'finished' =>
          '\Drupal\elx_reports\LearnerSummaryReport::learnerSummaryCallback',
          'batch_redirect' => '/admin/report/learner-summary-report',
        ];
        batch_set($batch);
      }
      else {
        drupal_set_message("No users founds!", 'error');
      }
    }

  }

  /**
   * Get Market Name.
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
