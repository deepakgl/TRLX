<?php

namespace Drupal\trlx_spotlight\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\image\Entity\ImageStyle;

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
   * GET resource for Spotlight Section.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();

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

    $result = [];
    foreach ($view_results['results'] as $key => $value ) {

      switch ($value['type']) {
        case 'product_detail':
          $node = $this->getNodeData($value, $language);
          $result[$key]['nid'] = $node->id();
          $result[$key]['displayTitle'] = $node->get('field_display_title')->value;
          $result[$key]['type'] = '';
          $result[$key]['body'] = strip_tags($node->get('body')->value);
          $thumbnail = $node->get(field_field_product_image)->referencedEntities();
          if (!empty($thumbnail)) {
            $image = array_shift($thumbnail)->get(field_media_image)->referencedEntities();
            $uri = (!empty($image)) ? (array_shift($image)->get(uri)->value) : '';
            if (!empty($uri)) {
              $result[$key]['imageSmall'] = $this->getImageUri($uri,'spotlight_mobile_375_x_270');
              $result[$key]['imageMedium'] = $this->getImageUri($uri,'spotlight_tablet_770_x_355_');
              $result[$key]['imageLarge'] = $this->getImageUri($uri,'spotlight_desktop_1194_357');
            }
          }
          $result[$key]['pointValue'] = $node->get('field_point_value')->value;
          break;

        case 'brand_story':
          $node = $this->getNodeData($value, $language);
          $result[$key]['nid'] = $node->id();
          $result[$key]['displayTitle'] = $node->get('field_display_title')->value;
          $result[$key]['type'] = '';
          $result[$key]['body'] = strip_tags($node->get('body')->value);
          $thumbnail = $node->get(field_featured_image)->referencedEntities();
          if (!empty($thumbnail)) {
            $image = array_shift($thumbnail)->get(field_media_image)->referencedEntities();
            $uri = (!empty($image)) ? (array_shift($image)->get(uri)->value) : '';
            if (!empty($uri)) {
              $result[$key]['imageSmall'] = $this->getImageUri($uri,'spotlight_mobile_375_x_270');
              $result[$key]['imageMedium'] = $this->getImageUri($uri,'spotlight_tablet_770_x_355_');
              $result[$key]['imageLarge'] = $this->getImageUri($uri,'spotlight_desktop_1194_357');
            }
          }
          $result[$key]['pointValue'] = $node->get('field_point_value')->value;
          break;

        case 'level_interactive_content':
          $node = $this->getNodeData($value, $language);
          $result[$key]['nid'] = $node->id();
          $result[$key]['displayTitle'] = $node->get('field_headline')->value;
          $result[$key]['type'] = '';
          $intro_text = $node->get(field_interactive_content)->referencedEntities();
          $body = (!empty($intro_text)) ? (array_shift($intro_text)->get('field_intro_text')->value) : '';
          $result[$key]['body'] = strip_tags($body);
          $thumbnail = $node->get(field_hero_image)->referencedEntities();
          if (!empty($thumbnail)) {
            $image = array_shift($thumbnail)->get(field_media_image)->referencedEntities();
            $uri = (!empty($image)) ? (array_shift($image)->get(uri)->value) : '';
            if (!empty($uri)) {
              $result[$key]['imageSmall'] = $this->getImageUri($uri,'spotlight_mobile_375_x_270');
              $result[$key]['imageMedium'] = $this->getImageUri($uri,'spotlight_tablet_770_x_355_');
              $result[$key]['imageLarge'] = $this->getImageUri($uri,'spotlight_desktop_1194_357');
            }
          }
          $result[$key]['pointValue'] = $node->get('field_point_value')->value;
          break;

        case 'stories':
          $node = $this->getNodeData($value, $language);
          $result[$key]['nid'] = $node->id();
          $result[$key]['displayTitle'] = $node->get('field_display_title')->value;
          $content_section = $node->get(field_content_section)->referencedEntities();
          $result[$key]['type'] = (!empty($content_section)) ? (array_shift($content_section)->get('field_content_section_key')->value) : '';
          $result[$key]['body'] = strip_tags($node->get('body')->value);
          $thumbnail = $node->get(field_hero_image)->referencedEntities();
          if (!empty($thumbnail)) {
            $image = array_shift($thumbnail)->get(field_media_image)->referencedEntities();
            $uri = (!empty($image)) ? (array_shift($image)->get(uri)->value) : '';
            if (!empty($uri)) {
              $result[$key]['imageSmall'] = $this->getImageUri($uri,'spotlight_mobile_375_x_270');
              $result[$key]['imageMedium'] = $this->getImageUri($uri,'spotlight_tablet_770_x_355_');
              $result[$key]['imageLarge'] = $this->getImageUri($uri,'spotlight_desktop_1194_357');
            }
          }
          $result[$key]['pointValue'] = $node->get('field_point_value')->value;
          break;

        case 'tools':
          $node = $this->getNodeData($value, $language);
          $result[$key]['nid'] = $node->id();
          $result[$key]['displayTitle'] = $node->get('field_display_title')->value;
          $result[$key]['type'] = '';
          $result[$key]['body'] = strip_tags($node->get('field_tool_description')->value);
          $thumbnail = $node->get(field_tool_thumbnail)->referencedEntities();
          if (!empty($thumbnail)) {
            $image = array_shift($thumbnail)->get(field_media_image)->referencedEntities();
            $uri = (!empty($image)) ? (array_shift($image)->get(uri)->value) : '';
            if (!empty($uri)) {

              $result[$key]['imageSmall'] = $this->getImageUri($uri,'spotlight_mobile_375_x_270');
              $result[$key]['imageMedium'] = $this->getImageUri($uri,'spotlight_tablet_770_x_355_');
              $result[$key]['imageLarge'] = $this->getImageUri($uri,'spotlight_desktop_1194_357');
            }
          }
          $result[$key]['pointValue'] = $node->get('field_point_value')->value;
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
   * @param $value
   * @param $language
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNodeData($value, $language) {
    $nid = $value['nid'];
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($node->hasTranslation($language)) {
      return $node->getTranslation($language);
    }
  }

  /**
   * @param $uri
   * @param $style
   * @return mixed
   */
  public function getImageUri($uri, $style) {
    $url = ImageStyle::load($style)->buildUrl($uri);
    return $url;
  }
}
