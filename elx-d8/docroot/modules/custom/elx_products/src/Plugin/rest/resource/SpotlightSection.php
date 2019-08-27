<?php

namespace Drupal\elx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_entityqueue_alter\Utility\EntityQueueUtility;

/**
 * Provides a spotlight section resource.
 *
 * @RestResource(
 *   id = "spotlight_section",
 *   label = @Translation("Spotlight Section"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/spotlightSection"
 *   }
 * )
 */
class SpotlightSection extends ResourceBase {

  /**
   * Rest resource for spotlight section.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $entity_utility = new EntityUtility();
    $user_utility = new UserUtility();
    $queue_utility = new EntityQueueUtility();
    $status_code = 200;
    $uid = \Drupal::currentUser()->id();
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId($uid);
    $roles = $user_utility->getUserRoles($uid);
    $bypass_market_queue = $queue_utility
    ->fetchQueueOverrideFlagStatus('spotlight');
    $decode = [];
    if (!$bypass_market_queue) {
      // Prepare redis key for spotlight market wise.
      $key = ':spotlightSectionMarketWise:' . $user_market . '_' . $roles[0] .
       '_' .  \Drupal::currentUser()->getPreferredLangcode();
      list($response, $status_code) = $entity_utility->fetchApiResult($key,
      'spotlight_section', 'rest_export_spotlight_market_wise');
      $decode = JSON::decode($response, TRUE);
    }
    if (empty(array_filter($decode))) {
      // Prepare redis key for global spotlight.
      $key = ':spotlightSection:' . $roles[0] .
       '_' . \Drupal::currentUser()->getPreferredLangcode();
      list($response, $status_code) = $entity_utility->fetchApiResult($key,
      'spotlight_section', 'rest_export_spotlight_section');
    }
    // Prepare the data for spotlight.
    if (!empty(JSON::decode($response, TRUE))) {
      $response = $this->prepareRow($response);
    }

    return new JsonResponse($response, $status_code, [], TRUE);
  }

  /**
   * Fetch spotlight section.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($output) {
    $decode = JSON::decode($output, TRUE);
    foreach ($decode as $key => $value) {
      // Change title field response.
      if (!empty($decode[$key]['headline'])) {
        $decode[$key]['title'] = Html::decodeEntities($decode[$key]['headline']);
      }
      elseif (!empty($decode[$key]['displayTitle'])) {
        $decode[$key]['title'] = Html::decodeEntities($decode[$key]['displayTitle']);
      }
      // Change subtitle field response.
      if (!empty($decode[$key]['field_subtitle'])) {
        $decode[$key]['subTitle'] = Html::decodeEntities($value['field_subtitle']);
      }
      elseif (!empty($decode[$key]['field_sub_title'])) {
        $decode[$key]['subTitle'] = Html::decodeEntities($value['field_sub_title']);
      }
      else {
        $decode[$key]['subTitle'] = '';
      }
      unset($decode[$key]['headline']);
      unset($decode[$key]['displayTitle']);
      unset($decode[$key]['field_subtitle']);
      unset($decode[$key]['field_sub_title']);
      $decode[$key]['nid'] = (int) $value['nid'];
      $decode[$key]['pointValue'] = (int) $value['pointValue'];
      $decode[$key]['categoryId'] = (int) $value['categoryId'];
    }
    $output = JSON::encode(['results' => $decode]);
    if (is_object($output)) {
      $output = $output->getContent();
    }

    return $output;
  }

}
