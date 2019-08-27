<?php

namespace Drupal\rest_password\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to email new password to user.
 *
 * @RestResource(
 *   id = "lost_password_resource",
 *   label = @Translation("Lost password"),
 *   uri_paths = {
 *     "canonical" = "/user/lost-password",
 *     "https://www.drupal.org/link-relations/create" = "/user/lost-password"
 *   }
 * )
 */
class GetPasswordRestResource extends ResourceBase {

  /**
   * Responds to post requests to reset password.
   *
   * @param array $data
   *   Rest resource query parameters.
   *
   * @return array
   *   Reset Password.
   */
  public function post(array $data) {
    if (empty($data['mail'])) {
      return new ResourceResponse(['message' => 'mail field is required.'],
       422);
    }
    $lang = NULL;
    if (!empty($data['lang'])) {
      $lang = $data['lang'];
    }
    // Try to load by email.
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(
      ['mail' => $data['mail']]);
    if (empty($users)) {
      return new ResourceResponse([
        'message' => 'Email does not exists. (User is not valid)',
      ], 404);
    }
    $account = reset($users);
    if ($account && $account->id()) {
      // Blocked accounts cannot request a new password.
      if (!$account->isActive()) {
        return new ResourceResponse(t('This account is blocked or has not been activated yet.'), 400);
      }
      // Mail a temp password.
      $mail = _rest_password_user_mail_notify('password_reset', $account,
       $lang);
      if (empty($mail)) {
        return new ResourceResponse([
          'message' => 'Sorry system can\'t send email at the moment',
        ], 400);
      }
      $response = [
        'message' => 'Further instructions have been sent to your email address.',
      ];
      $code = 200;
    }

    return new ResourceResponse($response, $code);
  }

}
