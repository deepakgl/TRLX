<?php

namespace Drupal\trlx_faq\Utility;

/**
 * Purpose of this class is to build faq object.
 */
class FaqUtility {

  /**
   * Load menu by name.
   *
   * @param string $langcode
   *   Language code.
   * @param int $brand_id
   *   Brand id.
   *
   * @return array
   *   Faq content data.
   */
  public function getFaqContent($langcode = 'en', $brand_id = NULL) {
    $query = \Drupal::database();
    $query = $query->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'langcode']);
    $query->leftJoin('node__field_question', 'nfq', 'nfq.entity_id = n.nid');
    $query->addExpression('nfq.field_question_value', 'question');
    $query->leftJoin('node__body', 'nb', 'nb.entity_id = n.nid');
    $query->addExpression('nb.body_value', 'answer');
    $query->leftJoin('node__field_brands', 'nfb', 'nfb.entity_id = n.nid');
    $query->leftJoin('taxonomy_term__field_brand_key', 'ttfbk', 'ttfbk.entity_id = nfb.field_brands_target_id');
    $query->addExpression('ttfbk.field_brand_key_value', 'brand_key_value');
    $query->condition('n.type', 'faq', '=');
    $query->condition('n.langcode', $langcode, '=');
    $query->condition('nb.langcode', $langcode, '=');
    $query->condition('nfq.langcode', $langcode, '=');
    if ($brand_id) {
      $query->condition('ttfbk.field_brand_key_value', $brand_id, '=');
    }
    $query->condition('n.status', 1, '=');
    $result = $query->execute()->fetchAll();
    return $result;
  }

}
