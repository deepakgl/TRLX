<?php

namespace Drupal\elc_profanity_list\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * Get profanity list.
 *
 * @RestResource(
 *   id = "profanity_list",
 *   label = @Translation("Profanity List"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/profanityList"
 *   }
 * )
 */
class ProfanityList extends ResourceBase {

  /**
   * Fetch profanity list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   All languages.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $_format = $request->get('_format');
    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    $response = [];
    try {
      // Load term to get all profanity list.
      $profanityList = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('profanity_list', 0, NULL, TRUE);
      if (!empty($profanityList)) {
        foreach ($profanityList as $delta => $term) {
          // Convert Object to Array.
          $term = $term->toArray();
          $response[] = $term['name'][0]['value'];
        }
      }
    }
    catch (\Exception $e) {
      $response = [];
    }

    return $commonUtility->successResponse($response, Response::HTTP_OK);
  }

}
