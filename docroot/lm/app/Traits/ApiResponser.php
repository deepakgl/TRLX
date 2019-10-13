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
  public function successResponse($data, $code = Response::HTTP_OK, $pager = [], $res = NULL) {
    $responseArr = $data;
    if (empty($res)) {
      $responseArr = ['results' => $data];
    }
    if (!empty($pager)) {
      $responseArr['pager'] = $pager;
    }
    return response()->json($responseArr, $code);
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
    return response()->json(['message' => $message], $code);
  }

}
