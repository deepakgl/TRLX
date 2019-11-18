<?php

namespace Drupal\trlx_brand\Utility;

/**
 * Purpose of this class is to build common object.
 */
class BrandUtility {

  /**
   * Taxonomy terms data based on language and term ids.
   *
   * @param array $tids
   *   Term ids.
   * @param string $language
   *   Language code.
   *
   * @return array
   *   Taxonomy term data.
   */
  public function brandTermData(array $tids, $language = 'en') {
    try {
      $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
      $query->fields('ttfd', ['tid', 'vid', 'langcode', 'name']);
      $query->leftjoin('taxonomy_term__field_brand_logo', 'ttfbl', 'ttfbl.entity_id = ttfd.tid');
      $query->leftjoin('file_managed', 'fm', 'fm.fid = ttfbl.field_brand_logo_target_id');
      $query->leftjoin('taxonomy_term__field_brand_key', 'ttfbk', 'ttfbk.entity_id = ttfd.tid');
      $query->addExpression('ttfbl.field_brand_logo_target_id', 'brand_logo_target_id');
      $query->addExpression('fm.uri', 'brand_logo_uri');
      $query->addExpression('ttfbk.field_brand_key_value', 'brand_key_value');
      $query->condition('ttfd.tid', $tids, 'IN');
      $query->condition('ttfd.langcode', $language, '=');
      return $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get all brand keys.
   *
   * @return array
   *   Brand keys.
   */
  public static function getAllBrandKeys() {
    try {
      $query = \Drupal::database()->select('taxonomy_term__field_brand_key', 'bk');
      $query->fields('bk', ['field_brand_key_value']);
      $results = $query->execute()->fetchAll();
      if (empty($results)) {
        return [];
      }
      return array_column($results, 'field_brand_key_value');
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
