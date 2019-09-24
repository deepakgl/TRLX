<?php

namespace App\Traits;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait Helper {

  /**
   * Sanitize request parameters.
   *
   * @param Request $request
   * 
   * @return Request $request
   */
  public function sanitizeRequest($request) {
    $all = $request->all(); // Get all the attributes from request.

    if (empty($all)) {
      throw new HttpException(Response::HTTP_BAD_REQUEST);
    }
    $input = array_map('trim', $all);
    $request->replace($input);
  }

  /**
   * Sanitize array request parameters.
   *
   * @param Request $request
   * 
   * @return Request $request
   */
  public function sanitizeArrayRequest($request) {
    $all = $request->all(); // Get all the attributes from request.

    if (empty($all)) {
      throw new HttpException(Response::HTTP_BAD_REQUEST);
    }
  }

}
