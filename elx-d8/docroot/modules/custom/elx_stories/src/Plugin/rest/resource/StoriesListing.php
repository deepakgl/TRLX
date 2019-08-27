<?php

namespace Drupal\elx_stories\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a stories listing resource.
 *
 * @RestResource(
 *   id = "stories_listing",
 *   label = @Translation("Stories Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/storiesListing"
 *   }
 * )
 */
class StoriesListing extends ResourceBase {

  /**
   * Fetch story listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Story listing.
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    $common_utility = new CommonUtility();
    $user_utility = new UserUtility();
    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    list($limit, $offset) = $common_utility->getPagerParam($request);
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    $roles = $user_utility->getUserRoles(\Drupal::currentUser()->id());
    // Prepare redis key.
    $key = ':storiesListing:' . $user_market . '_' . $roles[0] . '_' .
     \Drupal::currentUser()->getPreferredLangcode() . '_' . $limit . '_' .
      $offset;
    // Prepare response.
    list($view_results, $status_code) = $entity_utility->fetchApiResult($key,
    'stories_listing', 'rest_export_stories_listing', $data);

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
