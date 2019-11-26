<?php

namespace Drupal\trlx_drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\taxonomy\Entity\Term;

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
    if (isset($option)) {
      $lbUrl = \Drupal::config('elx_utility.settings')->get('middleware_lb_name');
      $token = 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJsYXN0TmFtZSI6IkNoaW50YWwiLCJjb3VudHJ5IjpbXSwic3ViUmVnaW9uIjpbXSwiYnJhbmRzIjpbIjAyIiwiMDEiLCIwMyIsIjA0IiwiMTAiLCI0MCIsIjExIiwiMDciLCIzMyIsIjI2Il0sImlzRXh0ZXJuYWwiOjAsImxhbmd1YWdlIjoiIiwidWlkIjoiNWRjOWE0NTUzZDNlNzEwMDAxMzNhODI2IiwiZmlyc3ROYW1lIjoiUHJhc2hhbnQiLCJwcmltYXJ5QnJhbmQiOiIiLCJyZWdpb24iOlsiMjAwMCIsIjIzMDAiLCIyNjAwIl0sImVtYWlsIjoicHJhc2hhbnQuY2hpbnRhbEBtaW5kc3RpeC5jb20iLCJzdGF0dXMiOjF9.MtShzRiHwFOzbgBttvrIGGg40qHd-IS3CBzbbOR_Oc4';
      $client = \Drupal::httpClient();
      $response = '';
      switch ($option) {
        case 'subRegion':
          $response = $client->get($lbUrl . '/api/secure/onboarding/subregions?regionId=ALL',
            [
              'headers' => [
                'Accept' => 'application/json',
                'X-AUTH-TOKEN' => $token,
              ],
            ]);
          $data = (string) $response->getBody();
          $terms = (array) json_decode($data);
          $terms = !empty($terms['subRegions']) ? $terms['subRegions'] : [];

          // Process Terms.
          $termsProcessed = $this->processTerms($terms, 'markets', 'field_region_subreg_country_id');
          if ($termsProcessed) {
            \Drupal::logger('trlx_drush')->info(t('SubRegion term(s) processed successfully...'));
          }
          break;

        case 'country':
          $response = $client->get($lbUrl . '/api/secure/onboarding/countries?regionId=ALL',
            [
              'headers' => [
                'Accept' => 'application/json',
                'X-AUTH-TOKEN' => $token,
              ],
            ]);
          $data = (string) $response->getBody();
          $terms = (array) json_decode($data);
          $terms = !empty($terms['countries']) ? $terms['countries'] : [];

          // Process Terms.
          $termsProcessed = $this->processTerms($terms, 'markets', 'field_region_subreg_country_id');
          if ($termsProcessed) {
            \Drupal::logger('trlx_drush')->info(t('Country term(s) processed successfully...'));
          }
          break;

        case 'brand':
          $response = $client->get($lbUrl . '/api/secure/onboarding/brands?regionId=ALL',
            [
              'headers' => [
                'Accept' => 'application/json',
                'X-AUTH-TOKEN' => $token,
              ],
            ]);
          $data = (string) $response->getBody();
          $terms = (array) json_decode($data);
          $terms = !empty($terms['brands']) ? $terms['brands'] : [];

          // Process Terms.
          $termsProcessed = $this->processTerms($terms, 'brands', 'field_brand_key');
          if ($termsProcessed) {
            \Drupal::logger('trlx_drush')->info(t('Brand term(s) processed successfully...'));
          }
          break;

      }
    }
  }

  /**
   * Process SubRegion, Country & Brand terms from middleware.
   *
   * @param array $termsArr
   *   SubRegion/Country/Brand terms.
   * @param string $type
   *   Taxonomy type in cms e.g. markets/brands.
   * @param string $termField
   *   Taxonomy field name e.g. field_brand_key.
   *
   * @return bool
   *   Boolean flag to determine whether terms are processed.
   */
  public function processTerms(array $termsArr = [], string $type = '', string $termField = '') {
    $results = [];
    $termsProcessed = FALSE;
    if (!empty($termsArr) && $type && $termField) {
      foreach ($termsArr as $termObj) {
        // Load term.
        $term = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'name' => $termObj->name,
            'vid' => $type,
          ]);

        // Update term field value in existing term.
        if (!empty($term)) {
          $tid = key($term);
          $term[$tid]->set($termField, $termObj->id);
          $term[$tid]->save();
          $results[] = $tid;
        }
        else {
          // Add term.
          $termCeated = Term::create([
            'parent' => [],
            'name' => $termObj->name,
            'vid' => $type,
            $termField => $termObj->id,
          ])->save();
          if ($termCeated) {
            $results[] = $termObj->name;
          }
        }
      }
    }
    if (!empty($results)) {
      $termsProcessed = TRUE;
    }
    return $termsProcessed;
  }

}
