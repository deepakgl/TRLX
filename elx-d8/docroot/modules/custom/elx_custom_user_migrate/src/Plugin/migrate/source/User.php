<?php
/**
 * @file
 * Contains \Drupal\elx_custom_user_migrate\Plugin\migrate\source.
 */

namespace Drupal\elx_custom_user_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Elasticsearch\ClientBuilder;

/**
 * Extract users from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "custom_user"
 * )
 */
class User extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $migration_mail = \Drupal::config('elx_utility.settings')->get('migration_mail');
    $migration_mail = explode(',', $migration_mail);
    $query = $this->select('users', 'u')
    ->fields('u', array_keys($this->baseFields()))
    ->condition('u.mail', $migration_mail, 'IN');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->baseFields();

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $uid = $row->getSourceProperty('uid');
    $dest_uid = $this->getMigratedUserId($uid);
    if ($dest_uid) {
      $check_access = $this->checkUserHasAccess($dest_uid);
    }
    if (!$check_access) {
      if ($row->getSourceProperty('access') == 0) {
        $row->setSourceProperty('access', $row->getSourceProperty('created'));
      }
      $roles = $this->select('users_roles', 'ur')
      ->fields('ur', ['rid'])
      ->condition('ur.uid', $row->getSourceProperty('uid'))
      ->execute()
      ->fetchCol();

      $user_picture = $this->select('users', 'u');
      $user_picture->fields('fm', ['fid', 'filename', 'uri']);
      $user_picture->addJoin('left', 'file_managed', 'fm', 'fm.fid = u.picture');
      $user_picture->condition('u.uid', $uid, '=');
      $user_picture_data = $user_picture->execute()->fetchAssoc();
      $row->setSourceProperty('picture', $user_picture_data['fid']);

      $row->setSourceProperty('roles', $roles);
      $row->setSourceProperty('default_langcode', 1);
      // Fetch user fields.
      $this->getUserFields('field_data_field_first_name', 'field_first_name_value', $uid, $row);
      $this->getUserFields('field_data_field_last_name', 'field_last_name_value', $uid, $row);
      $this->getUserFields('field_data_field_account_name', 'field_account_name_value', $uid, $row);
      $this->getUserFields('field_data_field_city', 'field_city_value', $uid, $row);
      $this->getUserFields('field_data_field_counter_manager', 'field_counter_manager_value', $uid, $row);
      $this->getUserFields('field_data_field_country', 'field_country_value', $uid, $row);
      $this->getMarketFields('og_membership', 'gid', 'user', $uid, $row);
      $this->getUserFields('field_data_field_door', 'field_door_value', $uid, $row);
      $this->getUserFields('field_data_field_education_manager_executiv', 'field_education_manager_executiv_value', $uid, $row);
      $this->getUserFields('field_data_field_employer_number', 'field_employer_number_value', $uid, $row);
      $this->getUserFields('field_data_field_employment_status', 'field_employment_status_value', $uid, $row);
      $this->getUserFields('field_data_field_general_manager_brand_mana', 'field_general_manager_brand_mana_value', $uid, $row);
      $this->getUserFields('field_data_field_hire_date', 'field_hire_date_value', $uid, $row);
      $this->getUserFields('field_data_field_last_access_date', 'field_last_access_date_value', $uid, $row);
      $this->getUserFields('field_data_field_level', 'field_level_value', $uid, $row);
      $this->getUserFields('field_data_field_market_administrator', 'field_market_administrator_value', $uid, $row);
      $this->getUserFields('field_data_field_modified_date', 'field_modified_date_value', $uid, $row);
      $this->getUserFields('field_data_field_rank', 'field_rank_value', $uid, $row);
      $this->getUserFields('field_data_field_region_list', 'field_region_list_value', $uid, $row);
      $this->getUserFields('field_data_field_field_sales_director_regio', 'field_field_sales_director_regio_value', $uid, $row);
      $this->getUserFields('field_data_field_regional_vice_president', 'field_regional_vice_president_value', $uid, $row);
      $this->getUserFields('field_data_field_account_field_executive', 'field_account_field_executive_value', $uid, $row);
      $this->getUserFields('field_data_field_state', 'field_state_value', $uid, $row);

      $user_points = $this->select('userpoints', 'up');
      $user_points->fields('up', ['uid', 'points']);
      $user_points->condition('up.uid' , $uid, '=');
      $user_points_data = $user_points->execute()->fetchAssoc();
      $params = [
        'body' => [
          'uid' => $user_points_data['uid'],
          'points' => $user_points_data['points'],
          ],
          'index' => 'user_' . $user_points_data['uid'],
          'type' => 'user_object',
          'id' => 'user_' . $user_points_data['uid'],
        ];
        $hire_date = $row->getSourceProperty('field_hire_date_value');
        if (!empty($hire_date)) {
          $new_hire_date = date("Y-m-d", strtotime($hire_date));
          $row->setSourceProperty('field_hire_date_value', $new_hire_date);
        }
        else {
          $row->setSourceProperty('field_hire_date_value', $hire_date);
        }
        $lang_code = $row->getSourceProperty('language');
        if ($lang_code == 'zhhans') {
          $row->setSourceProperty('language', 'zh-hans');
        }
        elseif ($lang_code == 'zhhant') {
          $row->setSourceProperty('language', 'zh-hant');
        }
        elseif (empty($lang_code)) {
          $row->setSourceProperty('language', 'en');
        }

        return parent::prepareRow($row);
      }
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'uid' => array(
        'type' => 'integer',
        'alias' => 'u',
      ),
    );
  }

  /**
   * Returns the user base fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function baseFields() {
    $fields = array(
      'uid' => $this->t('User ID'),
      'name' => $this->t('Username'),
      'pass' => $this->t('Password'),
      'mail' => $this->t('Email address'),
      'signature' => $this->t('Signature'),
      'signature_format' => $this->t('Signature format'),
      'created' => $this->t('Registered timestamp'),
      'access' => $this->t('Last access timestamp'),
      'login' => $this->t('Last login timestamp'),
      'status' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'language' => $this->t('Language'),
      'picture' => $this->t('Picture'),
      'init' => $this->t('Init'),
    );

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleMigrationRequired() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'user';
  }

  protected function getUserFields($table_name, $field_name, $uid, $row) {
    try {
      $result = $this->getDatabase()->query("SELECT fld.$field_name FROM $table_name fld WHERE fld.entity_id = :uid", array(':uid' => $uid));
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
    foreach ($result as $record) {
      $row->setSourceProperty($field_name, $record->$field_name);
    }

    return $row;
  }

  protected function getMarketFields($table_name, $field_name, $type, $uid, $row) {
    $query = $this->select($table_name, 'tn')
      ->fields('tn', [ $field_name ])
      ->condition('tn.etid', $uid, '=')
      ->condition('tn.entity_type', $type, '=');
    $result = $query->execute()->fetchAll();
    foreach ($result as $record) {
      $user_market[] = $record[$field_name];
    }
    $row->setSourceProperty('user_market', $user_market);

    return $row;
  }

  protected function getMigratedUserId($uid) {
    $query = \Drupal::database()->select('migrate_map_custom_user', 'mu');
    $query->fields('mu', ['destid1']);
    $query->condition('sourceid1', $uid, '=');
    $results = $query->execute()->fetchAll();

    return $results[0]->destid1;
  }

  protected function checkUserHasAccess($uid) {
    $query = \Drupal::database()->select('user__field_has_3_0_permission', 'hp');
    $query->fields('hp', ['field_has_3_0_permission_value']);
    $query->condition('entity_id', $uid, '=');
    $query->condition('field_has_3_0_permission_value', 1, '=');
    $results = $query->execute()->fetchAll();
    if (!empty($results[0])) {
      return TRUE;
    }

    return FALSE;
  }

}
?>
