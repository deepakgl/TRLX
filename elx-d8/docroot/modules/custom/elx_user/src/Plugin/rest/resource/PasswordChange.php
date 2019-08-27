<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Provides a password change resource.
 *
 * @RestResource(
 *   id = "password_change",
 *   label = @Translation("Password Change"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/passwordChange",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/passwordChange"
 *   }
 * )
 */
class PasswordChange extends ResourceBase {

  /**
   * Responds to post requests to change the user passowrd.
   *
   * @param array $data
   *   Rest resource query parameters.
   *
   * @return array
   *   Password updated.
   */
  public function post(array $data) {
    global $base_url;
    $request_uri = $base_url . \Drupal::request()->getRequestUri();
    $code = 400;
    // Try to load by the given user id of the user.
    $users = User::load(\Drupal::currentUser()->id());
    $response = $this->userPasswordValidation($users, $data);
    if (!empty($users) && $users->id()) {
      // Handle the validations of the password.
      if (!empty($response['message'])) {
        return new JsonResponse($response, $code);
      }
      // Change the password of the user on request.
      $users->setPassword($data['newPassword']);
      $users->save();
      $response = [
        'message' => t('Password updated.'),
        'status' => TRUE,
      ];
      $code = 200;
      \Drupal::service('logger.stdout')->log(RfcLogLevel::INFO, 'Password updated.', [
        'user' => \Drupal::currentUser(),
        'request_uri' => $request_uri,
        'data' => $data['newPassword'],
      ]);
    }

    return new JsonResponse($response, $code);
  }

  /**
   * Function to validate the password entered by user.
   *
   * @param object $users
   *   The current logged in user object.
   * @param array $data
   *   The data send for changing the password.
   *
   * @return string
   *   The message for the field validation.
   */
  public function userPasswordValidation(&$users, array $data) {
    $message = '';
    $status = FALSE;

    global $base_url;
    $request_uri = $base_url . \Drupal::request()->getRequestUri();
    if (!$users->isActive()) {
      // Blocked accounts cannot request a new password.
      $response = t('@username account is blocked or has not been activated yet.', ['@username' => $users->getUsername()]);
    }
    elseif (!\Drupal::service('user.auth')->authenticate($users->getUsername(), $data['currentPassword'])) {
      // Check if the current password entered is correct or not.
      $message = 'Incorrect current Password.';
      \Drupal::service('logger.stdout')->log(RfcLogLevel::NOTICE, 'Incorrect current Password.', [
        'user' => \Drupal::currentUser(),
        'request_uri' => $request_uri,
        'data' => $data['currentPassword'],
      ]);
    }
    elseif ($data['currentPassword'] == $data['newPassword']) {
      // Checks that the new password should not be same as current password.
      $message = 'New Password should not be same as current password.';
      \Drupal::service('logger.stdout')->log(RfcLogLevel::NOTICE, 'New Password should not be same as current password.', [
        'user' => \Drupal::currentUser(),
        'request_uri' => $request_uri,
        'data' => $data['newPassword'],
      ]);
    }
    elseif (empty($data['newPassword'])) {
      // Checks that the new password should not be same as current password.
      $message = 'New Password cannot be empty.';
      \Drupal::service('logger.stdout')->log(
        RfcLogLevel::NOTICE, 'New Password cannot be empty.', [
          'user' => \Drupal::currentUser(),
          'request_uri' => $request_uri,
        ]
      );
    }
    $response = [
      'message' => $message,
      'status' => $status,
    ];

    return $response;
  }

}
