<?php

namespace Drupal\elx_tnc\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a terms and conditions resource.
 *
 * @RestResource(
 *   id = "terms_and_conditions",
 *   label = @Translation("Terms and Conditions"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/termsAndConditions"
 *   }
 * )
 */
class TermsAndConditions extends ResourceBase {

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
    $entity_utility = new EntityUtility();
    // Prepare array of keys for alteration in response.
    $data = [
      'title' => 'decode',
      'body' => 'string_replace',
    ];
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    // Prepare redis key.
    $key = '_t_c:detail:' . $user_market . '_' . \Drupal::currentUser()
      ->getPreferredLangcode();
    // Fetch view response.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult($key, 't_c', 'rest_export_t_c', $data, $user_market);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
