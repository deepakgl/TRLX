<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Support\Helper;
use App\Model\Elastic\BadgeModel;
use App\Model\Elastic\ElasticUserModel;

/**
 * Purpose of building this class is to allocate badges to user.
 */
class BadgesController extends Controller {

  /**
   * Inspiration badges.
   *
   * @var inspirationBadge
   */
  private $inspirationBadge = NULL;
  /**
   * User uid.
   *
   * @var uid
   */
  private $uid = NULL;
  /**
   * Badge name.
   *
   * @var badge
   */
  private $badge = NULL;
  /**
   * Manual badges.
   *
   * @var manualBadge
   */
  private $manualBadge = NULL;
  /**
   * All badges.
   *
   * @var allBadges
   */
  private $allBadges = NULL;
  /**
   * Elastic client.
   *
   * @var client
   */
  private $client = NULL;

  /**
   * Create a new controller instance.
   */
  public function __construct() {
    $this->uid = '';
    $this->badge = '';
    $this->inspirationBadge = '';
    $this->manualBadge = '';
    $this->allBadges = '';
    $this->client = '';
  }

  /**
   * Get all stamps by UID.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   All stamps.
   */
  public function userStamps(Request $request) {
    $uri = strtok($request->getRequestUri(), '?');
    $url = explode("/", $uri);
    $validatedData = $this->validate($request, [
      '_format' => 'required|format',
      'language' => 'required|languagecode',
    ]);
    // Get user language.
    $lang = $request->input('language');
    global $_userData;
    // User id.
    $this->uid = $_userData->userId;
    // Check whether elastic connectivity is there.
    $this->client = Helper::checkElasticClient();
    // Check whether stamps master Index exists.
    $exist_badge = BadgeModel::checkBadgeMasterIndex($this->client);
    if (!$exist_badge || !$this->client) {
      return FALSE;
    }
    $badge_data = $all_badges = [];
    // Fetch all the stamps from elastic.
    $badge_data = BadgeModel::fetchBadgeMasterData($lang, $this->client);
    // Check whether user index exists in elastic.
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->client);
    if ($exist) {
      // Fetch respective user data from elastic.
      $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->client);
      if (empty($response['_source']['badge'])) {
        // Update master stamps.
        $all_badges = BadgeModel::setBadgeMasterData($badge_data, FALSE, $url[4]);
      }
      else {
        $badge_data['user_badge'] = $response['_source']['badge'][0];
        // Update master stamps.
        $all_badges = BadgeModel::setBadgeMasterData($badge_data, TRUE, $url[4]);
      }
    }
    else {
      // Update master stamps.
      $all_badges = BadgeModel::setBadgeMasterData($badge_data, FALSE, $url[4]);
    }
    if (empty($all_badges)) {
      return new Response([], Response::HTTP_NO_CONTENT);
    }

    return new Response($all_badges, 200);
  }

  /**
   * Get Current User Badge by UID.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User badges.
   */
  public function userBadgesByUid(Request $request) {
    // User uid.
    $this->uid = $request->input('uid');
    if (!$this->uid) {
      return Helper::jsonError('Please provide user id.', 422);
    }
    // Check whether elastic connectivity is there.
    $this->client = Helper::checkElasticClient();
    // Check whether user index exists in elastic.
    $exist = ElasticUserModel::checkElasticUserIndex($this->uid, $this->client);
    if (!$this->client || !$exist) {
      return FALSE;
    }
    // Fetch respective user data from elastic.
    $response = ElasticUserModel::fetchElasticUserData($this->uid, $this->client);
    if (!empty($response['_source']['badge'])) {
      return Helper::jsonSuccess($response['_source']['badge']);
    }
  }

  /**
   * Allocate badge to user.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return bool
   *   True or false.
   */
  public function allocateBadge(Request $request) {
    // Prepare request information provided by user.
    $data = $this->prepareRequestInfo($request);
    $res = $set_badges = [];
    if (!empty($this->manualBadge)) {
      // Update user manual badges key.
      foreach ($this->manualBadge as $key => $value) {
        $res[$value] = 0;
        if (!empty($this->badge)) {
          if (in_array($value, $this->badge)) {
            $res[$value] = 1;
          }
        }
      }
    }
    // If some badges is already assigned to user.
    if (empty($this->allBadges)) {
      foreach ($res as $key => $value) {
        if ($value == 1) {
          $set_badges[$key] = $value;
        }
      }
    }
    else {
      $common_badges = array_intersect($res, $this->allBadges[0]);
      $diff_badges = array_diff_key($this->allBadges[0], $res);
      $set_badges = array_merge($diff_badges, $common_badges);
    }
    // Update user badges.
    $response = $this->updateBadgeParams($this->uid, $this->client, $set_badges);

    return TRUE;
  }

  /**
   * Allocate inspiration badge to user.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   */
  public function allocateInspirationBadge(Request $request) {
    // Prepare request information provided by user.
    $data = $this->prepareRequestInfo($request);
    $res = $set_badges = [];
    // Update inspirational badges key.
    foreach ($this->inspirationBadge as $key => $value) {
      if (!empty($this->badge)) {
        if (in_array($value, $this->badge)) {
          $res[$value] = 1;
        }
      }
    }
    // If some badges is already assigned to user.
    if (empty($this->allBadges)) {
      foreach ($res as $key => $value) {
        if ($value == 1) {
          $set_badges[$key] = $value;
        }
      }
    }
    else {
      foreach ($this->allBadges[0] as $key => $value) {
        $inspiration[$key] = $value;
        if (array_key_exists($key, $res)) {
          $inspiration[$key] = $value + 1;
        }
      }
      $set_badges = array_merge($res, $inspiration);
    }
    // Update user badges.
    $response = $this->updateBadgeParams($this->uid, $this->client, $set_badges);

    return [];
  }

  /**
   * Prepare request information provided by user.
   *
   * @param mixed $request
   *   Rest resource query parameters.
   */
  protected function prepareRequestInfo($request) {
    // Check whether elastic connectivity is there.
    $this->client = Helper::checkElasticClient();
    if (!$this->client) {
      return FALSE;
    }
    $this->uid = $request->input('uid');
    // Selected inspiration badges.
    $this->badge = $request->input('badge');
    $this->badge = unserialize($this->badge);
    // All inspiration badges.
    $this->inspirationBadge = $request->input('inspiration_badge');
    if (!empty($this->inspirationBadge)) {
      $this->inspirationBadge = unserialize($this->inspirationBadge);
    }
    // Manual badges.
    $this->manualBadge = $request->input('manual_badge');
    if (!empty($this->manualBadge)) {
      $this->manualBadge = unserialize($this->manualBadge);
    }
    // Fetch user badges by uid.
    $all_badges = $this->userBadgesByUid($request);
    $this->allBadges = !empty($all_badges) ? json_decode($all_badges->getContent(), TRUE)['data'] : '';
  }

  /**
   * Update user badges.
   *
   * @param int $uid
   *   User id.
   * @param mixed $client
   *   Elastic object.
   * @param mixed $badges
   *   User badges.
   *
   * @return bool
   *   True or false.
   */
  protected function updateBadgeParams($uid, $client, $badges = []) {
    $params['body'] = [
      'doc' => [
        'badge' => [
          $badges,
        ],
      ],
      'doc_as_upsert' => TRUE,
    ];
    $response = ElasticUserModel::updateElasticUserData($params, $uid, $client);

    return $response;
  }

  /**
   * Fetch the image of star badge if assigned to user.
   *
   * @param mixed $user_badges
   *   User assigned badges.
   * @param mixed $badge_data
   *   All badges.
   *
   * @return string
   *   Star badge image url.
   */
  public static function starBadge($user_badges, $badge_data) {
    $star_badge = ['first_certification_star',
      'second_certification_star',
      'third_certification_star',
      'fourth_certification_star',
      'final_beautiful_start_star',
    ];
    $result = [];
    $badge_image = '';
    foreach ($user_badges as $key => $user_badge) {
      if (in_array($key, $star_badge)) {
        if ($key == 'first_certification_star') {
          $result[$key] = 1;
        }
        elseif ($key == 'second_certification_star') {
          $result[$key] = 2;
        }
        elseif ($key == 'third_certification_star') {
          $result[$key] = 3;
        }
        elseif ($key == 'fourth_certification_star') {
          $result[$key] = 4;
        }
        elseif ($key == 'final_beautiful_start_star') {
          $result[$key] = 5;
        }
      }
    }
    if (!empty($result)) {
      $highest_badge = array_search(max($result), $result);
      foreach ($badge_data['badge_master'] as $key => $value) {
        if ($key == $highest_badge) {
          $badge_image = $value['src'];
        }
      }
    }

    return $badge_image;
  }

}
