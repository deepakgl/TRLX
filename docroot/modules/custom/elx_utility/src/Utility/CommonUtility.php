<?php

namespace Drupal\elx_utility\Utility;

use Symfony\Component\HttpFoundation\JsonResponse;
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

}
