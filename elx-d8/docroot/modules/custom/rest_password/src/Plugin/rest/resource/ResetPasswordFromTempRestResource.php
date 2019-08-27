<?php

namespace Drupal\rest_password\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to reset drupal password for user.
 *
 * @RestResource(
 *   id = "lost_password_reset",
 *   label = @Translation("Reset Lost password Via Temp password"),
 *   uri_paths = {
 *     "canonical" = "/user/lost-password-reset",
 *     "https://www.drupal.org/link-relations/create" = "/user/lost-password-reset"
 *   }
 * )
 */
class ResetPasswordFromTempRestResource extends ResourceBase {

  /**
   * Responds to post requests to set the user passowrd.
   *
   * @param array $data
   *   Rest resource query parameters.
   *
   * @return array
   *   Set Password.
   */
  public function post(array $data) {
    if (empty($data['mail']) && empty($data['temp_pass']) &&
     empty($data['new_pass'])) {
      $code = 422;
      $response = ['message' => 'name, new_pass, and temp_pass fields are required'];

      return new ResourceResponse($response, $code);
    }
    $temp_pass = $data['temp_pass'];
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
      // Check the temp password.
      $uid = $account->id();
      $service = \Drupal::service('tempstore.shared');
      $collection = 'rest_password';
      $tempstore = $service->get($collection, $uid);
      $temp_pass_from_storage = $tempstore->getIfOwner('temp_pass_' . $uid);
      if (empty($temp_pass_from_storage) || $temp_pass_from_storage !=
       $temp_pass) {
        return new ResourceResponse([
          'message' => 'OTP is not valid or expired.',
        ], 422);
      }
      if ($temp_pass_from_storage === $temp_pass) {
        // Cool.... lets change this password.
        $account->setPassword($data['new_pass']);
        $account->save();
        $code = 200;
        $response = [
          'message' => 'Your New Password has been saved please login.',
        ];
        // Delete temp password.
        $tempstore->deleteIfOwner('temp_pass_' . $uid);
      }
    }

    return new ResourceResponse($response, $code);
  }

}
