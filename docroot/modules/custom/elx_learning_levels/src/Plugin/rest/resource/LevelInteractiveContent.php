<?php

namespace Drupal\elx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\Core\Site\Settings;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_learning_levels\Utility\LevelUtility;
use Drupal\elx_utility\Utility\EntityUtility;

/**
 * Provides a module detail page resource.
 *
 * @RestResource(
 *   id = "level_interactive_content",
 *   label = @Translation("Level Interactive Content"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/levelInteractiveContent"
 *   }
 * )
 */
class LevelInteractiveContent extends ResourceBase {

  /**
   * Rest resource for module detail page.
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
    $common_utility = new CommonUtility();
    $nid = $request->query->get('nid');
    if (empty($nid)) {
      $param = ['nid'];

      return $common_utility->invalidData($param);
    }
    // Check if node id exists.
    if (empty($common_utility->isValidNid($nid))) {
      return new JsonResponse('Node id does not exist.', 422, [], FALSE);
    }
    // Prepare array of keys for alteration in response.
    $data = [
      'displayTitle' => 'decode',
      'categoryName' => 'decode',
      'pointValue' => 'int',
      'nid' => 'int',
    ];
    list($view_results, $status_code) = $entity_utility
      ->fetchApiResult(NULL, 'interactive_content_detail_page',
    'rest_export_interactive_content_detail_page', $data, $nid);
    $decode = JSON::decode($view_results, TRUE);
    if (!empty($decode)) {
      $view_results = $this->prepareRow($decode, $nid);
    }

    return new JsonResponse($view_results, $status_code, [], TRUE);
  }


  /**
   * Fetch result form module detail.
   *
   * @param mixed $decode
   *   View data.
   * @param int $nid
   *   Node id.
   *
   * @return json
   *   View result.
   */
  private function prepareRow($decode, $nid) {
    $level_utility = new LevelUtility();
    $user_utility = new UserUtility();
    $base_url = Settings::get('file_public_root_base_url');
    $uuid = $user_utility->getUserUuid(\Drupal::currentUser()->id());
    $user_roles = $user_utility->getUserRoles(\Drupal::currentUser()->id(),
    'all_roles');
    $user_email = \Drupal::currentUser()->getEmail();
    $user_name = \Drupal::currentUser()->getUsername();
    $actor = '"mbox":"' . $user_email . '","name":"' .
    $user_name . '","objectType":"' .
    implode(',', $user_roles) . '"';
    $actor = "{" . urlencode($actor) . "}";
    $statement_id = \Drupal::config('elx_utility.settings')
      ->get('lrs_statement_id');
    $learning_category = $level_utility->getLevelCategory($nid);
    $decode[0]['articulateFile'] = $base_url . $decode[0]['articulateFile']
    . '?tincan=true&endpoint=' . $base_url . '/lm/api/v1/slrsa&auth='
    . $statement_id . '&actor=' . $actor . '&registration=' .
        $uuid . '&uid='
    . \Drupal::currentUser()->id() . '&tid=' . $learning_category . '&nid=' . $nid;
    // Fetch previous and next level.
    list($previous, $next) = $level_utility
      ->fetchPreviousAndNextLevel(\Drupal::currentUser()->id(), \Drupal::currentUser()
        ->getPreferredLangcode(), $learning_category, $nid);
    // @todo API call for user points.
    $data = [
      'categoryId' => (int) $learning_category,
      'interactiveContentPrevious' => $previous,
      'interactiveContentNext' => $next,
    ];
    $decode = $decode[0] + $data;
    $view_results = JSON::encode($decode);

    return $view_results;
  }

}
