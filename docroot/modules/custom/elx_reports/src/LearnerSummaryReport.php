<?php

namespace Drupal\elx_reports;

use Drupal\Core\Database\Database;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_reports\Utility\ReportUtility;

/**
 * Class for Learner Summary Report.
 */
class LearnerSummaryReport {

  /**
   * Batch operation. This function called for fetching users details.
   */
  public static function getLearnerSummary(array $result,
  $start,
  $file_path,
  $lang,
  array &$context) {
    usleep(20000);
    $context['sandbox']['file'] = $file_path;
    $header = [
      'fname' => 'First Name',
      'lname' => 'Last Name',
      'education_manager_executive' => 'Education Manager/ Executive',
      'sales_education_executive' => 'Sales/ Sales Education Executive',
      'region' => 'Region',
      'market' => 'Market',
      'account' => 'Account',
      'roles' => 'Roles',
      'door' => 'Door',
      'email' => 'Email',
      'lang' => 'Language',
      'total_points' => 'Total Points',
      'total_levels' => 'Total Levels',
    ];
    // Fetching Available levels(Name, Tid and Total Modules) wrt language.
    $level = LearnerSummaryReport::getAvailableLevels($lang);
    $level_headers = array_unique(array_column($level, 'name'));
    $header2 = ['completed_levels' => 'Completed Levels'];
    $merge_header = array_merge($level_headers, $header2);
    // Merge all headers.
    $header = array_merge($header, $merge_header);
    // If batch is started write headers first in csv else append.
    if ($start == 0) {
      ftruncate($context['sandbox']['file']);
      $handler = fopen($context['sandbox']['file'], 'w');
      fputcsv($handler, $header, ';');
    }
    else {
      $handler = fopen($context['sandbox']['file'], 'a');
    }
    // Elastic Object.
    $elastic_obj = new ReportUtility();
    $elastic_uids = [];
    // Arrays of uids to fetch data from elastic with proper index.
    foreach ($result as $key => $value) {
      $elastic_uids[] = 'user_' . $value->uid;
    }
    // Fields you want from elastic data.
    $fields = ['email', 'total_points', 'store', 'account'];
    $get_elastic_data = $elastic_obj->getElasticUserData($elastic_uids, $fields);
    $uids = array_column($result, 'uid');
    $market = $result[0]->name;
    $user_data = [];
    $level_tid = array_column($level, 'tid');
    $count_levels = count(array_unique($level_tid));
    $user_info = new UserUtility();
    // Table name with column names.
    $fetch_values = [
      'users_field_data' => [
        'value' => [
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
      'user__field_education_manager_executiv' => [
        'alias' => 'edum',
        'value' => 'field_education_manager_executiv_value',
      ],
      'user__field_account_field_executive' => [
        'alias' => 'edue',
        'value' => 'field_account_field_executive_value',
      ],
      'user__field_region_list' => [
        'alias' => 'rl',
        'value' => 'field_region_list_value',
      ],
    ];
    // Fetch User Data with all above given fields.
    $get_user_data = $user_info->userData($uids, $fetch_values);
    $count_completion = LearnerSummaryReport::getCompletedLevels($level, $uids);
    $i = 0;
    // Iterate User Data to write in a CSV.
    foreach ($get_user_data as $key => $data) {
      $uid = $data->uid;
      $user_data = (array) $data;
      $user_data['market'] = $market;
      $roles = $user_info->getUserRoles($uid, "All");
      $user_data['account'] = !empty($get_elastic_data[$key]['account'])
      ? $get_elastic_data[$key]['account'][0] : '';
      $user_data['roles'] = is_array($roles) ? implode(', ', $roles) : $roles;
      $user_data['store'] = implode(', ', $get_elastic_data[$key]['store']);
      $user_data['email'] = $get_elastic_data[$key]['email'];
      $user_data['lang'] = $lang;
      $user_data['total_points'] =
      $get_elastic_data[$key]['total_points'];
      $user_data['total_levels'] = $count_levels;
      // Iterate number of levels fetched from available levels.
      $comp = 0;
      foreach ($level_headers as $k => $levels_data) {
        if ($count_completion[$uid][$levels_data] == '100.00%') {
          $comp++;
        }
        $user_data[$levels_data] = $count_completion[$uid][$levels_data];
      }
      $user_data['completed_levels'] = $comp;
      unset($user_data['uid']);
      $i++;
      // Write in a file.
      fputcsv($handler, $user_data, ';');
    }
    // Close file.
    fclose($handler);
    $context['results'] = $user_data;
    $context['message'] = t('Running Batch @id', ['@id' => $uid]);
  }

  /**
   * Function to get availables levels.
   */
  public static function getAvailableLevels($lang) {

    $db = Database::getConnection();
    // Query to get Category name.
    $levels = db_select('taxonomy_term_field_data', 'tfd')
      ->fields('tfd', ['name', 'tid'])
      ->condition('tfd.vid', 'learning_category', '=')
      ->condition('tfd.langcode', $lang, '=')
      ->condition('tfd.status', 1, '=')
      ->execute()->fetchAll();

    $get_levels = $get_modules = [];
    try {
      // Query to get count nid, tid for levels.
      $get_modules = $db->query("SELECT count(nid) as nid, tid
      FROM {lm_terms_node} as records GROUP BY tid")->fetchAll();
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
    $levels_arr = array_intersect(array_column($get_modules, 'tid'),
    array_column($levels, 'tid'));
    // Iterate category data inside levels data to put in a array.
    foreach ($get_modules as $vals) {
      foreach ($levels as $v) {
        if ($v->tid == $vals->tid) {
          $get_levels['total'] = $vals->nid;
          $get_levels['tid'] = $vals->tid;
          $get_levels['name'] = $v->name;
        }
      }
      if (!empty($get_levels)) {
        $level[] = $get_levels;
      }
    }

    return $level;
  }

  /**
   * Function to get levels status.
   */
  public static function getCompletedLevels(array $tid,
  $uid) {
    // Implode tid and uid for IN clause query usage.
    $range_tid = "(" . implode(',', array_unique(array_column($tid, 'tid'))) . ")";
    $range_uid = "(" . implode(',', $uid) . ")";
    $level_status = [];
    $db = Database::getConnection();
    $count = 0;
    if (!empty($uid)) {
      try {
        // Fetch all levels module which user completed.
        $get_records = $db->query("SELECT count(nid)
          as completed,uid,tid FROM {lm_lrs_records}
          WHERE uid in $range_uid AND tid in $range_tid
          GROUP BY uid,tid")->fetchAll();
      }
      catch (\Exception $e) {
        return $e->getMessage();
      }
      $match_uid = array_column($get_records, 'uid');
      $match_tid = array_column($get_records, 'tid');
      // Iterate the records from above query for altering into percentage.
      foreach ($get_records as $records) {
        if (in_array($records->uid, $uid) &&
         in_array($records->tid, array_column($tid, 'tid'))) {
          foreach ($tid as $values) {
            if ($records->tid == $values['tid']) {
              $main_data[$records->uid][$values['name']] =
              number_format(($records->completed / $values['total']) * 100, 2) . '%';
            }
            else {
              $main_data[$records->uid][$values['name']] = '0%';
            }
          }
        }
      }
      // Iterate the users wrt to tid which didn't fall into above loop and set
      // it to 0.
      foreach ($uid as $data) {
        if (!in_array($data, array_keys($main_data))) {
          foreach ($tid as $values) {
            $main_data[$data][$values['name']] = '0%';
          }
        }
      }

      return $main_data;
    }
  }

  /**
   * Batch finished callback.
   */
  public static function learnerSummaryCallback($success, $results) {
    $messenger = \Drupal::messenger();
    $prefix = $results['market'] . '_' . $results['lang'];
    if ($success) {
      // File name.
      $file_name = $prefix . 'learner_summary_report' . date('ymd') . '.csv';
      // File path.
      $filename = $base_url . '/sites/default/files/learners_export/' .
      $file_name;
      // Message with a url.
      $messenger->addMessage(
        t('Click <a href="@link">HERE</a> to download the CSV',
         ['@link' => $filename])
       );
    }
    else {
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments :
           @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
