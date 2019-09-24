<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\file\Entity\File;
use Drupal\elx_user\Utility\UserUtility;

/**
 * Provides a resource to update/add the user profile picture.
 *
 * @RestResource(
 *   id = "change_user_picture",
 *   label = @Translation("Change User Profile Picture"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/user/profile/picture",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/user/profile/picture"
 *   }
 * )
 */
class UserChangeProfilePicture extends ResourceBase {

  /**
   * Stores the invalid user message.
   */
  const INVALID_USER_MESSAGE = 'This User was not found or invalid';

  /**
   * Responds to post requests to change the user's profile picture.
   *
   * @param array $data
   *   Rest resource query parameters.
   *
   * @return array
   *   Profile updated.
   */
  public function post(array $data) {
    $user_utility = new UserUtility();
    $code = 400;
    // Try to load by the given User ID of the user.
    $users = User::load(\Drupal::currentUser()->id());
    $post_data_keys = ['filename', 'filesize', 'image'];
    $response = $this->profileImageValidation($post_data_keys, $data, $users);
    if (!empty($response['message'])) {
      return new JsonResponse($response, $code);
    }
    $image_data = base64_decode($data['image']);
    if ($image_data) {
      // Picture folder to store image using image field settings.
      $file_info = file_save_data($image_data, 'public://' . $data['filename'], FILE_EXISTS_REPLACE);
      $code = 201;
      // If the file exists and is saved, set the profile picture of user.
      if ($file_info) {
        $users->set('user_picture', $file_info->id());
        $users->save();
        $img = $users->get('user_picture')->getValue();
        if (!empty($img[0])) {
          $fid = $img[0]['target_id'];
          $file = File::load($fid);
          $image_path = file_create_url($file->getFileUri());
        }
        $response = [
          'image' => $image_path,
          'message' => t('User profile picture updated successfully.'),
          'status' => TRUE,
        ];
        $user_utility->elkLog('User profile picture updated successfully.',
         $image_path, 'NOTICE');
      }
      else {
        $response = [
          'image' => "",
          'message' => t('No Image Found for user profile.'),
          'status' => FALSE,
        ];
        $user_utility->elkLog('No Image Found for user profile.',
         'No image found', 'NOTICE');
      }
    }

    return new ResourceResponse($response, $code);
  }

  /**
   * Validates the image size and dimension.
   *
   * @param array $post_data_keys
   *   The key for mapping the {POST} data.
   * @param array $data
   *   The {POST} data for the picture change.
   * @param object $users
   *   The current user object.
   */
  public function profileImageValidation(array $post_data_keys, array $data, &$users) {
    $user_utility = new UserUtility();
    $message = '';
    $status = FALSE;
    $f = finfo_open();
    $image_data = base64_decode($data['image']);
    $mime_type = finfo_buffer($f, $image_data, FILEINFO_MIME_TYPE);
    $file_type = end(explode('/', $mime_type));
    $file_type_name = end(explode('.', $data['filename']));
    $file_type_support = ['jpeg', 'jpg', 'png'];
    $file_extension = strtolower($file_type);
    $file_extension_name = strtolower($file_type_name);
    if (empty($users)) {
      // Check if user is valid.
      $message = self::INVALID_USER_MESSAGE;
    }
    elseif (!$users->isActive()) {
      // Blocked accounts cannot request a profile picture change.
      $message = t('@username account is blocked or has not been activated yet.', ['@username' => $users->getUsername()]);
      $user_utility->elkLog($message, 'Block user.', 'NOTICE');
    }
    elseif (empty($data['filename']) || empty($data['image'])) {
      // Checks the the {POST} data is empty | any key-value is empty.
      $message = t('The data value cannot be empty.');
      $user_utility->elkLog($message, $message, 'NOTICE');
    }
    elseif (!in_array($file_extension, $file_type_support)) {
      // Checks the file type is supported or not.
      $message = t('Incorrect File type. Supported file type : jpeg, jpg and png.');
      $user_utility->elkLog($message, $file_extension, 'NOTICE');
    }
    elseif (!in_array($file_extension_name, $file_type_support)) {
      // Checks the file type is supported or not.
      $message = t('Incorrect File type. Supported file type : jpeg, jpg and png.');
      $user_utility->elkLog($message, $file_extension_name, 'NOTICE');
    }
    $response = [
      'message' => $message,
      'status' => $status,
    ];

    return $response;
  }

}
