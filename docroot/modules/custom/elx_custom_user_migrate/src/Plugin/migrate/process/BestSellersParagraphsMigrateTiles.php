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
 *  id = "best_sellers_paragraphs_migrate_tiles"
 * )
 */
class BestSellersParagraphsMigrateTiles extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $paragraphs = [];
    $best_sellers_tiles = $row->getSourceProperty('best_sellers_tiles');
    $nid = $row->getSourceProperty('nid');
    $uid = $row->getSourceProperty('uid');
    foreach ($best_sellers_tiles as $best_sellers_tile) {
      $main_fid = $this->getMigratedDestinationId($best_sellers_tile['field_main_image_fid']);
      $operator_paragraph = [];
      // Create the parent paragraph.
      $operator_paragraph = Paragraph::create(['type' => 'best_sellers_template_two']);
      $operator_paragraph->set('field_content', $best_sellers_tile['field_content_value']);

      $node = Node::load($nid);
      if (!empty($node)) {
        $paragraph = $node->field_paragraph_templates->getValue();
        foreach ( $paragraph as $element ) {
          $p = \Drupal\paragraphs\Entity\Paragraph::load( $element['target_id'] );
          $main_image[] = $p->field_main_image->getValue();
         }
        foreach ($main_image as $main_img) {
          $fid = $main_img[0]['target_id'];
          $image_media = Media::create([
           'bundle' => 'image',
           'uid' => $uid,
           'langcode' => 'en',
           'status' => '1',
           'field_media_image' => [
             'target_id' => $main_fid,
            ],
          ]);
          $image_media->save();
          $image_id = $image_media->id();
          $field_image = [
            'target_id' => $image_id,
          ];
          $operator_paragraph->set('field_main_image', $field_image);
        }
      }
      foreach ($best_sellers_tile['media'] as $key => $value) {
        $video_fid = $this->getMigratedDestinationId($value['field_video_bs_t2_fid']);

        $image_bs_ts_fid = $this->getMigratedDestinationId($value['field_image_bs_t2_fid']);
        // Create the child paragraph.
        $performance_work_center_paragraph = Paragraph::create(['type' => 'media_template_two']);

        if (!empty($node)) {
          foreach ( $paragraph as $element ) {
            $media_para = \Drupal\paragraphs\Entity\Paragraph::load( $element['target_id'] );
            $media_bs_t2 = $media_para->get('field_media_template_two')->getValue();
           }
           foreach ($media_bs_t2 as $key => $value) {
             $media_bs_para = \Drupal\paragraphs\Entity\Paragraph::load( $value['target_id'] );
             $image_bs_t2[] = $media_bs_para->field_image_bs_t2->getValue();
           }
          foreach ($image_bs_t2 as $image_bs_t) {
            $fid = $image_bs_t[0]['target_id'];
            $image_media = Media::create([
             'bundle' => 'image',
             'uid' => $uid,
             'langcode' => 'en',
             'status' => '1',
             'field_media_image' => [
               'target_id' => $image_bs_ts_fid,
              ],
            ]);
            $image_media->save();
            $image_id = $image_media->id();
            $field_image = [
              'target_id' => $image_id,
            ];
            $performance_work_center_paragraph->set('field_image_bs_t2', $image_id);
          }
        }

        $performance_work_center_paragraph->set('field_title_bs_t2', $value['field_title_bs_t2_value']);

        $performance_work_center_paragraph->set('field_video_bs_t2', $video_fid);

        // Save the child paragraph.
        $performance_work_center_paragraph->save();
        // Append the items in the child paragraph.
        $operator_paragraph->field_media_template_two->appendItem($performance_work_center_paragraph);
      }
      $operator_paragraph->save();
      $paragraphs[]['entity'] = $operator_paragraph;
    }
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
