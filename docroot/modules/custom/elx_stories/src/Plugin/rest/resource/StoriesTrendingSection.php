<?php

namespace Drupal\elx_stories\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_entityqueue_alter\Utility\EntityQueueUtility;
use Drupal\Component\Serialization\Json;

/**
 * Provides a stories trending section resource.
 *
 * @RestResource(
 *   id = "stories_trending_section",
 *   label = @Translation("Stories Trending Section"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/storiesTrendingSection"
 *   }
 * )
 */
class StoriesTrendingSection extends ResourceBase {

  /**
   * Fetch story trending section.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Story trending section.
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    $user_utility = new UserUtility();
    $queue_utility = new EntityQueueUtility();
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
    ];
    $uid = \Drupal::currentUser()->id();
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId($uid);
    $roles = $user_utility->getUserRoles($uid);
    $bypass_market_queue = $queue_utility
    ->fetchQueueOverrideFlagStatus('stories');
    $decode = [];
    if (!$bypass_market_queue) {
      // Prepare redis key for stories market wise.
      $key = ':storiesTrendingSectionMarketWise:' . $user_market . '_' .
       $roles[0] . '_' . \Drupal::currentUser()->getPreferredLangcode();
       list($view_results, $status_code) = $entity_utility->fetchApiResult($key,
       'stories_listing', 'rest_export_trending_section_market_wise', $data,
        NULL, 'stories_listing');
       $decode = JSON::decode($view_results, TRUE);
    }
    if (empty(array_filter($decode))) {
      // Prepare redis key for global stories.
      $key = ':storiesTrendingSection:' . $roles[0] .
       '_' . \Drupal::currentUser()->getPreferredLangcode();
        list($view_results, $status_code) = $entity_utility->fetchApiResult($key,
        'stories_listing', 'rest_export_trending_section', $data, NULL,
        'stories_listing');
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }

}
