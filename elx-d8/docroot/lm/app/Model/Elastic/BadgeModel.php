<?php

namespace App\Model\Elastic;

use Illuminate\Http\Response;

/**
 * Purpose of this class is to check, fetch and update badges.
 */
class BadgeModel {

  /**
   * Check whether badges master index exists.
   *
   * @return bool
   *   True pr false.
   */
  public static function checkBadgeMasterIndex($client) {
    $params['index'] = getenv("ELASTIC_ENV") . '_badge_master';
    try {
      $exists = $client->indices()->exists($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $exists;
  }

  /**
   * Get badge data from elastic.
   *
   * @param string $lang
   *   User language.
   * @param array $client
   *   Elastic client object.
   *
   * @return array
   *   Elastic badge master index.
   */
  public static function fetchBadgeMasterData($lang, $client) {
    $response = [];
    $params = [
      'index' => getenv("ELASTIC_ENV") . '_badge_master',
      'type' => 'badge_master',
      'id' => $lang,
    ];
    try {
      $data = $client->get($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }
    if ($data) {
      $response['badge_master'] = $data['_source']['badge'];
    }

    return $response;
  }

  /**
   * Set badge master data in elastic.
   *
   * @param array $badge_data
   *   Badge master information.
   * @param mixed $flag
   *   TRUE/FALSE.
   *
   * @return array
   *   All badges with user activity.
   */
  public static function setBadgeMasterData($badge_data, $flag = NULL) {
    $all_badges = [];
    $all_badges['userActivity'] = [];
    foreach ($badge_data['badge_master'] as $key => $value) {
      $badge['key'] = $key;
      $badge['tid'] = $value['tid'];
      $badge['image'] = $value['src'];
      $badge['earned_description'] = $value['earned_description'];
      $badge['unearned_description'] = $value['unearned_description'];
      $badge['title'] = $value['title'];
      $all_badges['allBadges'][] = $badge;
      if (isset($flag) && $flag == TRUE) {
        if (isset($badge_data['user_badge'][$key])) {
          if ($badge_data['user_badge'][$key] > 0) {
            $all_badges['userActivity'][] = [
              'key' => $key,
              'status' => TRUE,
            ];
          }
        }
      }
      elseif (isset($flag) && $flag == FALSE) {
        $all_badges['userActivity'][] = [
          'key' => $key,
          'status' => FALSE,
        ];
      }
    }

    return $all_badges;
  }

  /**
   * Allocate badge to user.
   *
   * @param int $nid
   *   Node id.
   * @param array $badge_info
   *   Badge information.
   * @param array $badge
   *   Badge name.
   * @param array $client
   *   Elastic object.
   *
   * @return array
   *   Badge info.
   */
  public static function allocateBadgeToUser($nid, $badge_info, $badge, $client) {
    // Fetch data from user index.
    $response = ElasticUserModel::fetchElasticUserData($badge_info['uid'], $client);
    if (!empty($response['_source']['badge'])) {
      foreach ($response['_source']['badge'] as $key => $value) {
        $response['_source']['badge'] = $value;
      }
    }
    foreach ($badge as $key => $value) {
      $response['_source']['badge'][$value] = 1;
    }
    $params['body'] = [
      'doc' => [
        'badge' => [
          $response['_source']['badge'],
        ],
      ],
      'doc_as_upsert' => TRUE,
    ];
    // Update elastic user index.
    $output = ElasticUserModel::updateElasticUserData($params, $badge_info['uid'], $client);

    return new Response([
      'nid' => $nid,
      'status' => TRUE,
      'message' => 'Successfully updated',
    ], 200);
  }

}
