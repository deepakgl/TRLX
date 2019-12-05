<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Support\Helper;
use App\Model\Elastic\ElasticUserModel;
use App\Traits\ApiResponser;

/**
 * Purpose of building this class is to fetch user points.
 */
class PointsController extends Controller {

  use ApiResponser;

  /**
   * Create a new controller instance.
   */
  public function __construct() {

  }

  /**
   * Get User Points by UID.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User total points.
   */
  public function getEnvVariables(Request $request) {

    $array = [
      'REDIS_HOST',
      'REDIS_PORT',
      'REDIS_DATABASE',
      'REDIS_PASSWORD',
      'ELASTIC_URL',
      'LRS_URL',
      'SITE_IMAGE_URL'
    ];

    $result = [];
    foreach ($array as $value) {
      $result[$value] = env($value);
    }

    print_r($result);exit;
  }

}
