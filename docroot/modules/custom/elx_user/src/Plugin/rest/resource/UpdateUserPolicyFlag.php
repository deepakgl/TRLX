<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\elx_user\Utility\UserUtility;

/**
 * Provides a update user policy flag resource.
 *
 * @RestResource(
 *   id = "update_user_policy_flag",
 *   label = @Translation("Update User Policy flag"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/userPolicyFlag",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/userPolicyFlag"
 *   }
 * )
 */
class UpdateUserPolicyFlag extends ResourceBase {

  /**
   * Responds to post requests to update the user flag.
   *
   * @param array $data
   *   Rest resource query parameters.
   *
   * @return array
   *   Flag updated.
   */
  public function post(array $data) {
    $user_utility = new UserUtility();
    $uid = \Drupal::currentUser()->id();
    if (!$uid) {
      $user_utility->elkLog('Please provide user id.', $uid, 'NOTICE');

      return new JsonResponse('Please provide user id.', 400, [], FALSE);
    }
    $users = User::load($uid);
    $flag_status = $data['flagStatus'];
    if (($flag_status != 0 && $flag_status != 1) || !is_numeric($flag_status)) {
      $message = [
        "message" => "Invalid Policy flag value.",
        "status" => FALSE,
      ];
      $user_utility->elkLog('Invalid Policy flag value.', $flag_status, 'NOTICE');

      return new JsonResponse($message, 400, [], FALSE);
    }
    $users->set('field_t_c_flag', $flag_status);
    $result = $users->save();
    $message = [
      "message" => "Flag updated.",
      "status" => TRUE,
    ];
    $user_utility->elkLog('Policy flag updated.', $flag_status, 'INFO');

    return new JsonResponse($message, 200, [], FALSE);
  }

}
