<?php

namespace Drupal\elx_custom_user_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;
use \Drupal\field_collection\Entity\FieldCollection;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

/**
 * Provides a 'BestSellersParagraphsMigrate' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "best_sellers_paragraphs_migrate"
 * )
 */
class BestSellersParagraphsMigrate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $nid = $row->getSourceProperty('nid');
    $uid = $row->getSourceProperty('uid');
    $paragraphs = [];
    $prod_family_paras = $row->getSourceProperty('product_family_paragraph');
    // Create the parent paragraph.
    $operator_paragraph = Paragraph::create(['type' => 'best_sellers_template_three']);

    foreach ($prod_family_paras as $prod_family_para) {
      $image_fid = $this->getMigratedDestinationId($prod_family_para['field_image_bs_t3_fid']);
      // Create the child paragraph.
      $performance_work_center_paragraph = Paragraph::create(['type' => 'field_slides_bs_t3']);
      // Set value of product family description.
      $performance_work_center_paragraph->set('field_product_family_descr', $prod_family_para['field_product_family_description_value']);

      $node = Node::load($nid);
      if (!empty($node)) {
        $paragraph = $node->field_best_sellers_three->getValue();
        foreach ( $paragraph as $element ) {
          $p = \Drupal\paragraphs\Entity\Paragraph::load( $element['target_id'] );
          $main_image = $p->field_slides_bs_t3->getValue();
         }
         foreach ($main_image as $key => $value) {
           $ps = \Drupal\paragraphs\Entity\Paragraph::load( $value['target_id'] );
           $ps_image[] = $ps->field_image_bs_t3->getValue();
         }
        foreach ($ps_image as $main_img) {
          $fid = $main_img[0]['target_id'];
          $image_media = Media::create([
           'bundle' => 'image',
           'uid' => $uid,
           'langcode' => 'en',
           'status' => '1',
           'field_media_image' => [
             'target_id' => $image_fid,
            ],
          ]);
          $image_media->save();
          $image_id = $image_media->id();
          $field_image = [
            'target_id' => $image_id,
          ];
          $performance_work_center_paragraph->set('field_image_bs_t3', $field_image);
        }
      }
      // Save the child paragraph.
      $performance_work_center_paragraph->save();
      // Append the items in the child paragraph.
      $operator_paragraph->field_slides_bs_t3->appendItem($performance_work_center_paragraph);
    }
    // Save the parent paragraph.
    $operator_paragraph->save();
    $paragraphs[]['entity'] = $operator_paragraph;
    return $paragraphs;
  }

  protected function getMigratedDestinationId($value) {
    $query = \Drupal::database()->select('migrate_map_custom_file', 'mp');
      $query->fields('mp', ['destid1']);
      $query->condition('sourceid1', $value, '=');
      $results = $query->execute()->fetchAll();
      return $results[0]->destid1;
  }

  protected function getMediaId($table_name, $field_name, $media_id) {
    $query = \Drupal::database()->select($table_name, 'mp');
    $query->fields('mp', [$field_name]);
    $query->condition('mp.mid', $media_id);
    $data = $query->execute()->fetchColumn();
    return $data;
  }

}
