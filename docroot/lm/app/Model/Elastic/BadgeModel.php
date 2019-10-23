<?php

namespace App\Model\Elastic;

use Illuminate\Http\Response;
use App\Support\Helper;

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
   * Set stamps master data in elastic.
   *
   * @param array $badge_data
   *   Badge master information.
   * @param mixed $flag
   *   TRUE/FALSE.
   * @param string $url
   *   Request url.
   *
   * @return array
   *   AllStamps/MyStamps.
   */
  public static function setBadgeMasterData($badge_data, $flag = NULL, $url = NULL) {
    $all_badges = [];
    foreach ($badge_data['badge_master'] as $key => $value) {
      $fids[] = [
        'tid' => $value['tid'],
        'imageId' => $value['src'],
      ];
    }
    $image_uris = Helper::getUriByFid(array_column($fids, "imageId"));
    $result = Helper::buildStampsImageStyles($fids, $image_uris);
    if ($url == 'allStamps') {
      foreach ($badge_data['badge_master'] as $key => $value) {
        $image_style = Helper::buildImageResponse($result, $value['tid']);
        $badge['tid'] = (int) $value['tid'];
        $badge['title'] = $value['title'];
        $badge['imageSmall'] = $image_style['imageSmall'];
        $badge['imageMedium'] = $image_style['imageMedium'];
        $badge['imageLarge'] = $image_style['imageLarge'];
        if (isset($flag) && $flag == TRUE) {
          if (isset($badge_data['user_badge'][$key])) {
            $badge['status'] = TRUE;
          }
          else {
            $badge['status'] = FALSE;
          }
        }
        else {
          $badge['status'] = FALSE;
        }
        $all_badges['results'][] = $badge;
      }
    }

    if ($url == 'myStamps') {
      $count = [];
      $i = 1;
      $keys = array_column($badge_data['badge_master'], 'tid');
      array_multisort($keys, SORT_ASC, $badge_data['badge_master']);
      foreach ($badge_data['badge_master'] as $key => $value) {
        if (isset($flag) && $flag == TRUE && $i <= 3) {
          if (isset($badge_data['user_badge'][$key])) {
            $image_style = Helper::buildImageResponse($result, $value['tid']);
            $badge['tid'] = (int) $value['tid'];
            $badge['title'] = $value['title'];
            $badge['imageSmall'] = $image_style['imageSmall'];
            $badge['imageMedium'] = $image_style['imageMedium'];
            $badge['imageLarge'] = $image_style['imageLarge'];
            $i++;
            $all_badges['results'][] = $badge;
          }
        }
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
    else {
      $response['_source']['badge'] = [];
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
