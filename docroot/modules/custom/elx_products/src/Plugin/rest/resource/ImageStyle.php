<?php

namespace Drupal\elx_products\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\elx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;

/**
 * Provides a image style resource.
 *
 * @RestResource(
 *   id = "image_style",
 *   label = @Translation("Image Style"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/imageStyle"
 *   }
 * )
 */
class ImageStyle extends ResourceBase {

  /**
   * Rest resource for image style.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $style = [];
    $media_id = $request->query->get('imageId');
    $style_large = $request->query->get('styleLarge');
    $style_medium = $request->query->get('styleMedium');
    $style_small = $request->query->get('styleSmall');
    try {
      $query = CommonUtility::getFidByMediaId($media_id);
    }
    catch (\Exception $e) {
      return FALSE;
    }
    if (!empty($query)) {
      $file = File::load($query);
      $file_uri = $file->getFileUri();
      $style['large'] = $this->loadImageStyle($style_large, $file_uri);
      $style['medium'] = $this->loadImageStyle($style_medium, $file_uri);
      $style['small'] = $this->loadImageStyle($style_small, $file_uri);
    }

    return new JsonResponse($style, 200, [], FALSE);
  }

  /**
   * Load image style.
   *
   * @param string $style_name
   *   Image style name.
   * @param string $file_uri
   *   File uri.
   *
   * @return string
   *   Image style uri.
   */
  protected function loadImageStyle($style_name, $file_uri) {
    $image_style = \Drupal::entityTypeManager()->getStorage('image_style')->load($style_name);
    $result = $image_style->buildUrl($file_uri);

    return $result;
  }

}
