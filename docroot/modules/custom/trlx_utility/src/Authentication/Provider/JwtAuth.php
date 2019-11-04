<?php

namespace Drupal\trlx_utility\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\trlx_utility\Transcoder\JwtDecodeException;
use Drupal\trlx_utility\Transcoder\JwtTranscoder;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * JWT Auth token provider.
 */
class JwtAuth implements AuthenticationProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $auth = $request->headers->get('Authorization');
    $uri = \Drupal::request()->getRequestUri();
    $matches = [];
    if (preg_match('/\/api\//', $uri) == 1) {
      if ($auth == NULL) {
        throw new BadRequestHttpException('Authorization header is required.');
      }
      if (!$hasJWT = preg_match('/^Bearer (.*)/', $auth, $matches)) {
        throw new UnprocessableEntityHttpException('Provided token is not valid.');
      }
    }
    return preg_match('/^Bearer .+/', $auth);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    global $_userData;
    $commonUtility = new CommonUtility();
    $this->transcoder = new JwtTranscoder();
    $auth_header = $request->headers->get('Authorization');
    $matches = [];
    if (!$hasJWT = preg_match('/^Bearer (.*)/', $auth_header, $matches)) {
      throw new UnprocessableEntityHttpException('Provided token is not valid.');
    }

    $raw_jwt = $matches[1];

    try {
      $jwt = $this->transcoder->decode($raw_jwt);
      $userId = $commonUtility->getUserRealId($jwt->uid);
      $jwt->userId = $userId;
      if (isset($jwt->subRegion)) {
        $subregions = $jwt->subRegion;
        $jwt->subregion = $subregions;
      }
    }
    catch (JwtDecodeException $e) {
      throw new AccessDeniedHttpException($e->getMessage(), $e);
    }

    $_userData = $jwt;
    return FALSE;
  }

}
