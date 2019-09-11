<?php

namespace Drupal\trlx_utility\Transcoder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Firebase\JWT\JWT;

/**
 * Class JwtTranscoder.
 *
 * @package Drupal\jwt
 */
class JwtTranscoder {

  /**
   * The firebase/php-jwt transcoder.
   *
   * @var \Firebase\JWT\JWT
   */
  protected $transcoder;

  /**
   * Constructs a new JwtTranscoder.
   *
   * @param \Firebase\JWT\JWT $php_jwt
   *   The JWT library object.
   */
  public function __construct() {
    $this->transcoder = new JWT();
  }

  /**
   * {@inheritdoc}
   */
  public function decode($jwt) {

    //@TODO Create a config to set key and $algorithms
    $key = '43B0CEF99265F9E34C10EA9D3501926D27B39F57C6D674561D8BA236E7A819FB';
    $algorithms = ['HS256'];
    try {
      $token = $this->transcoder->decode($jwt, $key, $algorithms);
    }
    catch (\Exception $e) {
      throw JwtDecodeException::newFromException($e);
    }
    return $token;
  }

}
