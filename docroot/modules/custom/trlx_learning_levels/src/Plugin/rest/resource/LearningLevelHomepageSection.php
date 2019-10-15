<?php

namespace Drupal\trlx_learning_levels\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * Provides a learning levels homepage section.
 *
 * @RestResource(
 *   id = "learning_levels_homepage_section",
 *   label = @Translation("Learning Level Homepage Section"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/learningLevelSection"
 *   }
 * )
 */
class LearningLevelHomepageSection extends ResourceBase {

 /**
  * Fetch Learning Level Section.
  *
  * @param \Symfony\Component\HttpFoundation\Request $request
  *   Rest resource query parameters.
  *
  * @return \Drupal\rest\ResourceResponse
  *   Learning Level Section.
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
    // Checkfor valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    $nids = $this->getNids();
    $result = [];
    if (!empty($nids)) {
      foreach ($nids as $key => $nid) {
        $node = $commonUtility->getNodeData($nid->nid, $language);
        $result[$key]['id'] = $node->id();
        $result[$key]['displayTitle'] = $node->get('field_headline')->value;
        $result[$key]['subTitle'] = $node->get('field_subtitle')->value;
        $articulate_content = $node->get(field_interactive_content)->referencedEntities();
        $result[$key]['body'] = (!empty($articulate_content)) ? (array_shift($articulate_content)->get('field_intro_text')->value) : '';
        $featured_image = $node->get(field_featured_image)->referencedEntities();
        if (!empty($featured_image)) {
          $image = array_shift($featured_image)->get(field_media_image)->referencedEntities();
          $uri = (!empty($image)) ? (array_shift($image)->get(uri)->value) : '';
          $result[$key]['imageSmall'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_mobile', $uri)) : '';
          $result[$key]['imageMedium'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_tablet', $uri)) : '';
          $result[$key]['imageLarge'] = (!empty($uri)) ? ($commonUtility->loadImageStyle('level_home_page_desktop', $uri)) : '';
        } else {
          $result[$key]['imageSmall'] = '';
          $result[$key]['imageMedium'] = '';
          $result[$key]['imageLarge'] = '';
        }
        $result[$key]['pointValue'] = $node->get('field_point_value')->value;
      }
    }

    $response = [];
    $response['results'] = $result;
    if (empty($response['results'])) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($response['results'], 200);
  }

 /**
  * Method to get node data.
  *
  * @return array
  *   Node data.
  */
  public function getNids() {
    // Query to get the nid for in-progress learning level content.
    $database = \Drupal::database();
    $passed_nid = $database->select('lm_lrs_records', 't')
      ->fields('t', ['nid'])
      ->condition('statement_status', db_like("passed"), 'LIKE')
      ->distinct()
      ->execute()
      ->fetchAllKeyed(0,0);

    $query = $database->select('lm_lrs_records', 't');
    $query->fields('t',array('nid','id'));
    if (!empty($passed_nid)) {
      $query->condition('nid', $passed_nid, 'NOT IN');
    }
    $query->distinct();
    $query->orderBy("id", 'DESC');
    $query->range(0, 4);
    
    return $query->execute()->fetchAll();
  }
}
