<?php

namespace Drupal\elx_user\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Purpose of this class is to build user object.
 */
class UserUtility {

  /**
   * Fetch user badges.
   *
   * @param int $uid
   *   User uid.
   *
   * @return json
   *   Object of user badges.
   */
  public function getUserBadges($uid) {
    $common_utility = new CommonUtility();
    $elx_site_url = \Drupal::config('elx_utility.settings')
      ->get('elx_site_url');
    try {
      $response = \Drupal::httpClient()->get(
        $elx_site_url . "/lm/api/v1/getUserBadgesByUid?_format=json&uid=" .
         $uid, [
           'headers' => [
             'Accept' => 'application/json',
           ],
         ]
        );
    }
    catch (\Exception $e) {
      return FALSE;
    }
    $data = Json::decode($response->getBody());
    if (empty($data['data'])) {
      return [];
    }
    foreach ($data['data'][0] as $key => $value) {
      if ($value == 1) {
        $tid[$key] = $common_utility->getTidByName($key);
        $name[] = $key;
      }
    }

    return [$tid, $name, $data['data'][0]];
  }

  /**
   * Fetch user market by uid.
   *
   * @param int $uid
   *   User uid.
   * @param string $flag
   *   Flag name.
   *
   * @return array
   *   User market.
   */
  public function getMarketByUserId($uid, $flag = NULL) {
    $query = db_select('user__field_default_market', 'um');
    $query->fields('um', ['field_default_market_target_id']);
    $query->condition('um.entity_id', $uid, '=');
    // Fetch top user market.
    $result = $query->execute()->fetchAssoc()['field_default_market_target_id'];
    // Fetch all user markets.
    if ($flag == 'all') {
      $result = $query->execute()->fetchAll();
    }

    return $result;
  }

}
