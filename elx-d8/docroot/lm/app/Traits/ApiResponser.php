<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait ApiResponser {

  /**
   * Build success response
   * 
   * @param  string|array $data
   * @param  int $code
   * 
   * @return Illuminate\Http\JsonResponse
   */
  public function successResponse($data, $code = Response::HTTP_OK, $success = TRUE) {
    return response()->json(['success' => $success, 'result' => $data, 'code' => $code], $code);
  }

  /**
   * Build error responses
   * 
   * @param  string|array $message
   * @param  int $code
   * 
   * @return Illuminate\Http\JsonResponse
   */
  public function errorResponse($message, $code) {
    return response()->json(['success' => FALSE, 'result' => $message, 'code' => $code], $code);
  }

}
