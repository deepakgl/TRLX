<?php

namespace Drupal\elx_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a user dashboard resource.
 *
 * @RestResource(
 *   id = "user_dashboard",
 *   label = @Translation("User Dashboard"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/userDashboard"
 *   }
 * )
 */
class UserDashboard extends ResourceBase {

  /**
   * Fetch user profile data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   User profile data.
   */
  public function get(Request $request) {
    $response = $this->prepareRow();
    return new JsonResponse($response, 200, [], TRUE);
  }

  /**
   * Fetch user profile.
   *
   * @return json
   *   View results.
   */
  protected function prepareRow() {
    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    $entity_utility = new EntityUtility();
    $uid = \Drupal::currentUser()->id();
    // Fetch user dashboard view.
    list($view_result, $status_code) = $entity_utility
      ->fetchApiResult(NULL, 'user_dashboard', 'rest_export_user_dashboard',
     NULL, $uid);
    $decode = Json::decode($view_result);
    $decode_key = key($decode);
    $policy_flag = $decode[$decode_key]['policyFlag'] === 'True' ? TRUE : FALSE;
    $decode[$decode_key]['policyFlag'] = $policy_flag;
    $ar_search = $decode[$decode_key]['arSearchAccess'] === 'True' ? TRUE :
     FALSE;
    $decode[$decode_key]['arSearchAccess'] = $ar_search;
    $decode[$decode_key]['currentLanguageId'] = \Drupal::currentUser()
      ->getPreferredLangcode();
    $language_name = \Drupal::languageManager()->getLanguage(\Drupal::currentUser()->getPreferredLangcode());
    $decode[$decode_key]['currentLanguageName'] = $language_name->getName();
    $user_store = explode(",", $decode[$decode_key]['field_door']);
    $user_markets = explode(",", $decode[$decode_key]['market']);
    $user_interest = explode(",", $decode[$decode_key]['field_user_interest']);
    $decode[$decode_key]['markets'] = $common_utility
      ->getMarketNameByLang($user_markets, TRUE);
    $decode[$decode_key]['regions'] = [];
    foreach ($user_interest as $key => $value) {
      $user_interest_id[] = $value;
    }
    $interest_status = FALSE;
    if (!empty($user_interest_id[0])) {
      $interest_status = TRUE;
    }
    $decode[$decode_key]['interestStatus'] = $interest_status;
    // Load all user interest by term id.
    $user_interest = $user_utility->fetchUserInterest($user_interest_id);
    $decode[0]['userInterest'] = $user_interest;
    // Get current user rank.
    $rank = $user_utility->currentUserRank($uid);
    $store_info = [
      'store' => $user_store[0],
      'city' => $decode[$decode_key]['city'],
      'state' => $decode[$decode_key]['state'],
      'rank' => $rank,
    ];
    unset($decode[$decode_key]['market']);
    unset($decode[$decode_key]['field_door']);
    unset($decode[$decode_key]['field_user_interest']);
    unset($decode[$decode_key]['city']);
    unset($decode[$decode_key]['state']);
    $decode[$decode_key]['createdDate'] = intval($decode[$decode_key]['createdDate']);
    // @TODO functionality is not in scope for the time being hence static place holder added for level id and status.
    $decode[$decode_key]['LevelStatus'] = 0;
    $decode[$decode_key]['levelId'] = 0;
    // Get user points.
    $points = $user_utility->getUserPoints($uid);
    $decode = $decode[$decode_key] + ['storeInfo' => $store_info] + ['userPoints' => $points];
    $view_results = JSON::encode($decode);
    if (is_object($view_results)) {
      $view_results = $view_results->getContent();
    }
    
    return $view_results;
  }

}
