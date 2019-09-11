<?php

namespace Drupal\trlx_utility\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
    global $userData;
    $this->transcoder = new JwtTranscoder();
    $auth_header = $request->headers->get('Authorization');
    $matches = array();
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

    $userData = $jwt;
    return FALSE;
  }
}

