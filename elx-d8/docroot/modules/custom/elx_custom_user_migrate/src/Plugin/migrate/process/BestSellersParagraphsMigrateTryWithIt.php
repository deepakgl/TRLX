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
 *  id = "best_sellers_paragraphs_migrate_try_with_it"
 * )
 */
class BestSellersParagraphsMigrateTryWithIt extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $paragraphs = [];
    $try_with_its = $row->getSourceProperty('best_sellers_try_with');
    $nid = $row->getSourceProperty('nid');
    $uid = $row->getSourceProperty('uid');
    // Create the parent paragraph.
    $operator_paragraph = Paragraph::create(['type' => 'best_sellers_template_four']);
    foreach ($try_with_its as $try_with_it) {
      $image_fid = $this->getMigratedDestinationId($try_with_it['field_image_first_bs_t4_fid']);
      // Create the child paragraph.
      $performance_work_center_paragraph = Paragraph::create(['type' => 'field_slids_bs_t4']);

      $node = Node::load($nid);
      if (!empty($node)) {
        $paragraph = $node->field_best_sellers_four->getValue();
        foreach ( $paragraph as $element ) {
          $p = \Drupal\paragraphs\Entity\Paragraph::load( $element['target_id'] );
          $main_image = $p->field_slids_bs_t4->getValue();
         }
         foreach ($main_image as $key => $value) {
           $ps = \Drupal\paragraphs\Entity\Paragraph::load( $value['target_id'] );
           $ps_image[] = $ps->field_image_first_bs_t4->getValue();
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
          $performance_work_center_paragraph->set('field_image_first_bs_t4', $field_image);
        }
      }
      // Set value of product family image.
      $performance_work_center_paragraph->set('field_title_first_bs_t4', $try_with_it['field_title_first_bs_t4_value']);
      // Save the child paragraph.
      $performance_work_center_paragraph->save();
      // Append the items in the child paragraph.
      $operator_paragraph->field_slids_bs_t4->appendItem($performance_work_center_paragraph);
    }
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

}
