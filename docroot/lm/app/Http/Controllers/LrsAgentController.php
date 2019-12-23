<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Mysql\ContentModel;
use Illuminate\Http\Response;

/**
 * Purpose of building this class is to work as a bridge between Articulate and.
 *
 * Learning Locker. In between the process, this class will store user's.
 *
 * Activity in specific tables.
 */
class LrsAgentController extends Controller {

  /**
   * Create a new controller instance.
   */
  public function __construct() {

  }

  /**
   * Building url and headers.
   *
   * @param mixed $arg1
   *   Rest resource arguments.
   * @param mixed $arg2
   *   Rest resource arguments.
   * @param mixed $param
   *   Query parameters.
   * @param mixed $header
   *   Curl headers.
   *
   * @return array
   *   Url and response header.
   */
  protected function build($arg1, $arg2, $param, $header) {
    return [
      'url' => $this->buildUrl($arg1, $arg2, $param),
      'header' => $this->buildHeader($header),
    ];
  }

  /**
   * Building LRS url.
   *
   * @param mixed $arg1
   *   Dynamic rest resource first argument.
   * @param mixed $arg2
   *   Dynamic rest resource second argument.
   * @param mixed $param
   *   Rest resource query parameters.
   *
   * @return string
   *   LRS url.
   */
  protected function buildUrl($arg1, $arg2, $param) {
    $lrs_url = getenv("LRS_URL");
    $param = http_build_query($param);
    $url = $lrs_url . '/' . $arg1 . '/' . $arg2 . '?' . $param;
    return $url;
  }

  /**
   * Building headers.
   *
   * @param array $header
   *   Request headers.
   *
   * @return array
   *   Response header.
   */
  protected function buildHeader(array $header) {
    $return = [];
    foreach ($header as $key => $value) {
      if ($key == 'postman-token' || $key == 'host') {
        continue;
      }
      $return[] = "$key: $value[0]";
    }
    return $return;
  }

  /**
   * Building LRS OPTIONS API.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   * @param mixed $arg1
   *   Dynamic rest resource first argument.
   * @param mixed $arg2
   *   Dynamic rest resource second argument.
   *
   * @return array
   *   Curl response.
   */
  public function option(Request $request, $arg1, $arg2 = NULL) {
    // Building url and headers.
    $build = $this->build($arg1, $arg2, $request->all(), $request->headers->all());
    // Building Curl params.
    $response = $this->processCurl($build, 'OPTIONS');
    return $response;
  }

  /**
   * Creating LRS POST API.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource parameters.
   * @param mixed $arg1
   *   Dynamic rest resource first argument.
   * @param mixed $arg2
   *   Dynamic rest resource second argument.
   *
   * @return array
   *   Curl response.
   */
  public function post(Request $request, $arg1, $arg2 = NULL) {
    // Building url and headers.
    $build = $this->build($arg1, $arg2, $request->all(), $request->headers->all());
    // Building Curl params.
    $response = $this->processCurl($build, 'POST');
    return $response;
  }

  /**
   * Creating LRS GET API.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource parameters.
   * @param mixed $arg1
   *   Dynamic rest resource first argument.
   * @param mixed $arg2
   *   Dynamic rest resource second argument.
   *
   * @return array
   *   Curl response.
   */
  public function get(Request $request, $arg1, $arg2 = NULL) {
    // Building url and headers.
    $build = $this->build($arg1, $arg2, $request->all(), $request->headers->all());
    // Building the Curl params.
    $response = $this->processCurl($build, 'GET');
    return $response;
  }

  /**
   * Creating LRS PUT API.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   * @param mixed $arg1
   *   Dynamic rest resource first argument.
   * @param mixed $arg2
   *   Dynamic rest resource second argument.
   *
   * @return mixed
   *   No content.
   */
  public function put(Request $request, $arg1, $arg2 = NULL) {
    $decode = json_decode($request->getcontent(), TRUE);
    if (!empty($decode['verb']['display'])) {
      $displayKey = array_keys($decode['verb']['display']);
      $statement_status = $decode['verb']['display'][$displayKey[0]];
      $statement_id = $decode['id'];
      $uid = $request->input('uid');
      $nid = $request->input('nid');
      $tid = $request->input('tid');
      $lang = $request->input('lang');
      $market = $request->input('market');
      $lrs = [
        'uid' => $uid,
        'nid' => $nid,
        'tid' => $tid,
        'statement_status' => $statement_status,
        'statement_id' => $statement_id,
        'lang' => $lang,
        'market' => $market,
      ];
      if (!empty($statement_id)) {
        // Update LRS data.
        ContentModel::setLrsData($lrs);
      }
      else {
        file_put_contents('/tmp/curl_lrs_log.txt', print_r($decode, 1), FILE_APPEND);
      }
    }
    // Building url and headers.
    $build = $this->build($arg1, $arg2, $request->all(), $request->headers->all());
    $post_field = $request->getcontent();
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $build['url'],
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => $post_field,
      CURLOPT_HTTPHEADER => $build['header'],
      CURLOPT_HEADER => TRUE,
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      // Add failed record in queue for retry later.
      $get_parsed_data = $this->getParsedLrsData($request);
      ContentModel::setLrsQueueRecord($get_parsed_data, 'PUT', $err, $arg1, $arg2);
    }

    $parsed = array_map(function ($x) {
      return array_map("trim", explode(":", $x, 2));
    }, array_filter(array_map("trim", explode("\n", $response))));
    $res = Response('');
    foreach ($parsed as $value) {
      if (!empty($value[1]) && isset($value[0])) {
        $res->header($value[0], $value[1]);
      }
    }
    $res->setStatusCode(204);

    return new Response(NULL, 204);
  }

  /**
   * Process curl.
   *
   * @param array $build
   *   cURL object.
   * @param string $method
   *   Request type.
   *
   * @return array
   *   Curl response.
   */
  protected function processCurl(array $build, $method) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $build['url'],
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => $build['header'],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      // Add failed record in queue for retry later.
      ContentModel::setLrsQueueRecord($build, $method, $err);
    }
    return $response;
  }

  /**
   * Get parsed lrs data.
   */
  public function getParsedLrsData($request) {
    $data = [
      'request_all' => $request->all(),
      'header_all' => $request->headers->all(),
      'post_field' => $request->getcontent(),
    ];

    return $data;

  }

  /**
   * Creating LRS PUT API.
   *
   * @param mixed $data
   *   Data.
   * @param mixed $arg1
   *   Dynamic rest resource first argument.
   * @param mixed $arg2
   *   Dynamic rest resource second argument.
   *
   * @return mixed
   *   No content.
   */
  public function putLrsStatement($data, $arg1, $arg2 = NULL) {
    // Building url and headers.
    $build = $this->build($arg1, $arg2, $data['request_all'], $data['header_all']);
    $post_field = $data['post_field'];
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $build['url'],
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => $post_field,
      CURLOPT_HTTPHEADER => $build['header'],
      CURLOPT_HEADER => TRUE,
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      // Add failed record in queue for retry later.
      ContentModel::setLrsQueueRecord($data, 'PUT', $err, $arg1, $arg2);
    }

    return TRUE;
  }

}
