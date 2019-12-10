<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponser;

/**
 * Purpose of building this class is to fetch user points.
 */
class GetEnvController extends Controller {

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
      'LRS_URL',
      'SITE_IMAGE_URL',
      'SITE_URL',
      'ELASTIC_URL',
      'ELASTIC_PORT',
      'ELASTIC_SEARCH_NOTIFICATION_INDEX',
      'ELASTIC_SEARCH_NOTIFICATION_TYPE',
      'ELASTIC_SEARCH_INDEX',
      'ELASTIC_SEARCH_TYPE',
      'ELASTIC_ENV',
      'JWT_SECRET',
      'JWT_TTL',
      'DB_HOST',
      'DB_PORT',
      'DB_DATABASE',
      'DB_USERNAME',
      'DB_PASSWORD',
      'DB_CONNECTION',
      'APP_ENV',
      'APP_KEY',
      'APP_DEBUG',
      'APP_TIMEZONE',
      'APP_URL',
    ];

    $result = [];
    foreach ($array as $value) {
      $result[$value] = env($value);
    }

    print_r($result);exit;
  }

}
