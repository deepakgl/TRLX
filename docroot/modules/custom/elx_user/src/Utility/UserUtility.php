<?php

namespace Drupal\elx_user\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\taxonomy\Entity\Term;

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
   * Fetch user roles.
   *
   * @param int $uid
   *   User uid.
   * @param string $key
   *   Key name.
   * @param array $user_role
   *   Role of users.
   *
   * @return array
   *   User roles.
   */
  public function getUserRoles($uid, $key = NULL, array $user_role = []) {
    $query = db_select('user__roles', 'ur')
      ->distinct()
      ->fields('ur', ['roles_target_id'])
      ->condition('ur.entity_id', $uid, '=')
      ->execute()->fetchAll();
    $roles = array_map(function ($e) {
      return is_object($e) ? $e->roles_target_id : $e['roles_target_id'];
    }, $query);
    if (empty($key)) {
      if (in_array('administrator', $roles) || in_array('el_nyo_global_education_system_admin', $roles)) {
        return FALSE;
      }
    }
    elseif (!empty($user_role) && is_array($user_role)) {
      foreach ($user_role as $key => $value) {
        if (in_array($value, $roles)) {
          return TRUE;
        }
      }

      return FALSE;
    }

    return $roles;
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

  /**
   * Set user badges.
   *
   * @param int $uid
   *   User uid.
   * @param mixed $badge
   *   Badge details.
   * @param mixed $manual_badges
   *   Manual badges.
   *
   * @return json
   *   Set user badges.
   */
  public function setUserBadges($uid, $badge, $manual_badges) {
    $badge = serialize($badge);
    $manual_badges = serialize($manual_badges);
    $elx_site_url = \Drupal::config('elx_utility.settings')
      ->get('elx_site_url');
    try {
      $response = \Drupal::httpClient()->get(
        $elx_site_url . "/lm/api/v1/allocateBadge?_format=json&uid=" . $uid .
         "&badge=" . $badge . "&manual_badge=" . $manual_badges, [
           'headers' => [
             'Accept' => 'application/json',
           ],
         ]
      );
      $data = (string) $response->getBody();

      return TRUE;
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  /**
   * Set User Inspiration Badges.
   *
   * @param int $uid
   *   User uid.
   * @param mixed $badge
   *   Badge details.
   * @param mixed $all_inspiration_badges
   *   Inspiration badges.
   *
   * @return json
   *   Set inspiration badge.
   */
  public function setUserInspirationBadges($uid, $badge, $all_inspiration_badges) {
    $badge = serialize($badge);
    $all_inspiration_badges = serialize($all_inspiration_badges);
    $elx_site_url = \Drupal::config('elx_utility.settings')
      ->get('elx_site_url');
    try {
      $response = \Drupal::httpClient()->get(
        $elx_site_url
        . "/lm/api/v1/allocateInspirationBadge?_format=json&uid="
        . $uid
        . "&badge=" . $badge
        . "&inspiration_badge=" . $all_inspiration_badges, [
          'headers' => [
            'Accept' => 'application/json',
          ],
        ]
      );
      $data = (string) $response->getBody();

      return TRUE;
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  /**
   * Fetch badge name by tid.
   *
   * @param int $tid
   *   Term id.
   *
   * @return array
   *   Badges name.
   */
  public function getBadgeName($tid) {
    $tid = array_map('trim', $tid);
    if (empty($tid)) {
      return [];
    }
    $lang = \Drupal::currentUser()->getPreferredLangcode();
    $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
    $query->distinct();
    $query->fields('ttfd', ['name']);
    $query->condition('ttfd.tid', $tid, 'IN');
    $results = $query->execute()->fetchAll();
    $badge_name = array_column($results, 'name');

    return $badge_name;
  }

  /**
   * Fetch user data by uid.
   *
   * @param string $uid
   *   User ID.
   * @param array $fields
   *   Field Array ['table_name' => [
   *         'alias' => 'Alias Name',
   *         'values' => 'Table Column Name or Field Value',
   *    ]];
   *    Above Syntax is a key-value pair.
   *
   * @return array
   *   User field values.
   */
  public function userData($uid, array $fields = []) {
    $query = \Drupal::database()->select('users_field_data', 'ufd');
    foreach ($fields as $key => $val) {
      if ($key == 'users_field_data') {
        foreach ($val['value'] as $k => $vals) {
          $query->addField('ufd', $vals);
        }
      }
      else {
        $query->addJoin('left', $key,
        $val['alias'], 'ufd.uid =' . $val['alias'] . '.entity_id');
        $query->addField($val['alias'], $val['value']);
      }
    }
    if (!is_array($uid)) {
      $uid = [$uid];
    }
    $query->condition('ufd.uid', $uid, 'IN');
    $results = $query->execute()->fetchAll();

    return $results;
  }

  /**
   * Get Market Name.
   *
   * @return array
   *   Array of Market ids.
   */
  public static function getMarketsName($uid) {
    $query = \Drupal::database()->select('user__field_default_market', 'u');
    $query->fields('u', ['field_default_market_target_id']);
    $query->condition('u.entity_id', $uid, '=');
    $result = $query->execute()->fetchAll();
    foreach ($result as $value) {
      $market_tid = $value->field_default_market_target_id;
      $term = Term::load($market_tid);
      $market_name[$market_tid] = $term->getName();
    }

    return $market_name;
  }

}
