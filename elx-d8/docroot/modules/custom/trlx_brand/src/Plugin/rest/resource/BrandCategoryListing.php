<?php

namespace Drupal\trlx_brand\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\trlx_brand\Utility\BrandUtility;
use Drupal\trlx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a brands category listing resource.
 *
 * @RestResource(
 *   id = "brands_category_listing",
 *   label = @Translation("Brands Category Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/brandCategories"
 *   }
 * )
 */
class BrandCategoryListing extends ResourceBase {

  /**
   * Fetch brands category listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Brands category listing.
   */
  public function get(Request $request) {
    $this->commonUtility = new CommonUtility();
    $this->brandUtility = new BrandUtility();

    // Response format validation.
    $_format = $request->query->get('_format');
    $response = $this->commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Query to get all brand term ids.
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'brands');
    $term_ids = $query->execute();
    $brands_list = [];
    $i = 0;
    foreach ($term_ids as $term) {
      $data = $this->brandUtility->brandTermData($term);
      if (!empty($data['brand_logo_target_id'])) {
        $brands_list[$i]['id'] = $data['tid'];
        $brands_list[$i]['link'] = '';
        $brands_list[$i]['title'] = $data['name'];
        // Create image urls for three different display screens.
        $brands_list[$i]['imageSmall'] = $this->commonUtility->getImageStyleBasedUrl('brands_category_listing_mobile', $data['brand_logo_uri']);
        $brands_list[$i]['imageMedium'] = $this->commonUtility->getImageStyleBasedUrl('brands_category_listing_tablet', $data['brand_logo_uri']);
        $brands_list[$i]['imageLarge'] = $this->commonUtility->getImageStyleBasedUrl('brands_category_listing_desktop', $data['brand_logo_uri']);
      }
      $i++;
    }
    // Sorting items alphabetically by title.
    usort($brands_list, 'compareTitle');
    return $this->commonUtility->successResponse($brands_list);
  }

  /**
   * Compare title to arrange alphabetically.
   */
  public function compareTitle($a, $b) {
    return strnatcmp($a['title'], $b['title']);
  }

}
