<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Log;
use App\Support\Helper;

class Handler extends ExceptionHandler
{
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
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $HttpException =  'Symfony\Component\HttpKernel\Exception\HttpExceptionInterface';

        if ($e instanceof $HttpException) {
            $error_code = $e->getStatusCode();
            if($e->getMessage() && $e->getMessage() != "") {
                $error_message = $e->getMessage();
            }
            switch ($error_code) {
                case '401':
                    $error['message'] = isset($error_message) ? $error_message : UNAUTHORIZED_MESSAGE;
                    $error['error_message'] = UNAUTHORIZED;
                    return Helper::jsonError($error, '401');
                    break;
                case '404':
                    $error['message'] = isset($error_message) ? $error_message : NOT_FOUND_MESSAGE;
                    $error['error_message'] = NOT_FOUND;
                    return Helper::jsonError($error, '404');
                    break;
                case '405':
                    $error['message'] = isset($error_message) ? $error_message : METHODNOTALLOWED_MESSAGE;
                    $error['error_message'] = METHODNOTALLOWED;
                    return Helper::jsonError($error, '405');
                    break;
                case '500':
                    $error['message'] = isset($error_message) ? $error_message : EXCEPTION_MESSAGE;
                    $error['error_message'] = EXCEPTION;
                    return Helper::jsonError($error,'500');
                    break;

                default:
                    return parent::render($request, $e);
                break;
            }
        }

        return parent::render($request, $e);
    }
}
