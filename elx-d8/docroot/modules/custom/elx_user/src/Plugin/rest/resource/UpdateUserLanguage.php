<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\elx_user\Utility\UserUtility;

/**
 * Provides a update user language resource.
 *
 * @RestResource(
 *   id = "update_user_language",
 *   label = @Translation("Update User Language"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/updateUserLanguage",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/updateUserLanguage"
 *   }
 * )
 */
class UpdateUserLanguage extends ResourceBase {

  /**
   * Responds to post requests to change the user language.
   *
   * @param array $data
   *   Rest resource query parameters.
   *
   * @return array
   *   Language updated.
   */
  public function post(array $data) {
    $user_utility = new UserUtility();
    $uid = \Drupal::currentUser()->id();
    if (!$uid) {
      $user_utility->elkLog('Please provide user id.', $uid, 'NOTICE');

      return new JsonResponse('Please provide user id.', 400, [], FALSE);
    }
    $users = User::load($uid);
    $language_code = $data['languageCode'];
    if (empty($language_code)) {
      $user_utility->elkLog('Please provide language code.', $language_code,
       'NOTICE');

      return new JsonResponse('Please provide language code.', 400, [], FALSE);
    }
    $all_languages = \Drupal::languageManager()->getLanguages();
    $lang_code = [];
    foreach ($all_languages as $key => $all_language) {
      $lang_code[] = $all_language->getId();
    }
    if (!in_array($language_code, $lang_code)) {
      $message = [
        "message" => "Invalid language code.",
        "status" => FALSE,
      ];
      $user_utility->elkLog('Invalid language code.', $language_code, 'NOTICE');

      return new JsonResponse($message, 400, [], FALSE);
    }
    $users->set('langcode', $language_code);
    $users->set('preferred_langcode', $language_code);
    $users->set('preferred_admin_langcode', $language_code);
    $result = $users->save();
    $message = [
      "message" => "Language updated.",
      "status" => TRUE,
    ];
    $user_utility->elkLog('Language updated.', $language_code, 'INFO');

    return new JsonResponse($message, 200, [], FALSE);
  }

}
