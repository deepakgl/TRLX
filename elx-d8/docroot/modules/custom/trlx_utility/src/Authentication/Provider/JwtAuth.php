<?php

namespace Drupal\trlx_utility\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\trlx_utility\Transcoder\JwtDecodeException;
use Drupal\trlx_utility\Transcoder\JwtTranscoder;

/**
 * JWT Auth token provider.
 */
class JwtAuth implements AuthenticationProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $auth = $request->headers->get('Authorization');
    return preg_match('/^Bearer .+/', $auth);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    global $_userData;
    $this->transcoder = new JwtTranscoder();
    $auth_header = $request->headers->get('Authorization');
    $matches = [];
    if (!$hasJWT = preg_match('/^Bearer (.*)/', $auth_header, $matches)) {
      return FALSE;
    }

    $raw_jwt = $matches[1];

    try {
      $jwt = $this->transcoder->decode($raw_jwt);
    }
    catch (JwtDecodeException $e) {
      throw new AccessDeniedHttpException($e->getMessage(), $e);
    }

    $_userData = $jwt;
    return FALSE;
  }

}
