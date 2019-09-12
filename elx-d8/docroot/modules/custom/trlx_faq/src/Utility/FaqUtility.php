<?php

namespace Drupal\trlx_faq\Utility;

use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\file\Entity\File;

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
    if ($brand_id) {
      $query->condition('nfb.langcode', $langcode, '=');
    }
    $query->condition('nfq.langcode', $langcode, '=');
    $query->condition('n.status', 1, '=');
    $result = $query->execute()->fetchAll();
    return $result;
  }

  /**
   * Create menu custom array.
   *
   * @param object $menu_item
   *   Menu data.
   * @param string $langcode
   *   Language code.
   *
   * @return array
   *   Menu array.
   */
  public function createMenuArray($menu_item, $langcode) {
    $uuid = $menu_item->getDerivativeId();
    if (!empty($uuid)) {
      $this->commonUtility = new CommonUtility();
      $entity = \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $uuid);
      $tid = $entity->getFields()['trlx_menu_content']->getString();
      $term_name = '';
      if ($tid) {
        $term_name = $this->commonUtility->getTermName($tid);
      }
      $fid = $entity->link->first()->options['menu_icon']['fid'];
      $type = 'internal';
      $url = $icon_path = '';
      if ($menu_item->getUrlObject()->isExternal()) {
        $type = 'external';
        $url = $menu_item->getUrlObject()->toString();
      }
      // Get menu icon path.
      if (!empty($fid)) {
        $file = File::load($fid);
        if (!empty($file)) {
          $icon_path = file_create_url($file->getFileUri());
        }
      }
      
      // Get link attributes.
      $options = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode)->getUrlObject()->getOptions() : $entity->getUrlObject()->getOptions();

      $menu_result = [
        'sequenceId' => $entity->hasTranslation($langcode) ? intval($entity->getTranslation($langcode)->getWeight()) : intval($entity->getWeight()),
        'content' => $term_name,
        'name' => $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode)->getTitle() : $entity->getTitle(),
        'URL' => $url,
        'type' => $type,
        'attributes' => isset($options['attributes']) ? $options['attributes'] : "",
      ];
    }
    return $menu_result;
  }

}
