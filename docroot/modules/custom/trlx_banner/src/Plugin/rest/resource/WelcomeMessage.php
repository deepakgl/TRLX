<?php

namespace Drupal\trlx_banner\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * Provides a Welcome Message resource.
 *
 * @RestResource(
 *   id = "welcome_site_message",
 *   label = @Translation("Welcome Message"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/welcomeMessage"
 *   }
 * )
 */
class WelcomeMessage extends ResourceBase {

  /**
   * Fetch Image listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Image Listing.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
      'language',
    ];

    // Check for required parameters.
    $missingParams = [];
    foreach ($requiredParams as $param) {
      $$param = $request->query->get($param);
      if (empty($$param)) {
        $missingParams[] = $param;
      }
    }

    // Report missing required parameters.
    if (!empty($missingParams)) {
      return $commonUtility->invalidData($missingParams);
    }

    // Check for valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Check for valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    $config = \Drupal::config('trlx_banner.welcome_message.settings');
    // Return all languages for global admin role.
    $response = [];
    $message = 'message_' . $language;
    $response['results']['message'] = $config->get($message);
    if (empty($response['results']['message'])) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($response['results'], 200);
  }

}
