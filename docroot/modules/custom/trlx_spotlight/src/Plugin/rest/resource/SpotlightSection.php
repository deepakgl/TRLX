<?php

namespace Drupal\trlx_spotlight\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_utility\Utility\UserUtility;

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
  * Fetch Spotlight Section.
  *
  * @param \Symfony\Component\HttpFoundation\Request $request
  *   Rest resource query parameters.
  *
  * @return \Drupal\rest\ResourceResponse
  *   Spotlight Section.
  */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();
    $userUtility = new UserUtility();

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

    // Prepare array for fields that need to be replaced.
    $data = [
      'nid' => 'int',
    ];

    // Prepare view response.
    list($view_results, $status_code) = $entityUtility->fetchApiResult(
      '',
      'spotlight_section',
      'rest_export_spotlight_section',
      $data,
      ['language' => $language],
      NULL
    );

    // Check for empty / no result from views.
    if (empty($view_results)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    $user_brands = $userUtility->getUserBrandIds();
    $result = [];
    foreach ($view_results['results'] as $key => $value ) {
      switch ($value['type']) {
        case 'stories':
          $result[$key]['nid'] = $value['nid'];
          $node = $this->getNodeData($value, $language);
          $result[$key]['displayTitle'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('field_display_title')->value : '';
          $content_section = $node->get('field_content_section')->referencedEntities();
          $result[$key]['type'] = (!empty($content_section)) ? (array_shift($content_section)->get('field_content_section_key')->value) : '';
          $result[$key]['body'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('body')->value : '';
          $result[$key]['imageSmall'] = $value['imageSmall'];
          $result[$key]['imageMedium'] = $value['imageMedium'];
          $result[$key]['imageLarge'] = $value['imageLarge'];
          $result[$key]['pointValue'] = $value['pointValue'];
          break;
        case 'brand_story':
          $node = $this->getNodeData($value, $language);
          $brand = $node->get('field_brands')->referencedEntities();
          $brand = array_shift($brand);
          $brand_id = $brand->get('field_brand_key')->value;
          if (in_array($brand_id, $user_brands)) {
            $result[$key]['nid'] = $value['nid'];
            $result[$key]['displayTitle'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('field_display_title')->value : '';
            $result[$key]['type'] = '';
            $result[$key]['body'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('body')->value : '';
            $result[$key]['imageSmall'] = $value['imageSmall'];
            $result[$key]['imageMedium'] = $value['imageMedium'];
            $result[$key]['imageLarge'] = $value['imageLarge'];
            $result[$key]['pointValue'] = $value['pointValue'];
          }
          break;
        case 'tools':
          $node = $this->getNodeData($value, $language);
          $brand = $node->get('field_brands')->referencedEntities();
          $brand = array_shift($brand);
          $brand_id = $brand->get('field_brand_key')->value;
          if (in_array($brand_id, $user_brands)) {
            $result[$key]['nid'] = $value['nid'];
            $node = $this->getNodeData($value, $language);
            $result[$key]['displayTitle'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('field_display_title')->value : '';
            $result[$key]['type'] = '';
            $result[$key]['body'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('field_tool_description')->value : '';
            $result[$key]['imageSmall'] = $value['imageSmall'];
            $result[$key]['imageMedium'] = $value['imageMedium'];
            $result[$key]['imageLarge'] = $value['imageLarge'];
            $result[$key]['pointValue'] = $value['pointValue'];
          }
          break;
        case 'product_detail':
          $node = $this->getNodeData($value, $language);
          $brand = $node->get('field_brands')->referencedEntities();
          $brand = array_shift($brand);
          $brand_id = $brand->get('field_brand_key')->value;
          if (in_array($brand_id, $user_brands)) {
            $result[$key]['nid'] = $value['nid'];
            $node = $this->getNodeData($value, $language);
            $result[$key]['displayTitle'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('field_display_title')->value : '';
            $result[$key]['type'] = '';
            $result[$key]['body'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('body')->value : '';
            $result[$key]['imageSmall'] = $value['imageSmall'];
            $result[$key]['imageMedium'] = $value['imageMedium'];
            $result[$key]['imageLarge'] = $value['imageLarge'];
            $result[$key]['pointValue'] = $value['pointValue'];
          }
          break;
        case 'level_interactive_content':
          $result[$key]['nid'] = $value['nid'];
          $node = $this->getNodeData($value, $language);
          $result[$key]['displayTitle'] = $node->hasTranslation($language) ? $node->getTranslation($language)->get('field_headline')->value : '';
          $result[$key]['type'] = '';
          $intro_text = $node->get('field_interactive_content')->referencedEntities();
          if (!empty($intro_text)) {
            $interactive_content = array_shift($intro_text);
            $body = $interactive_content->hasTranslation($language) ? $interactive_content->getTranslation($language)->get('field_intro_text')->value : '';
            $result[$key]['body'] = strip_tags($body);
          } else {
            $result[$key]['body'] = '';
          }
          $result[$key]['imageSmall'] = $value['imageSmall'];
          $result[$key]['imageMedium'] = $value['imageMedium'];
          $result[$key]['imageLarge'] = $value['imageLarge'];
          $result[$key]['pointValue'] = $value['pointValue'];
          break;
      }
    }
    $response = [];
    $response['results'] = $result;
    if (empty($response['results'])) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    return $commonUtility->successResponse($response['results'], $status_code);
  }

  /**
   * Method to get node data.
   *
   * @param array $value
   *   node object array.
   * @param string $language
   *   Language code.
   *
   * @return mixed
   *   Node data.
   */
  public function getNodeData($value, $language) {
    try {
      $nid = $value['nid'];
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node->hasTranslation($language)) {
        return $node->getTranslation($language);
      }
    } catch (\Exception $e) {
      return FALSE;
    }
  }

}
