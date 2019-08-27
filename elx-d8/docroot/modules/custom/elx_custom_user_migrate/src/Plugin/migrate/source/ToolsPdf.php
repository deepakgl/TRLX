<?php
namespace Drupal\elx_custom_user_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

/**
 * Source plugin for beer content.
 *
 * @MigrateSource(
 *   id = "custom_tools_pdf"
 * )
 */
 class ToolsPdf extends SqlBase {

   /**
    * {@inheritdoc}
    */
   public function query() {
     $query = $this->select('node', 'n');
     $query->addJoin('left', 'field_data_field_tool_pdf', 'ftp', 'ftp.entity_id = n.nid');
     $query->addJoin('left', 'file_managed', 'fm', 'fm.fid = ftp.field_tool_pdf_fid');
     $query->fields('n')
       ->condition('n.type', 'tools', '=')
       ->condition('n.language', 'en', '=')
       ->condition('fm.type', 'video', '!=');
     $result = $query->execute()->fetchAll();
     return $query;
   }

   /**
    * {@inheritdoc}
    */
   public function fields() {
     $fields = [
       'nid' => $this->t('Node ID'),
       'type' => $this->t('Type'),
       'title' => $this->t('Title'),
       'created' => $this->t('Created timestamp'),
       'changed' => $this->t('Modified timestamp'),
       'status' => $this->t('Published'),
       'promote' => $this->t('Promoted to front page'),
       'sticky' => $this->t('Sticky at top of lists'),
       'language' => $this->t('Language (fr, en, ...)'),
       'timestamp' => $this->t('The timestamp the latest revision of this node was created.'),
     ];
     return $fields;
   }

   /**
    * {@inheritdoc}
    */
   public function getIds() {
     return [
       'nid' => [
         'type' => 'integer',
         'alias' => 'n',
       ],
     ];
   }

   /**
    * {@inheritdoc}
    */
   public function prepareRow(Row $row) {
     $uid = $row->getSourceProperty('uid');
     $nid = $row->getSourceProperty('nid');
     $title = $row->getSourceProperty('title');

     $this->getToolsFields('field_data_field_display_title', 'field_display_title_value', $nid, $row);
     $this->getToolsFields('field_data_field_tool_description', 'field_tool_description_value', $nid, $row);
     $this->getToolsFields('field_data_field_point_value', 'field_point_value_value', $nid, $row);
     $this->getToolsFields('field_data_field_featured', 'field_featured_value', $nid, $row);
     $this->getToolsFields('field_data_field_featured_on_elx_specialty', 'field_featured_on_elx_specialty_value', $nid, $row);
     $this->getToolsFields('field_data_field_headline', 'field_headline_value', $nid, $row);

     $this->getToolsReferenceFields('field_data_field_tool_pdf', 'field_tool_pdf_fid', $nid, $row);
     $this->getToolsReferenceFields('field_data_field_tool_thumbnail', 'field_tool_thumbnail_fid', $nid, $row);
     $this->getToolsReferenceFields('field_data_field_featured_image', 'field_featured_image_fid', $nid, $row);
     $this->getToolsReferenceFields('field_data_field_tool_by_role', 'field_tool_by_role_tid', $nid, $row);
     $this->getMarketFields('og_membership', 'gid', 'node', $nid, $row);


     $tool_pdf = $row->getSourceProperty('field_tool_pdf_fid');
     $tool_thumbnail = $row->getSourceProperty('field_tool_thumbnail_fid');
     $tool_featured_image = $row->getSourceProperty('field_featured_image_fid');

     // Get files destination id.
     $tool_pdf_ids = $this->getMigratedDestinationId($tool_pdf);
     $tool_thumbnail_ids = $this->getMigratedDestinationId($tool_thumbnail);
     $tool_featured_image_ids = $this->getMigratedDestinationId($tool_featured_image);
     $node = Node::load($nid);
     if (!empty($node)) {
       if (!empty($tool_pdf_ids[0]->destid1)) {
         $fid = $tool_pdf_ids[0]->destid1;
         $image_media = Media::create([
           'bundle' => 'file',
           'uid' => $uid,
           'langcode' => 'en',
           'status' => '1',
           'field_media_file' => [
             'target_id' => $fid,
           ],
         ]);
         $image_media->save();
         $image_id = $image_media->id();
         $field_image = array(
           'target_id' => $image_id,
         );
         $node->field_tool_media_pdf = $field_image;
         $node->save();
       }

       if (!empty($tool_thumbnail_ids[0]->destid1)) {
         $fid = $tool_thumbnail_ids[0]->destid1;
         $image_media = Media::create([
           'bundle' => 'image',
           'uid' => $uid,
           'langcode' => 'en',
           'status' => '1',
           'field_media_image' => [
             'target_id' => $fid,
           ],
         ]);
         $image_media->save();
         $image_id = $image_media->id();

         $field_image = array(
           'target_id' => $image_id,
         );
         $node->field_tool_thumbnail = $field_image;
         $node->save();
       }

       if (!empty($tool_featured_image_ids[0]->destid1)) {
         $fid = $tool_featured_image_ids[0]->destid1;
         $image_media = Media::create([
           'bundle' => 'image',
           'uid' => $uid,
           'langcode' => 'en',
           'status' => '1',
           'field_media_image' => [
             'target_id' => $fid,
           ],
         ]);
         $image_media->save();
         $image_id = $image_media->id();

         $field_image = array(
           'target_id' => $image_id,
         );
         $node->field_featured_image = $field_image;
         $node->save();
       }
     }

     return parent::prepareRow($row);

   }

   protected function getMigratedDestinationId($sourceid) {
     $query = \Drupal::database()->select('migrate_map_custom_file', 'mp');
     $query->fields('mp', ['destid1']);
     $query->condition('sourceid1', $sourceid, 'IN');
     $results = $query->execute()->fetchAll();
     return $results;
   }

   protected function getMediaId($table_name, $field_name, $media_id) {
     $query = \Drupal::database()->select($table_name, 'mp');
     $query->fields('mp', [$field_name]);
     $query->condition('mp.mid', $media_id);
     $data = $query->execute()->fetchColumn();
     return $data;
   }

   protected function getToolsFields($table_name, $field_name, $nid, &$row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.entity_id', $nid, '=')
       ->condition('tn.bundle', 'tools', '=');
     $result = $query->execute()->fetchAll();
     foreach ($result as $record) {
       $product_fields[] = $record[$field_name];
     }
     $row->setSourceProperty($field_name, $product_fields);
     return $row;
   }

   protected function getToolsReferenceFields($table_name, $field_name, $nid, &$row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.entity_id', $nid, '=')
       ->condition('tn.bundle', 'tools', '=')
       ->condition('tn.entity_type', 'node', '=');
     $result = $query->execute()->fetchAll();
     foreach ($result as $record) {
       $product_fields[] = $record[$field_name];
     }
     $row->setSourceProperty($field_name, $product_fields);
     return $row;
   }

   protected function getMarketFields($table_name, $field_name, $type, $nid, &$row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.etid', $nid, '=')
       ->condition('tn.entity_type', $type, '=');
     $result = $query->execute()->fetchAll();
     foreach ($result as $record) {
       $user_market[] = $record[$field_name];
     }
     $row->setSourceProperty('tools_market', $user_market);
     return $row;
   }

 }

?>
