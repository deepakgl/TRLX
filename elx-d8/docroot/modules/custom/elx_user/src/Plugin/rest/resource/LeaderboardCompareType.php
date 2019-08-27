<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a leaderboard compare type resource.
 *
 * @RestResource(
 *   id = "leaderboard_compare_type",
 *   label = @Translation("Leaderboard Compare Type"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/LeaderboardCompareType"
 *   }
 * )
 */
class LeaderboardCompareType extends ResourceBase {

  /**
   * Fetch compare type.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Compare type.
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    // Fetch result from user dashboard view.
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult(NULL, 'leaderboard_comparison',
    'rest_export_leaderboard_comparison');

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
