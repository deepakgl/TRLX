<?php

namespace Drupal\elx_reports;

/**
 * ParticipationRateReport.
 *
 * Extends ELXExportCSV to generate ParticipationRateReport.
 */
class ParticipationRateReport extends ELXExportCSV {

  /**
   * CSV settings.
   */
  const CSV_FILE_NAME = 'participation_rate_report.csv';

  /**
   * Header name to generate csv.
   *
   * @var mixed
   */
  protected $csvHeader = [];

  /**
   * Market name.
   *
   * @var mixed
   */
  protected $market;

  /**
   * Region name.
   *
   * @var mixed
   */
  protected $region;

  /**
   * Account name.
   *
   * @var mixed
   */
  protected $account;

  /**
   * Door name.
   *
   * @var mixed
   */
  protected $door;

  /**
   * Market name for specific user.
   *
   * @var mixed
   */
  protected $usermarket;

  /**
   * Start Date.
   *
   * @var mixed
   */
  protected $startdate;

  /**
   * Participation rate report constructor.
   *
   * @param mixed $options
   *   Options to display to the end user.
   * @param mixed $startdate
   *   Start date option to display to the end user.
   * @param mixed $usermarket
   *   Market for the user.
   */
  public function __construct($options, $startdate, $usermarket = NULL) {
    $this->market = $options['market'];
    $this->region = $options['region'];
    $this->account = $options['account'];
    $this->door = $options['door'];
    $this->startdate = $startdate->getTimestamp();
    $this->usermarket = $usermarket;
    $this->setHeader();
  }

  /**
   * Display report as CSV.
   */
  public function toCsv() {
    $this->generateCsv($this->getData(), "php://output", "w");
  }

  /**
   * Get report data.
   *
   * @return array
   *   All the data to generate csv.
   */
  public function getData() {
    $query = db_select('users_field_data', 'u');
    $sub_query = db_select('users_field_data', 'u');
    if (isset($this->region) && $this->region !== 0) {
      $query->join('user__field_region_list', 'rl', 'u.uid = rl.entity_id');
      $query->condition('rl.bundle', 'user', '=');
      $query->addField('rl', 'field_region_list_value', 'region');
      $query->groupBy('rl.field_region_list_value');
      // Sub Query.
      $sub_query->join('user__field_region_list', 'rl', 'u.uid = rl.entity_id');
      $sub_query->condition('rl.bundle', 'user', '=');
      $sub_query->addField('rl', 'field_region_list_value', 'region');
      $sub_query->groupBy('rl.field_region_list_value');
    }
    if (isset($this->market) && $this->market !== 0) {
      $query->join('user__field_default_market', 'fm', 'u.uid = fm.entity_id');
      $query->join('taxonomy_term_field_data', 'td', 'td.tid = fm.field_default_market_target_id');
      $query->addField('td', 'tid');
      $query->addField('td', 'name', 'market');
      $query->condition('fm.bundle', 'user', '=');
      $query->condition('td.vid ', 'markets', '=');
      $query->groupBy('td.tid');
      $query->groupBy('td.name');
      // Sub query.
      $sub_query->join('user__field_default_market', 'fm', 'u.uid = fm.entity_id');
      $sub_query->join('taxonomy_term_field_data', 'td', 'td.tid = fm.field_default_market_target_id');
      $sub_query->addField('td', 'tid');
      $sub_query->addField('td', 'name', 'market');
      $sub_query->condition('fm.bundle', 'user', '=');
      $sub_query->condition('td.vid ', 'markets', '=');
      $sub_query->groupBy('td.tid');
      $sub_query->groupBy('td.name');
    }
    if (isset($this->account) && $this->account !== 0) {
      $query->join('user__field_account_name', 'an', 'u.uid = an.entity_id');
      $query->addField('an', 'field_account_name_value', 'account');
      $query->condition('an.bundle', 'user', '=');
      $query->groupBy('an.field_account_name_value');

      $sub_query->join('user__field_account_name', 'an', 'u.uid = an.entity_id');
      $sub_query->addField('an', 'field_account_name_value', 'account');
      $sub_query->condition('an.bundle', 'user', '=');
      $sub_query->groupBy('an.field_account_name_value');
    }
    if (isset($this->door) && $this->door !== 0) {
      $query->join('user__field_door', 'd', 'u.uid = d.entity_id');
      $query->condition('d.bundle', 'user', '=');
      $query->addField('d', 'field_door_value', 'door');
      $query->groupBy('d.field_door_value');

      $sub_query->join('user__field_door', 'd', 'u.uid = d.entity_id');
      $sub_query->condition('d.bundle', 'user', '=');
      $sub_query->addField('d', 'field_door_value', 'door');
      $sub_query->groupBy('d.field_door_value');
    }
    // User count for Active users.
    $query->addExpression('COUNT( u.uid )', 'registered_users');
    $query->condition('u.status', 1, '=');
    // User count with last access date is greater then 0.
    $sub_query->addExpression('COUNT( u.uid )', 'active_users');
    $sub_query->condition('u.status', 1, '=');
    $sub_query->condition('u.access', $this->startdate, '>');
    if (isset($this->usermarket)) {
      $query->condition('td.tid', $this->usermarket);
    }
    $join_condition = '';
    if (isset($this->region) && $this->region !== 0) {
      if (!empty($join_condition)) {
        $join_condition .= 'AND ';
      }
      $join_condition .= 'a.region = rl.field_region_list_value ';
    }
    if (isset($this->market) && $this->market !== 0) {
      if (!empty($join_condition)) {
        $join_condition .= 'AND ';
      }
      $join_condition .= 'a.tid = td.tid ';
    }
    if (isset($this->account) && $this->account !== 0) {
      if (!empty($join_condition)) {
        $join_condition .= 'AND ';
      }
      $join_condition .= 'a.account = field_account_name_value ';
    }
    if (isset($this->door) && $this->door !== 0) {
      if (!empty($join_condition)) {
        $join_condition .= 'AND ';
      }
      $join_condition .= 'a.door = field_door_value ';
    }
    $query->leftJoin($sub_query, 'a', $join_condition);
    // Get the participation rate.
    $query->addExpression('IFNULL(a.active_users, 0)', 'active_users');
    $query->addExpression('CONCAT(ROUND(IFNULL(a.active_users, 0)/COUNT( u.uid )*100, 2), \' %\')', 'participation_rate');
    $query->groupBy('a.active_users');
    $result = $query->execute();

    return $result;
  }

  /**
   * Get the csv header and columns order.
   */
  protected function getHeader() {
    return $this->csvHeader;
  }

  /**
   * Get region.
   */
  protected function getRegion() {
    return $this->region;
  }

  /**
   * Get market.
   */
  protected function getMarket() {
    return $this->market;
  }

  /**
   * Get account.
   */
  protected function getAccount() {
    return $this->account;
  }

  /**
   * Get door.
   */
  protected function getDoor() {
    return $this->door;
  }

  /**
   * Set csv header element.
   */
  protected function setHeaderElement($option) {
    $this->csvHeader[str_replace(' ', '_', strtolower($option))] = ucfirst($option);
    return $this->csvHeader;
  }

  /**
   * Set Header.
   */
  protected function setHeader() {
    if (isset($this->region) && $this->region !== 0) {
      $this->setHeaderElement($this->region);
    }
    if (isset($this->market) && $this->market !== 0) {
      $this->setHeaderElement($this->market);
    }
    if (isset($this->account) && $this->account !== 0) {
      $this->setHeaderElement($this->account);
    }
    if (isset($this->door) && $this->door !== 0) {
      $this->setHeaderElement($this->door);
    }
    $this->setHeaderElement('Registered Users');
    $this->setHeaderElement('Active Users');
    $this->setHeaderElement('Participation Rate');
  }

}
