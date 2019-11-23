<?php

namespace Drupal\trlx_stamp\Utility;

use Drupal\taxonomy\Entity\Term;
use Elasticsearch\ClientBuilder;

/**
 * Purpose of this class is to build common object.
 */
class StampUtility {

  /**
   * Function for migrate stamps.
   */
  public function migrateStamp() {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'badges');
    $lang = \Drupal::languageManager()->getLanguages();
    $lang = array_keys($lang);

    // Get terms Ids.
    $tids = $query->execute();

    // Loading the multiple terms by tid.
    $terms = Term::loadMultiple($tids);

    foreach ($lang as $key => $value) {
      // Tree by Term name.
      foreach ($terms as $term) {
        $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $value);
        $badge_master[$translated_term->name->value] = [
        // Term id.
          'tid' => $translated_term->id(),
          'src' => $translated_term->field_badge_image->target_id,
          'title' => $translated_term->field_badges_title->value,
        ];
      }
      $hosts = [
        [
          'host' => \Drupal::config('elx_utility.settings')->get('elastic_host'),
          'port' => \Drupal::config('elx_utility.settings')->get('elastic_port'),
          'scheme' => \Drupal::config('elx_utility.settings')->get('elastic_scheme'),
          'user' => \Drupal::config('elx_utility.settings')
            ->get('elastic_username'),
          'pass' => \Drupal::config('elx_utility.settings')
            ->get('elastic_password'),
        ],
      ];
      $client = ClientBuilder::create()->setHosts($hosts)->build();
      $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
      // Check for index existence previously.
      $indexParams = [
        'index' => $env . '_badge_master',
        'type' => 'badge_master',
        'id' => $value,
      ];
      $exist = $client->exists($indexParams);
      // If index not exist, create new index.
      $params = [
        'index' => $env . '_badge_master',
        'type' => 'badge_master',
        'id' => $value,
        'body' => [
          'badge' => $badge_master,
        ],
      ];
      $response = $client->index($params);
    }
  }

}
