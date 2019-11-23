<?php

namespace Drupal\trlx_comment\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_comment\Utility\CommentUtility;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;


/**
 * Helps to save comment in database.
 *
 * @RestResource(
 *   id = "image_style_generate",
 *   label = @Translation("Image Style Generate Post API"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/imagestylegenerate",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/imagestylegenerate"
 *   }
 * )
 */
class ImageStyleGenerate extends ResourceBase {

  /**
   * Save comment data in database.
   *
   * @param array $data
   *   Rest resource query parameters.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Brands category listing.
   */
  public function post(array $data, Request $request) {
    $entity = (object) $data;
    $this->trlx_translation_workflow_generate_img_styles($entity->entity);
  }


  /**
 * Custom method to handle image styles.
 */
public function trlx_translation_workflow_generate_img_styles($data) {
  $commonUtility = new CommonUtility();
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  // Load a single node.
  $entity = $node_storage->load($data['nid'][0]['value']);
  // Product image.
  if ($entity->hasField('field_field_product_image')) {
    $commonUtility->setMediaEntity($entity, 'field_field_product_image', [
      'product_listings_tablet',
      'product_listings_mobile',
      'product_listing_desktop',
      'search_listings_tablet',
      'search_listings_desktop',
      'search_listings_mobile',
      'bookmark_image_mobile',
      'bookmark_image_tablet',
      'bookmark_image_desktop',
      'stories_level_listing_mobile',
      'stories_level_listing_tablet',
      'stories_level_listing_desktop',
      'media_entity_browser_thumbnail',
    ]
    );
  }
  // Feature image.
  if ($entity->hasField('field_featured_image')) {
    $commonUtility->setMediaEntity($entity, 'field_featured_image', [
      'level_home_page_tablet',
      'level_home_page_mobile',
      'level_home_page_desktop',
      'product_details_tablet',
      'product_details_mobile',
      'product_details_desktop',
      'brand_story_mobile',
      'brand_story_desktop',
      'brand_story_tablet',
      'story_detail_mobile',
      'story_detail_desktop',
      'story_detail_tablet',
      'insider_corner_detail_mobile',
      'insider_corner_detail_desktop',
      'insider_corner_detail_tablet',
      'spotlight_mobile',
      'spotlight_desktop',
      'spotlight_tablet',
      'search_listings_tablet',
      'search_listings_desktop',
      'search_listings_mobile',
      'bookmark_image_mobile',
      'bookmark_image_tablet',
      'bookmark_image_desktop',
      'trends_homepage_desktop',
      'trends_homepage_tablet',
      'trends_homepage_mobile',
      'insider_corner_hompage_section_desktop',
      'insider_corner_hompage_section_tablet',
      'insider_corner_hompage_section_mobile',
      'media_entity_browser_thumbnail',
    ]
    );
  }
  // Carousel image.
  if ($entity->hasField('field_product_carousel')) {
    $commonUtility->setMediaEntity($entity, 'field_product_carousel', [
      'carousel_image_mobile',
      'carousel_image_tablet',
      'carousel_image_desktop',
      'media_entity_browser_thumbnail',
    ]
    );
  }
  // Hero image.
  if ($entity->hasField('field_hero_image')) {
    $commonUtility->setMediaEntity($entity, 'field_hero_image', [
      'listing_image_mobile',
      'listing_image_tablet',
      'listing_image_desktop',
      'trends_homepage_tablet',
      'trends_homepage_mobile',
      'trends_homepage_desktop',
      'insider_corner_hompage_section_mobile',
      'insider_corner_hompage_section_tablet',
      'insider_corner_hompage_section_desktop',
      'stories_listing_mobile',
      'stories_listing_desktop',
      'stories_listing_tablet',
      'levels_mobile_module',
      'levels_module_desktop',
      'levels_module_tablet',
      'search_listings_tablet',
      'search_listings_desktop',
      'search_listings_mobile',
      'bookmark_image_mobile',
      'bookmark_image_tablet',
      'bookmark_image_desktop',
      'stories_level_listing_mobile',
      'stories_level_listing_tablet',
      'stories_level_listing_desktop',
      'media_entity_browser_thumbnail',
    ]
    );
  }
  // Video image.
  if ($entity->hasField('field_tool_thumbnail')) {
    $commonUtility->setMediaEntity($entity, 'field_tool_thumbnail', [
      'video_listing_mobile',
      'video_listing_desktop',
      'video_listing_tablet',
      'video_detail_mobile',
      'video_detail_desktop',
      'video_detail_tablet',
      'search_listings_tablet',
      'search_listings_desktop',
      'search_listings_mobile',
      'bookmark_image_mobile',
      'bookmark_image_tablet',
      'bookmark_image_desktop',
      'media_entity_browser_thumbnail',
    ]
    );
  }
  // Spotlight mobile image.
  if ($entity->hasField('field_image_home_page')) {
    $commonUtility->setMediaEntity($entity, 'field_image_home_page', [
      'spotlight_mobile',
      'media_entity_browser_thumbnail',
    ]
    );
  }
  // Video thumbnail image.
  if ($entity->hasField('field_video_thumbnail')) {
    $commonUtility->setMediaEntity($entity, 'field_video_thumbnail', [
      'video_thumbnail_desktop',
      'video_thumbnail_tablet',
      'video_thumbnail_mobile',
    ]
    );
  }
  if ($entity->hasField('field_product_video_thumbnail')) {
    $commonUtility->setMediaEntity($entity, 'field_product_video_thumbnail', [
      'video_thumbnail_desktop',
      'video_thumbnail_tablet',
      'video_thumbnail_mobile',
    ]
    );
  }
}
}