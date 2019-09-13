<?php

namespace App\Exceptions;

use Exception;
use App\Traits\ApiResponser;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Illuminate\Database\QueryException;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use ErrorException;
use UnexpectedValueException;

class Handler extends ExceptionHandler {

  use ApiResponser;

  /**
   * A list of the exception types that should not be reported.
   *
   * @var array
   */
  protected $dontReport = [
    AuthorizationException::class,
    HttpException::class,
    ModelNotFoundException::class,
    ValidationException::class,
  ];

  /**
   * Report or log an exception.
   *
   * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
   *
   * @param  \Exception  $exception
   * @return void
   */
  public function report(Exception $exception) {
    parent::report($exception);
  }

  /**
   * Render an exception into an HTTP response.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Exception  $exception
   * @return \Illuminate\Http\Response
   */
  public function render($request, Exception $exception) {
    if ($exception instanceof HttpException) {
      $code = $exception->getStatusCode();
      $message = Response::$statusTexts[$code];
      return $this->errorResponse($message, $code);
    }
    if ($exception instanceof ModelNotFoundException) {
      $model = strtolower(class_basename($exception->getModel()));
      return $this->errorResponse("Does not exist any instance of {$model} with the given id", Response::HTTP_NOT_FOUND);
    }
    if ($exception instanceof AuthorizationException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_FORBIDDEN);
    }
    if ($exception instanceof AuthenticationException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
    if ($exception instanceof ValidationException) {
      $required_key_exists = array_column($exception->validator->failed(), 'Required');
      $errors = $exception->validator->errors()->getMessages();
      $error_message = array_column($errors, 0);
      if (array_key_exists(0, $required_key_exists)) {
        return $this->errorResponse($error_message[0], Response::HTTP_BAD_REQUEST);
      }
      return $this->errorResponse($error_message[0], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    if ($exception instanceof TokenInvalidException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
    if ($exception instanceof ExpiredException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
    if ($exception instanceof NoNodesAvailableException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_FORBIDDEN);
    }
    if ($exception instanceof QueryException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    if ($exception instanceof BeforeValidException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
    if ($exception instanceof InvalidArgumentException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    if ($exception instanceof ErrorException) {
      return $this->errorResponse($exception->getMessage() . ' in ' . $exception->getFile() . ' at line ' . $exception->getLine(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    if ($exception instanceof UnexpectedValueException) {
      return $this->errorResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    if (env('APP_DEBUG', false)) {
      return parent::render($request, $exception);
    }

    return $this->errorResponse('Unexpected error. Try later', Response::HTTP_INTERNAL_SERVER_ERROR);
  }

}
