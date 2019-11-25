<?php

namespace Drupal\trlx_utility\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
    if ((preg_match('/\/languageList/', $uri) == 1) || (preg_match('/\/imagestylegenerate/', $uri) == 1)) {
      if (isset($auth)) {
        if (preg_match('/\/api\//', $uri) == 1) {
          if ($auth == NULL) {
            throw new BadRequestHttpException('Authorization header is required.');
          }
          if (!$hasJWT = preg_match('/^Bearer (.*)/', $auth, $matches)) {
            throw new UnauthorizedHttpException('', 'Provided token is not valid.');
          }
        }
      }
    }
    else {
      if (preg_match('/\/api\//', $uri) == 1) {
        if ($auth == NULL) {
          throw new BadRequestHttpException('Authorization header is required.');
        }
        if (!$hasJWT = preg_match('/^Bearer (.*)/', $auth, $matches)) {
          throw new UnauthorizedHttpException('', 'Provided token is not valid.');
        }
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
      throw new AccessDeniedHttpException('Provided token is not valid.');
    }

    $raw_jwt = $matches[1];

    try {
      $jwt = $this->transcoder->decode($raw_jwt);
      $userId = $commonUtility->getUserRealId($jwt->uid);
      // Check user access.
      if ($userId == 0 || $jwt->status == 0) {
        throw new AccessDeniedHttpException('Unauthorized or inactive user.');
      }
      $jwt->userId = $userId;
      if (isset($jwt->subRegion)) {
        $subregions = $jwt->subRegion;
        $jwt->subregion = $subregions;
      }
    }
    catch (JwtDecodeException $e) {
      throw new UnauthorizedHttpException('', 'An error while decoding token.');
    }

    $_userData = $jwt;
    return FALSE;
  }

}
