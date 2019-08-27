<?php

namespace Drupal\elx_tnc\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Provides a terms and conditions resource.
 *
 * @RestResource(
 *   id = "global_terms_and_condition",
 *   label = @Translation("Global Terms And Condition"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/globalTermsAndCondition"
 *   }
 * )
 */
class GlobalTermsAndCondition extends ResourceBase {

  /**
   * Fetch term and condition.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   View result.
   */
  public function get(Request $request) {
    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    // Validate Client Id.
    $client_id = $request->query->get('clientId');
    if (empty($client_id)) {
      return new JsonResponse(
        'Required parameter clientId is missing.',
        400,
        [],
        FALSE
      );
    }
    $valid_client_id = $common_utility->isValidClientId($client_id);
    if (!$valid_client_id) {
      return new JsonResponse('Invalid clientId.', 401, [], FALSE);
    }
    $mail = $request->query->get('mail');
    if (empty($mail)) {
      return new JsonResponse(
        'Required parameter mail is missing.',
        400,
        [],
        FALSE
      );
    }
    // Check if mail is valid.
    $user = $user_utility->isValidUserMail($mail);
    if (!$user) {
      return new JsonResponse('Invalid user.', 401, [], FALSE);
    }
    $entity_utility = new EntityUtility();
    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'body' => 'string_replace',
    ];
    $user_market = $user_utility->getMarketByUserId($user['uid']);
    // Prepare redis key.
    $key = '_t_c:detail:' . $user_market . '_' . $user['langcode'];
    // Fetch view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key, 't_c', 'rest_export_t_c', $data, $user_market);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
