<?php

namespace Drupal\trlx_drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\trlx_drush\Utility\DrushUtility;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class BatchImport extends DrushCommands {

  /**
   * Process Brand Region/Market terms.
   *
   * @param string $option
   *   Term type e.g. subRegion, country & brand.
   *
   * @command import:batch
   * @aliases import-batch
   *
   * @usage import:batch
   */
  public function importBatch($option) {
    $drush_utility = new DrushUtility();
    $lbUrl = \Drupal::config('elx_utility.settings')->get('middleware_lb_name');
    if ((isset($option)) && (!empty($lbUrl))) {
      // Token for authentication.
      $token = 'Y21zY2xpZW50bWFzdGVyOmNtc3Bhc3MxMjkw';
      $response = '';
      $header = [
        'headers' => [
          'Accept' => 'application/json',
          'X-AUTH-TOKEN' => $token,
        ],
        'http_errors' => FALSE,
      ];
      switch ($option) {
        case 'subRegion':
          $endpoint = $lbUrl . '/api/secure/cms/onboarding/subregions?regionId=ALL';
          $terms = $this->getApiResponse($endpoint, $header);
          $terms = !empty($terms['subRegions']) ? $terms['subRegions'] : [];
          $drush_utility->processTerms($terms, 'markets', 'subregion');
          break;

        case 'country':
          $endpoint_regions = $lbUrl . '/api/secure/cms/onboarding/subregions?regionId=ALL';
          $sub_regions = $this->getApiResponse($endpoint_regions, $header);
          $sub_regions = !empty($sub_regions['subRegions']) ? $sub_regions['subRegions'] : [];
          $sub_region_string = '';
          if (!empty($sub_regions)) {
            foreach ($sub_regions as $sub_region) {
              $sub_region_string .= ($sub_region->id . ',');
            }
            $sub_region_query_param = substr($sub_region_string, 0, -1);
          }

          if ($sub_region_query_param !== '') {
            $endpoint = $lbUrl . '/api/secure/cms/onboarding/countries?subRegionIds=' . $sub_region_query_param;
            $terms = $this->getApiResponse($endpoint, $header);
            $terms = !empty($terms['countries']) ? $terms['countries'] : [];
            $drush_utility->processTerms($terms, 'markets', 'country');
          }
          break;

        case 'brand':
          $endpoint = $lbUrl . '/api/secure/cms/onboarding/brands?regionId=ALL';
          $terms = $this->getApiResponse($endpoint, $header);
          $terms = !empty($terms['brands']) ? $terms['brands'] : [];

          // Process Terms.
          $termsProcessed = $drush_utility->processBrandTerms($terms, 'brands', 'field_brand_key');
          if ($termsProcessed) {
            \Drupal::logger('trlx_drush')->info(t('Brand term(s) processed successfully...'));
          }
          break;

      }
    }
  }

  /**
   * Fetch Api Response.
   *
   * @param \Drupal\Core\Url $endpoint
   *   Endpoint url.
   * @param array $header
   *   Header of request.
   *
   * @return array
   *   Response array.
   */
  public function getApiResponse($endpoint, $header) {
    $client = \Drupal::httpClient();
    try {
      $response = $client->get($endpoint,
        $header);
      $status_code = $response->getStatusCode();

      if ($status_code === 200) {
        $response_data = $response->getBody();
        $data = (array) json_decode($response_data);

        return $data;
      }
    }
    catch (Exception $e) {
      return [];
    }
  }

}
