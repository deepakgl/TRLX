<?php

namespace Drupal\elx_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\elx_user\Utility\UserUtility;

/**
 * Class UserExportForm.
 *
 * @package Drupal\elx_user\Form
 */
class UserExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'export_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_path = \Drupal::service('path.current')->getPath();
    if ($current_path == '/admin/people/user-export') {
      $user_info = new UserUtility();
      $user = User::load(\Drupal::currentUser()->id());
      $uid = $user->get('uid')->value;
      $markets = $user_info->getMarketsName($uid);
      // $form['markets'] = [
      //        '#type' => 'select',
      //        '#title' => t('Markets'),
      //        '#options' => $markets,
      //      ];
      //      $form['export_users_list'] = [
      //        '#type' => 'submit',
      //        '#value' => $this->t('Export Users'),
      //      ];
    }
    elseif ($current_path == '/admin/active-users') {
      // $form['user_active'] = [
      //        '#type' => 'submit',
      //        '#value' => $this->t('csv'),
      //        '#button_type' => 'primary',
      //        '#weight' => 11,
      //      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $user_input = $form_state->getUserInput()['op'];
    if ($user_input == 'Export Users') {
      $market_id = $form_state->getUserInput()['markets'];
      $markets = (!is_array($market_id)) ? [$market_id] : $market_id;
      $user_utility = new UserUtility();
      $roles = $user_utility->getUserRoles(\Drupal::currentUser()->id(), 'all',
       ['el_regional_market_admin']);
      if ($roles) {
        $query = \Drupal::database();
        $query = $query->select('user__field_default_market', 'u');
        $query->distinct();
        $query->join('user__roles', 'ur', 'u.entity_id = ur.entity_id');
        $query->fields('u', ['entity_id']);
        $query->condition('u.field_default_market_target_id', $markets, 'IN');
        $query->condition('ur.roles_target_id', ['administrator', 'el_nyo_global_education_system_admin'], 'NOT IN');
        $result_uid = $query->execute()->fetchCol();
      }
      else {
        $query = \Drupal::database()->select('user__field_default_market', 'u');
        $query->fields('u', ['entity_id']);
        $query->condition('u.field_default_market_target_id', $markets, 'IN');
        $result_uid = $query->execute()->fetchCol();
      }
    }
    // If user input for active list .
    if ($user_input == 'csv') {
      $query = \Drupal::database()->select('users_field_data', 'u');
      $query->fields('u', ['uid']);
      $query->condition('u.status', 1, '=');
      $result_uid = $query->execute()->fetchCol();
    };
    $name = 'export_user.csv';
    $header = self::writeHeader($name);
    $download_folder = 'public://export_user';
    $file_path = 'public://export_user/' . $name;
    if (!file_exists()) {
      mkdir($download_folder, 0777, TRUE);
    }
    $output = fopen($file_path, "w");
    fputcsv($output, $header);
    fclose($output);
    $total_count = count($result_uid);
    $limit = 1000;

    $data_chunks = array_chunk($result_uid, $limit);
    $operations = [];
    // Check result is not empty.
    if (!empty($result_uid)) {
      foreach ($data_chunks as $key => $value) {
        $operations[] = [
          'Drupal\elx_user\Utility\UserExport::exportUsersStart',
          [
            $value, $file_path, $header,
          ],
        ];
      }
      // Set batch for Exporting Users.
      $batch = [
        'title' => t('Exporting Users ...'),
        'operations' => $operations,
        'finished' => 'Drupal\elx_user\Utility\UserExport::exportFinishedCallback',
      ];
      batch_set($batch);
    }
    else {
      drupal_set_message("No users founds!", 'error');
    }
  }

  /**
   * Header name to generate csv..
   *
   * @return array
   *   Array of header.
   */
  public static function writeHeader($name) {
    $header = [
      'status' => 'Status',
      'email' => 'Email',
      'langcode' => 'Language',
      'access' => 'Last Access',
      'first_name' => 'First Name',
      'last_name' => 'Last Name',
      'employer_number' => 'Employee ID',
      'employment_status' => 'Employment Status',
      'account' => 'Account Name',
      'field_door' => 'Door',
      'field_city' => 'City',
      'state' => 'State',
      'field_country' => 'Country',
      'hire_date' => 'Hire Date',
      'counter_manager' => 'Counter Manager',
      'education_manager_executive' => 'Education Manager/Executive',
      'sales_education_executive' => 'Sales/Sales Education Executive',
      'field_field_sales_director_regio'  => 'Regional Sales/Sales Education Manager',
      'regional_vice_president' => 'Regional Vice President',
      'general_manager' => 'General Manager/Brand Manager' ,
      'roles' => 'Active Learner Groups',
      'markets' => 'Market Name',
    ];

    return $header;
  }

}
