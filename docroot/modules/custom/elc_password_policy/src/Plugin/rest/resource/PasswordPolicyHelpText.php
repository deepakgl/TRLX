<?php

namespace Drupal\elc_password_policy\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Provides a password policy help text resource.
 *
 * @RestResource(
 *   id = "password_policy_help_text",
 *   label = @Translation("Password Policy Help Text"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/passwordPolicyHelpText"
 *   }
 * )
 */
class PasswordPolicyHelpText extends ResourceBase {

  /**
   * Fetch password policy help text.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   View result.
   */
  public function get(Request $request) {
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
    $result = $this->getPolicyStrings();
    $response = [
      'passwordPolicyLabel' => isset($result['passwordPolicyLabel']) ? $result['passwordPolicyLabel'] : 'Password must have the following:',
      'passwordPolicyAllowedLength' => isset($result['passwordPolicyAllowedLength']) ? $result['passwordPolicyAllowedLength'] : 'minimum of 8 characters',
      'passwordPolicyAllowedNumber' => isset($result['passwordPolicyAllowedNumber']) ? $result['passwordPolicyAllowedNumber'] : 'must include an alpha/numeric value',
      'passwordPolicyAllowedChar' => isset($result['passwordPolicyAllowedChar']) ? $result['passwordPolicyAllowedChar'] : 'must include upper/lower and a special character',
    ];
    return new JsonResponse($response, 200, [], FALSE);
  }

  /**
   * Fetch strings.
   *
   * @return array
   *   Strings.
   */
  public function getPolicyStrings() {
    $cid = 'elc_password_policy';
    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
      return $data;
    }
    else {
      $query = \Drupal::database()->select('taxonomy_term__field_translation_key', 'ubp');
      $query->join('taxonomy_term_field_data', 'tfd', 'tfd.tid =
      ubp.entity_id');
      $query->fields('tfd', ['name']);
      $query->fields('ubp', ['field_translation_key_value']);
      $query->condition('tfd.name', ['passwordPolicyAllowedChar', 'passwordPolicyAllowedLength', 'passwordPolicyAllowedNumber', 'passwordPolicyLabel'], 'IN');
      $data = $query->execute()->fetchAllKeyed();
      if (!empty($data)) {
        \Drupal::cache()->set($cid, $data);
      }

      return $data;
    }
  }

}
