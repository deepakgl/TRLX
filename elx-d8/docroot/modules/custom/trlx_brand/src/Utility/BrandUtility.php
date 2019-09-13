<?php

namespace Drupal\trlx_brand\Utility;

/**
 * Purpose of this class is to build common object.
 */
class BrandUtility {

  /**
   * Taxonomy term data based on language and term id.
   *
   * @param int $tid
   *   Term id.
   * @param string $language
   *   Language code.
   *
   * @return array
   *   Taxonomy term data.
   */
  public function brandTermData($tid, $language = 'en') {
    $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');
    $query->fields('ttfd', ['tid', 'vid', 'langcode', 'name']);
    $query->leftjoin('taxonomy_term__field_brand_logo', 'ttfbl', 'ttfbl.entity_id = ttfd.tid');
    $query->leftjoin('file_managed', 'fm', 'fm.fid = ttfbl.field_brand_logo_target_id');
    $query->addExpression('ttfbl.field_brand_logo_target_id', 'brand_logo_target_id');
    $query->addExpression('fm.uri', 'brand_logo_uri');
    $query->condition('ttfd.tid', $tid, '=');
    $query->condition('ttfd.langcode', $language, '=');
    return $query->execute()->fetchAssoc();
  }

}
