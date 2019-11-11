<?php

namespace Drupal\trlx_banner\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a Listing Image resource.
 *
 * @RestResource(
 *   id = "listing_image",
 *   label = @Translation("Listing Image"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/listingImage"
 *   }
 * )
 */
class ListingImage extends ResourceBase {

  /**
   * Fetch Image listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Image Listing.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
      'section',
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

    $img_name = $commonUtility->getListingImg($section);
    $result = [];
    if (!empty($img_name['image'])) {
      $result['imageSmall'] = $commonUtility->loadImageStyle('listing_image_mobile', $img_name['image']);
      $result['imageMedium'] = $commonUtility->loadImageStyle('listing_image_tablet', $img_name['image']);
      $result['imageLarge'] = $commonUtility->loadImageStyle('listing_image_desktop', $img_name['image']);
    }

    if (empty($result)) {
      $emptyResult['imageSmall'] = $emptyResult['imageMedium'] = $emptyResult['imageLarge'] = null;
      return new JsonResponse($emptyResult, Response::HTTP_OK);
    }

    return new JsonResponse($result, 200);
  }

}
