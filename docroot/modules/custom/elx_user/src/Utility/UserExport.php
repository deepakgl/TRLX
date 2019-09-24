<?php

namespace Drupal\elx_user\Utility;

/**
 * User Export Implementation.
 */
class UserExport {

  /**
   * Exporting the user information from our system into CSV.
   *
   * @param array $result
   *   Values of user export.
   * @param string $file_path
   *   File path for download file.
   * @param string $header
   *   Header for download file.
   * @param array $context
   *   Batch context.
   */
  public static function exportUsersStart(array $result, $file_path, $header, array &$context) {
    $context['sandbox']['file'] = $file_path;
    $context['sandbox']['fields'] = $header;
    $handle = fopen($context['sandbox']['file'], 'a');
    $user_info = new UserUtility();
    $fetch_values = [
      'users_field_data' => [
        'value' => [
          'status',
          'mail',
          'langcode',
          'access',
          'uid',
        ],
      ],
      'user__field_first_name' => [
        'alias' => 'fn',
        'value' => 'field_first_name_value',
      ],
      'user__field_last_name' => [
        'alias' => 'ln',
        'value' => 'field_last_name_value',
      ],
      'user__field_employer_number' => [
        'alias' => 'ue',
        'value' => 'field_employer_number_value',
      ],
      'user__field_employment_status' => [
        'alias' => 'emps',
        'value' => 'field_employment_status_value',
      ],
      'user__field_account_name' => [
        'alias' => 'an',
        'value' => 'field_account_name_value',
      ],
      'user__field_door' => [
        'alias' => 'door',
        'value' => 'field_door_value',
      ],
      'user__field_city' => [
        'alias' => 'ct',
        'value' => 'field_city_value',
      ],
      'user__field_state' => [
        'alias' => 'st',
        'value' => 'field_state_value',
      ],
      'user__field_country' => [
        'alias' => 'cy',
        'value' => 'field_country_value',
      ],
      'user__field_hire_date' => [
        'alias' => 'hd',
        'value' => 'field_hire_date_value',
      ],
      'user__field_counter_manager' => [
        'alias' => 'cm',
        'value' => 'field_counter_manager_value',
      ],
      'user__field_education_manager_executiv' => [
        'alias' => 'edum',
        'value' => 'field_education_manager_executiv_value',
      ],
      'user__field_account_field_executive' => [
        'alias' => 'edue',
        'value' => 'field_account_field_executive_value',
      ],
      'user__field_field_sales_director_regio' => [
        'alias' => 'sdr',
        'value' => 'field_field_sales_director_regio_value',
      ],
      'user__field_regional_vice_president' => [
        'alias' => 'rvp',
        'value' => 'field_regional_vice_president_value',
      ],
      'user__field_general_manager_brand_mana' => [
        'alias' => 'gmbm',
        'value' => 'field_general_manager_brand_mana_value',
      ],
    ];
    $get_user_data = $user_info->userData($result, $fetch_values);
    $custom_arr = [];
    foreach ($get_user_data as $key => $value) {
      if ($value->uid) {
        $uid = $value->uid;
        $roles = $user_info->getUserRoles($uid, 'all');
        $custom_arr['roles'] = is_array($roles) ? implode(", ", $roles) : $roles;
        $markets_name = $user_info->getMarketsName($uid);
        $custom_arr['markets'] = implode(",", array_values($markets_name));
      }
      unset($value->uid);
      if ($value->status == 1) {
        $value->status = 'Active';
      }
      else {
        $value->status = 'Blocked';
      }
      if ($value->access == 0) {
        $value->access = 'never';
      }
      else {
        $value->access = \Drupal::service('date.formatter')->formatTimeDiffSince($value->access);
      }
      $values = (array) $value;
      $data = array_merge($values, $custom_arr);
      fputcsv($handle, $data);
    }

    // Close the file.
    fclose($handle);
    $context['message'] = t('Running Batch for user id "@id"', ['@id' => $result]);
  }

  /**
   * Batch 'finished' callback.
   */
  public static function exportFinishedCallback($success, $results, $operations) {
    global $base_url;
    $messenger = \Drupal::messenger();
    if ($success) {
      $message = t('The batch was successful.');
      drupal_set_message($message);
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
    $filename = $base_url . '/sites/default/files/export_user/export_user.csv';
    drupal_set_message(t('Click here to download the file <a href="@link">Export User</a>', ['@link' => $filename]));
  }

}
