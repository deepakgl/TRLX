<?php

namespace Drupal\elx_reports\Utility;

use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Class for Reports common methods.
 */
class ReportUtility {

  /**
   * Get elastic user data.
   *
   * @param array $uids
   *   User IDs.
   * @param array $fields
   *   Field Names.
   *
   * @return array
   *   Market name.
   */
  public function getElasticUserData(array $uids, $fields = NULL) {
    if (!is_array($fields)) {
      $fields = [$fields];
    }
    $common_utility = new CommonUtility();
    // Elastic connection.
    $elastic_conn = $common_utility->setElasticConnectivity();
    $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
    if (is_array($uids)) {
      $count = count($uids);
    }
    $search_param = [
      'index' => $env . '_user',
      'type' => 'user',
      'body' => [
        'ids' => $uids,
      ],
      '_source' => $fields,
    ];
    $output = [];
    // Multiple get with specific columns mention in $search_param.
    $response = $elastic_conn->mget($search_param);
    foreach ($response['docs'] as $key => $value) {
      if ($value['found'] == 1) {
        $output[] = $value['_source'];
      }
    }

    return $output;
  }

}
