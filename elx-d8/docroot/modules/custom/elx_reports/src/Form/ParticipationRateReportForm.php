<?php

namespace Drupal\elx_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\elx_reports\ParticipationRateReport;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class ParticipationRateReportForm.
 */
class ParticipationRateReportForm extends FormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'participation_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['granularity'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Report Granularity'),
      '#options' => [
        'region' => $this->t('Region'),
        'market' => $this->t('Market'),
        'account' => $this->t('Account'),
        'door' => $this->t('Door'),
      ],
      // Make this field required.
      '#required' => TRUE,
    ];
    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Select Date'),
      '#required' => TRUE,
    ];
    // Set hidden market field.
    if (!empty(\Drupal::request()->get('market_id'))
      && is_numeric(\Drupal::request()->get('market_id'))) {
      $form['market_id'] = [
        '#type' => 'hidden',
        '#value' => \Drupal::request()->get('market_id'),
      ];
    }

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Report'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('granularity'))
      && !empty($form_state->getValue('start_date'))) {
      return $this->getParticipationRateReport($form_state->getValue([]));
    }
  }

  /**
   * Get getParticipationRateReport as CSV.
   */
  public function getParticipationRateReport($options) {
    $filename = ParticipationRateReport::CSV_FILE_NAME;
    $market_id = NULL;
    // Set header to create CSV.
    header("Cache-Control: public");
    header("Content-Type: application/octet-stream");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0");
    header("Content-Disposition: attachment; filename=\"$filename\";");
    header("Content-Transfer-Encoding: binary");
    $start_date = DrupalDateTime::createFromFormat('Y-m-d', $options['start_date']);
    $granularity = $options['granularity'];
    if (isset($options['market_id'])) {
      $market_id = $options['market_id'];
      // Set Market granularity.
      $granularity['market'] = 'market';
    }
    $report = new ParticipationRateReport($granularity, $start_date, $market_id);
    $report->toCsv();
    exit();
  }

}
