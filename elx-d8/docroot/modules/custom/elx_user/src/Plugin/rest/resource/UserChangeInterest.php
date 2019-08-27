<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\elx_user\Utility\UserUtility;

/**
 * Provides a resource to update user interest.
 *
 * @RestResource(
 *   id = "user_interest",
 *   label = @Translation("Update User Interest"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/userInterest",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/userInterest"
 *   }
 * )
 */
class UserChangeInterest extends ResourceBase {

  /**
   * Responds to post requests to change the user interest.
   *
   * @param array $data
   *   Rest resource query parameters.
   *
   * @return array
   *   Interest updated.
   */
  public function post(array $data) {
    $user_utility = new UserUtility();
    global $base_url;
    $request_uri = $base_url . \Drupal::request()->getRequestUri();
    $code = 400;
    $total_val = implode(",", $data['categoryId']);
    // Try to load by the given user id of the user.
    $users = User::load(\Drupal::currentUser()->id());
    $response = $this->userInterestValidation($users, $data);
    if (!empty($users) && $users->id()) {
      // Handle the validations of the interest.
      if (!empty($response['message'])) {
        return new JsonResponse($response, $code);
      }
      // Change the user interest on request.
      $users->set('field_user_interest', $data['categoryId']);
      $users->save();
      $response = [
        'message' => t('Interest updated.'),
        'status' => TRUE,
      ];
      $code = 200;
      $user_utility->elkLog('Interest updated.', $total_val, 'INFO');
    }

    return new JsonResponse($response, $code);
  }

  /**
   * Function to validate the user interest.
   *
   * @param object $users
   *   The current logged in user object.
   * @param array $data
   *   The data send for changing the interest.
   *
   * @return string
   *   The message for the field validation.
   */
  public function userInterestValidation(&$users, array $data) {
    $user_utility = new UserUtility();
    // Get term id.
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'product_category');
    $tids = $query->execute();
    // Loading the multiple terms by tid.
    $terms = Term::loadMultiple($tids);
    foreach ($terms as $term) {
      $term_id[] = $term->id();
    }
    $total_val = implode(",", $data['categoryId']);
    foreach ($data['categoryId'] as $category) {
      if (in_array($category, $term_id)) {
        $status[$category] = "valid";
      }
      else {
        $status[$category] = "Not valid";
      }
    }
    foreach ($status as $key => $value) {
      if ($value == "Not valid") {
        $not_valid[] = $key;
      }
    }
    $interest_id = implode(",", $not_valid);
    $message = '';
    $status = FALSE;
    if (!$users->isActive()) {
      $message = "Inactive user.";
      $user_utility->elkLog($users->getUsername() . ' account is blocked or has
       not been activated yet.', $message, 'NOTICE');
    }
    elseif (!empty($not_valid)) {
      // Check if the interest id entered is correct or not.
      $message = '(' . $interest_id . ') Invalid interest Id.';
      $user_utility->elkLog($interest_id . ' Invalid interest Id.', $message, 'NOTICE');
    }
    $response = [
      'message' => $message,
      'status' => $status,
    ];

    return $response;
  }

}
