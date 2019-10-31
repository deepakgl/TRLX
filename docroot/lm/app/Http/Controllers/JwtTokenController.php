<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Traits\ApiResponser;

/**
 * Class to get token.
 */
class JwtTokenController extends Controller {

  use ApiResponser;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Get jwt token.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Json token.
   */
  public function jwtToken(Request $request) {
    // Get user id from jwt token.
    global $_userData;
    $token = [];
    if (preg_match('/Bearer\s(\S+)/', $request->header('Authorization'), $matches)) {
      $token['jwtToken'] = $matches[1];
    }
    return $this->successResponse($token, Response::HTTP_OK);
  }

}
