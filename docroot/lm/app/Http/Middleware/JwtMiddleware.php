<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use App\Traits\ApiResponser;
use Illuminate\Http\Response;

/**
 * Purpose of this class is to verify and decode token coming in request header.
 */
class JwtMiddleware {

  use ApiResponser;

  /**
   * Handle an incoming request.
   *
   * @param Request $request
   *   Request object.
   * @param \Closure $next
   *   Next request param.
   * @param string $guard
   *   Middleware authetication gurad.
   *
   * @return mixed
   *   Token or json response.
   */
  public function handle($request, Closure $next, $guard = NULL) {
    global $_userData;

    if (!$request->headers->has('Authorization')) {
      return $this->errorResponse('Authorization header is required.', Response::HTTP_BAD_REQUEST);
    }
    if (preg_match('/Bearer\s(\S+)/', $request->header('Authorization'), $matches)) {
      $token = $matches[1];

      if (!$token) {
        // Unauthorized response if token not there.
        return $this->errorResponse('Token not provided.', Response::HTTP_UNAUTHORIZED);
      }
      try {
        $_userData = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
      }
      catch (Exception $e) {
        return $this->errorResponse('An error while decoding token.', 400);
      }
    }
    else {
      return $this->errorResponse('Provided token is invalid.', Response::HTTP_UNAUTHORIZED);
    }
    return $next($request);
  }

}
