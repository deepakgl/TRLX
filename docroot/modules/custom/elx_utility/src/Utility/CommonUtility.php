<?php

namespace Drupal\elx_utility\Utility;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\Core\Logger\RfcLogLevel;
use Elasticsearch\ClientBuilder;

/**
 * Purpose of this class is to build common object.
 */
class CommonUtility {

  /**
   * Check rest resource params.
   *
   * @param mixed $param
   *   Parameter name.
   *
   * @return json
   *   Following params required.
   */
  public function invalidData($param = []) {
    global $base_url;
    $request_uri = $base_url . \Drupal::request()->getRequestUri();
    $param = implode(',', $param);
    $logger = \Drupal::service('logger.stdout');
    $logger->log(RfcLogLevel::ERROR, 'Following params required: ' . $param, [
      'user' => \Drupal::currentUser(),
      'request_uri' => $request_uri,
      'data' => $param,
    ]);

    return new JsonResponse('Following params required: ' . $param, 400);
  }

  /**
   * Check if node id exists.
   *
   * @param int $nid
   *   Node id.
   *
   * @return bool
   *   True or false.
   */
  public function isValidNid($nid) {
    $query = \Drupal::database();
    $query = $query->select('node_field_data', 'n');
    $query->fields('n', ['nid'])
      ->condition('n.nid', $nid, '=')
      ->range(0, 1);
    $result = $query->execute()->fetchAll();
    if (empty($result)) {
      global $base_url;
      $request_uri = $base_url . \Drupal::request()->getRequestUri();
      $logger = \Drupal::service('logger.stdout');
      $logger->log(RfcLogLevel::ERROR, 'Node Id @nid does not exist in database.', [
        '@nid' => $nid,
        'user' => \Drupal::currentUser(),
        'request_uri' => $request_uri,
        'data' => $nid,
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check if term id exists.
   *
   * @param int $tid
   *   Term id.
   *
   * @return bool
   *   True or false.
   */
  public function isValidTid($tid) {
    $query = \Drupal::database();
    $query = $query->select('taxonomy_term_data', 't');
    $query->fields('t', ['tid'])
      ->condition('t.tid', $tid, '=')
      ->range(0, 1);
    $result = $query->execute()->fetchAll();
    if (empty($result)) {
      global $base_url;
      $request_uri = $base_url . \Drupal::request()->getRequestUri();
      $logger = \Drupal::service('logger.stdout');
      $logger->log(RfcLogLevel::ERROR, 'Term Id @tid does not exist in database.', [
        '@tid' => $tid,
        'user' => \Drupal::currentUser(),
        'request_uri' => $request_uri,
        'data' => $tid,
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Set the limit and pager.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   * @param int $limit_default
   *   Limit.
   * @param int $offset_default
   *   Offset.
   *
   * @return array
   *   Limit and offset.
   */
  public function getPagerParam(Request $request, $limit_default = 10, $offset_default = 0) {
    $limit = $request->query->get('limit') ? $request->query->get('limit') : $limit_default;
    $offset = $request->query->get('offset') ? $request->query->get('offset') : $offset_default;

    return [$limit, $offset];
  }

  /**
   * Fetch term by name and vid.
   *
   * @param string $name
   *   Term name.
   * @param string $vid
   *   Vocabulary name.
   *
   * @return int
   *   Term id or 0 if none.
   */
  public function getTidByName($name = NULL, $vid = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);

    return !empty($term) ? $term->id() : 0;
  }

  /**
   * Access Denied for API.
   *
   * @return json
   *   Access denied HTTP code and message.
   */
  public function accessDenied() {
    $data = ['message' => t('The access content permission is required.')];

    return new JsonResponse($data, 403, [], FALSE);
  }

  /**
   * Fetch user activities information.
   *
   * @param string $json_result
   *   Json result.
   * @param string $case
   *   Case name.
   *
   * @return json
   *   User activity.
   *   TODO fuction will be removed once LM call implements at front end side.
   */
  public function getUserActivities($json_result, $case = NULL) {
    $output['results'] = JSON::decode($json_result, TRUE);
    if ($case == 'videoListing' || $case == 'productListing' || $case == 'storiesListing' || $case == 'levelInteractiveTermsContent') {
      $output = JSON::decode($json_result, TRUE);
      if (empty($output) || empty($output['results'])) {
        return JSON::encode([]);
      }
    }
    if (isset($output['results']['results']) &&
      empty($output['results']['results'])) {

      return JSON::encode(["results" => [], "userActivity" => []]);
    }
    try {
      $nids = array_column($output['results'], 'nid');
      $nid = serialize($nids);
      $video_nid = [];
      if ($case == 'videoListingMobile') {
        foreach ($output['results'] as $key => $value) {
          $video_nid = array_values(array_column($value['results'], 'nid'));
        }
        $nid = serialize($video_nid);
      }
      $uid = \Drupal::currentUser()->id();
      $elx_site_url = \Drupal::config('elx_utility.settings')->get('elx_site_url');
      $response = \Drupal::httpClient()->get(
        $elx_site_url . "/lm/api/v1/getUserActivities?_format=json&nid=" . $nid
         . "&uid=" . $uid, [
           'headers' => [
             'Accept' => 'application/json',
           ],
         ]
      );
      $data = (string) $response->getBody();
      $decode = Json::decode($data);
      $output['userActivity'] = $decode['data']['userActivities'];
      if ($case == 'videoListingMobile') {
        return $decode['data']['userActivities'];
      }

      return JSON::encode($output);
    }
    catch (RequestException $e) {
      return JSON::encode($output);
    }
  }

  /**
   * Fetch user level activities information.
   *
   * @param string $json_result
   *   Json result.
   * @param string $case
   *   Case name.
   *
   * @return json
   *   User level activity.
   */
  public function getUserLevelActivities($json_result, $case = NULL) {
    try {
      $output = JSON::decode($json_result, TRUE);
      if (empty($output)) {
        return JSON::encode([]);
      }
      $nids = array_column($output['results'], 'nid');
      $nid = serialize($nids);
      $uid = \Drupal::currentUser()->id();
      $tid = $output['levelDetail']['userLevelActivity']['categoryId'];
      $elx_site_url = \Drupal::config('elx_utility.settings')->get('elx_site_url');
      $response = \Drupal::httpClient()->get(
        $elx_site_url . "/lm/api/v1/getUserLevelActivities?_format=json&nid=" . $nid . "&uid=" . $uid . "&tid=" . $tid, [
          'headers' => [
            'Accept' => 'application/json',
          ],
        ]
        );
      $data = (string) $response->getBody();
      $decode = Json::decode($data);
      $output['userActivity'] = $decode['data']['userActivities'];

      return JSON::encode($output);
    }
    catch (RequestException $e) {
      return JSON::encode($output);
    }
  }

  /**
   * Fetch term name by tid.
   *
   * @param int $tid
   *   Term Id.
   *
   * @return string
   *   Term name.
   */
  public function getTermName($tid) {
    $lang = \Drupal::currentUser()->getPreferredLangcode();
    $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
    $query->fields('ttfd', ['name', 'tid', 'langcode']);
    $query->condition('ttfd.tid', $tid);
    $query->condition('ttfd.langcode', ['en', $lang], 'IN');
    $results = $query->execute()->fetchAll();
    $data = [];
    foreach ($results as $key => $result) {
      if (empty($data[$result->tid]) || $data[$result->tid]['lang'] == 'en') {
        $data[$result->tid] = [
          'name' => $result->name,
          'lang' => $result->langcode,
        ];
      }
    }
    $term_name = array_column($data, 'name');

    return $term_name[0];
  }

  /**
   * Get market name by tid and lang.
   *
   * @param int $tid
   *   Term id.
   * @param int $flag
   *   Flag name.
   *
   * @return array
   *   Term name.
   */
  public function getMarketNameByLang($tid, $flag = NULL) {
    $tid = array_map('trim', $tid);
    $user_lang = \Drupal::currentUser()->getPreferredLangcode();
    // Get market name on basis of user language.
    $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
    $query->fields('ttfd', ['name', 'tid', 'langcode']);
    $query->condition('ttfd.tid', $tid, 'IN');
    $query->condition('ttfd.langcode', ['en', $user_lang], 'IN');
    $results = $query->execute()->fetchAll();
    $data = [];
    foreach ($results as $key => $result) {
      if (empty($data[$result->tid]) || $data[$result->tid]['lang'] == 'en') {
        $data[$result->tid] = [
          'name' => $result->name,
          'mid' => $result->tid,
          'lang' => $result->langcode,
        ];
      }
    }
    $market_name = array_column($data, 'name');
    if (isset($flag)) {
      $market_name = array_values($data);
    }

    return $market_name;
  }

  /**
   * Get term nodes by tid.
   *
   * @param int $tids
   *   Term id.
   *
   * @return string
   *   Return term name.
   */
  public function getTermNodes($tids) {
    $uid = \Drupal::currentUser()->id();
    $user_utility = new UserUtility();
    $roles = $user_utility->getUserRoles($uid);
    $user_langcode = \Drupal::currentUser()->getPreferredLangcode();
    // Get current user markets.
    $market = $user_utility->getMarketByUserId($uid);
    $query = \Drupal::database();
    $query = $query->select('node_field_data', 'n');
    $query->join('node__field_learning_category', 'nflc', 'n.nid = nflc.entity_id');
    $query->join('node__field_point_value', 'nfpv', 'n.nid = nfpv.entity_id');
    $query->distinct('nid');
    $query->fields('n', ['nid']);
    $query->fields('nflc', ['field_learning_category_target_id']);
    $query->fields('nfpv', ['field_point_value_value']);
    $query->condition('nflc.field_learning_category_target_id', $tids, 'IN');
    $query->condition('n.status', '1');
    $query->condition('n.langcode', $user_langcode);
    $query->condition('n.type', 'level_interactive_content');
    $query->condition('nflc.langcode', $user_langcode);
    if ($roles) {
      $query->join('node__field_markets', 'nfm', 'n.nid = nfm.entity_id');
      $query->condition('nfm.field_markets_target_id', $market);
    }
    $result = $query->execute()->fetchAll();
    foreach ($result as $key => $value) {
      $arr[$value->field_learning_category_target_id][] = [
        'nid' => $value->nid,
        'point_value' => $value->field_point_value_value,
      ];
    }

    return $arr;
  }

  /**
   * Fetch level module information.
   *
   * @param int $uid
   *   User uid.
   * @param int $tid
   *   Term id.
   * @param int $nid
   *   Node id.
   *
   * @return json
   *   Module details.
   */
  public function getLevelModuleDetail($uid, $tid, $nid) {
    $query = \Drupal::database();
    $query = $query->select('lm_lrs_records', 'records');
    $query->fields('records', ['statement_status', 'nid']);
    $query->condition('records.uid', $uid);
    if (!empty($tid)) {
      $query->condition('records.tid', $tid);
    }
    $query->condition('records.nid', $nid, 'IN');
    $results = $query->execute()->fetchAll();
    foreach ($results as $key => $result) {
      $data[$result->nid] = $result;
    }
    $incomplete_status = ['progress', NULL];
    foreach ($nid as $key => $value) {
      $status = (isset($data[$value]) && !in_array($data[$value]->statement_status, $incomplete_status)) ? (int) 1 : (int) 0;
      $response[$value] = [
        "nid" => (int) $value,
        "status" => $status,
      ];
    }
    $total_count = count($nid);
    $completed = 0;
    foreach ($response as $key => $module_detail) {
      if ($module_detail['status'] == 1) {
        $completed = $completed + 1;
      }
    }
    $percentage = ceil($completed / $total_count * 100);
    $arr[$tid] = [
      'term_id' => $tid,
      'percentage' => $percentage,
      'completedCount' => $completed,
      'totalCount' => $total_count,
    ];

    return $arr;
  }

  /**
   * Fetch level information.
   *
   * @param int $nid
   *   Node id.
   *
   * @return json
   *   Level status.
   */
  public function getLevelDetail($nid) {
    try {
      $nid = serialize($nid);
      $elx_site_url = \Drupal::config('elx_utility.settings')->get('elx_site_url');
      $response = \Drupal::httpClient()->get($elx_site_url . "/lm/api/v1
      /getIntereactiveLevelTermStatus?_format=json&nid=" . $nid, [
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]
      );
      $data = (string) $response->getBody();
      $decode = Json::decode($data);

      return $decode;
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Set Elastic Connectivity.
   *
   * @return json
   *   Elastic Client.
   */
  public function setElasticConnectivity() {
    try {
      // Create elastic connection.
      $hosts = [
        [
          'host' => \Drupal::config('elx_utility.settings')
            ->get('elastic_host'),
          'port' => \Drupal::config('elx_utility.settings')
            ->get('elastic_port'),
          'scheme' => \Drupal::config('elx_utility.settings')
            ->get('elastic_scheme'),
          'user' => \Drupal::config('elx_utility.settings')
            ->get('elastic_username'),
          'pass' => \Drupal::config('elx_utility.settings')
            ->get('elastic_password'),
        ],
      ];
      $client = ClientBuilder::create()->setHosts($hosts)->build();

      return $client;
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Fetch user level status.
   *
   * @param int $uid
   *   User uid.
   * @param string $level_status
   *   Level status.
   *
   * @return array
   *   User level status.
   */
  public function getUserLevelStatus($uid, $level_status = NULL) {
    $status = [];
    $user_lang = \Drupal::currentUser()->getPreferredLangcode();
    $user_utility = new UserUtility();
    $user_market = $user_utility->getMarketByUserId($uid);
    $connection = \Drupal::database();
    $results = [];
    try {
      $query = $connection->query(
      "SELECT lm.tid , GROUP_CONCAT(COALESCE(lrs.statement_status, 'NULL'))
      as status FROM lm_terms_node as lm LEFT JOIN lm_lrs_records
      as lrs on  lm.nid = lrs.nid AND  lm.tid = lrs.tid AND lrs.uid = {$uid} LEFT
      JOIN node_field_data as nfd on nfd.nid  = lm.nid INNER JOIN node__field_markets as nfm on nfm.entity_id = lm.nid WHERE nfd.langcode =
      '{$user_lang}' AND nfm.field_markets_target_id = {$user_market} AND nfd.status = 1 GROUP BY lm.tid");
      $results = $query->fetchAll();
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
    if (empty($results)) {
      return $status;
    }
    foreach ($results as $key => $result) {
      $status['all'][] = [$result->tid];
      $status_array = explode(",", $result->status);
      $count_null = array_count_values($status_array);
      if ($count_null['NULL'] != count($status_array)) {
        // Get the in-progress/completed levels.
        if (($count_null['NULL'] > 0) || in_array('progress', $status_array)) {
          $status['progress'][] = $result->tid;
        }
        elseif (!in_array('progress', $status_array) && !in_array(NULL, $status_array)) {
          $status['completed'][] = $result->tid;
        }
      }
    }

    if ($level_status != NULL) {
      return $status[$level_status];
    }

    return $status;
  }

  /**
   * Get the file id from media id.
   *
   * @param int $media_id
   *   Media id.
   *
   * @return array
   *   The file id.
   */
  public function getFidByMediaId($media_id) {
    $query = db_select('media__field_media_image', 'mf')
      ->fields('mf', ['field_media_image_target_id'])
      ->condition('mf.entity_id', $media_id, '=')
      ->execute()->fetchAssoc();

    return $query['field_media_image_target_id'];
  }

  /**
   * Load Image styles.
   *
   * @param string $style_name
   *   Image style name.
   * @param string $file_uri
   *   File url.
   *
   * @return array
   *   Image url.
   */
  public function loadImageStyle($style_name, $file_uri) {
    $image_style = \Drupal::entityTypeManager()->getStorage('image_style')->load($style_name);
    $result = $image_style->buildUrl($file_uri);

    return $result;
  }

  /**
   * Validate if client_id is valid.
   *
   * @param string $client_id
   *   Client id.
   *
   * @return string
   *   The consumer UUID for the OAuth Client.
   */
  public function isValidClientId($client_id) {
    if (\Drupal::moduleHandler()->moduleExists('simple_oauth') && db_table_exists('consumer')) {
      $query = db_select('consumer', 'oc')
        ->fields('oc', ['uuid'])
        ->fields('oc', ['id'])
        ->condition('uuid', $client_id);
      $consumer = $query->execute()->fetch();

      return ($consumer->uuid) ? $consumer->uuid : NULL;
    }

    return FALSE;
  }

  /**
   * Get all languages.
   *
   * @param int $flag
   *   Client id.
   *
   * @return array
   *   Market name.
   */
  public function getCompleteLanguages($flag = NULL) {
    $lang = \Drupal::languageManager()->getLanguages();
    $lang_list = [];
    foreach ($lang as $key => $val) {
      $lang_list[$key] = $val->getName();
    }

    return $lang_list;
  }

  /**
   * Check whether node is published or not.
   *
   * @param  int $nid
   *   Node id.
   * @param  string $lang
   *   Language code.
   *
   * @return boolean
   *   True or False.
   */
  public function isNodePublished($nid, $lang) {
    $query = \Drupal::database()->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'quiz', '=')
      ->condition('n.nid', $nid, '=')
      ->condition('n.langcode', $lang, '=')
      ->condition('n.status', 1, '=')
      ->range(0, 1);
    $result = $query->execute()->fetchAssoc();
    if (empty($result)) {
      return FALSE;
    }

    return TRUE;
  }

}
