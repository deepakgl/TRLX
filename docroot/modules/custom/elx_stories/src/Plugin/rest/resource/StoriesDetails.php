<?php

namespace Drupal\elx_stories\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\EntityUtility;
use Drupal\Component\Serialization\Json;

/**
 * Provides a stories details resource.
 *
 * @RestResource(
 *   id = "stories_details",
 *   label = @Translation("Stories Details"),
 *   uri_paths = {
 *     "canonical" = "/api/{version}/story"
 *   }
 * )
 */
class StoriesDetails extends ResourceBase {

  /**
   * Fetch story details.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   * @param string $version
   *   Version name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Story details.
   */
  public function get(Request $request, $version) {
    if ($version != 'v2' && $version != 'v3') {
      return new JsonResponse('No route found for GET /api/' . $version .
      '/story', 404, [], FALSE);
    }
    $entity_utility = new EntityUtility();
    $common_utility = new CommonUtility();
    $user_utility = new UserUtility();
    $nid = $request->query->get('nid');
    if (empty($nid)) {
      $param = ['nid'];

      return $common_utility->invalidData($param);
    }
    // Check if node id exists.
    if (empty($common_utility->isValidNid($nid))) {
      return new JsonResponse('Node id does not exist.', 422, [], FALSE);
    }
    $this->lang = \Drupal::currentUser()->getPreferredLangcode();
    // Get user market by user id.
    $user_market = $user_utility->getMarketByUserId(\Drupal::currentUser()
      ->id());
    $roles = $user_utility->getUserRoles(\Drupal::currentUser()->id());
    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'subTitle' => 'decode',
      'nid' => 'int',
      'pointValue' => 'int',
      'body' => 'string_replace',
      'relatedStoriesNid' => 'int',
      'relatedStoriesDisplayTitle' => 'decode',
      'relatedStoriesSubTitle' => 'decode',
      'relatedStoriesPointValue' => 'int',
    ];
    // Prepare redis key.
    $key = ':storiesDetail:' . $user_market . '_' . $roles[0] . '_' . $nid .
     '_' . \Drupal::currentUser()->getPreferredLangcode();
    // Prepare response.
    list($view_results, $status_code) = $entity_utility->fetchApiResult($key,
    'stories_details', 'rest_export_stories_details', $data, $nid);
    $decode = JSON::decode($view_results, TRUE);
    // Response for stories details page.
    $result['results'] = [
      'nid' => $decode[0]['nid'],
      'displayTitle' => $decode[0]['displayTitle'],
      'subTitle' => $decode[0]['subTitle'],
      'imageLarge' => $decode[0]['imageLarge'],
      'imageMedium' => $decode[0]['imageMedium'],
      'imageSmall' => $decode[0]['imageSmall'],
      'body' => $decode[0]['body'],
      'pointValue' => $decode[0]['pointValue'],
    ];
    if (($version == 'v2' || $version == 'v3')) {
      $language =
      \Drupal::languageManager()->getLanguage(\Drupal::currentUser()
      ->getPreferredLangcode());
      // Validate whether quiz is published in current story language.
      if ($version == 'v3') {
        $quiz_status = $common_utility->isNodePublished($decode[0]['quizId'],
        $language->getId());
        $result['results']['quizId'] = !empty($quiz_status) ? (int)
        $decode[0]['quizId'] : '';
      }
      $result['results']['relatedStories'] = [];
      foreach ($decode as $key => $value) {
        $access_by_roles =
         $entity_utility->getAccessByRolesByNid($value['relatedStoriesNid']);
        // Response for related stories.
        if ((!empty($value['relatedStoriesNid']) &&
         $value['relatedStoriesStatus'] != 'Disabled' &&
         $value['langcode'] == $language->getName() && in_array($user_market,
          explode(',', $value['relatedStoriesMarket'])) && in_array($roles[0],
          $access_by_roles)) || (!$roles && $value['langcode'] ==
           $language->getName() && $value['relatedStoriesStatus'] != 'Disabled'
           && in_array($user_market,
           explode(',', $value['relatedStoriesMarket'])))) {
          $result['results']['relatedStories'][$value['relatedStoriesNid']] = [
            'nid' => $value['relatedStoriesNid'],
            'displayTitle' => $value['relatedStoriesDisplayTitle'],
            'subTitle' => $value['relatedStoriesSubTitle'],
            'imageLarge' => $value['relatedStoriesImageLarge'],
            'imageMedium' => $value['relatedStoriesImageMedium'],
            'imageSmall' => $value['relatedStoriesImageSmall'],
            'pointValue' => $value['relatedStoriesPointValue'],
          ];
        }
      }
    }

    $result['results']['relatedStories'] = array_values($result['results']['relatedStories']);

    return new JsonResponse($result, $status_code, [], FALSE);
  }

}
