<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a password change resource.
 *
 * @RestResource(
 *   id = "user_logout",
 *   label = @Translation("User Logout"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/userLogout",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/userLogout"
 *   }
 * )
 */
class UserLogout extends ResourceBase {

  /**
   * Responds to post requests to user logout.
   *
   * @param array $request
   *   Rest resource query parameters.
   *
   * @return array
   *   User logout.
   */
  public function post(array $request) {
    $access_token_manager =
     \Drupal::service('simple_oauth.repositories.access_token');
    if (empty($request['token'])) {
      return new JsonResponse([
        'message' => 'Please provide a Oauth Access
       Token.',
      ], 400, [], FALSE);
    }
    preg_match_all('/{(.*?)}/', base64_decode($request['token']), $token_id);
    $token_id = json_decode($token_id[0][0])->jti;
    try {
      if (!$access_token_manager->isAccessTokenRevoked($token_id)) {
        $access_token_manager->revokeAccessToken($token_id);
        $data = ['message' => 'The Token ID is revoked'];
      }
      $response = new JsonResponse($data, 200, [], FALSE);
    }
    catch (OAuthServerException $exception) {
      $response = $exception->generateHttpResponse(new Response());
    }

    return $response;
  }

}
