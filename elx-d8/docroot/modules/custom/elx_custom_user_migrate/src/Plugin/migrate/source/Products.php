<?php
namespace Drupal\elx_custom_user_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

/**
 * Source plugin for products content.
 *
 * @MigrateSource(
 *   id = "custom_products"
 * )
 */
 class Products extends SqlBase {

   /**
    * {@inheritdoc}
    */
   public function query() {
     $query = $this->select('node', 'n')
       ->fields('n')
       ->condition('n.type', 'product_detail', '=')
       ->condition('n.language', 'en', '=');
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

     $this->getProductsFields('field_data_field_benefits', 'field_benefits_value', $nid, $row);
     $this->getProductsFields('field_data_field_if_she_asks_share', 'field_if_she_asks_share_value', $nid, $row);
     $this->getProductsFields('field_data_field_perfect_partners_text', 'field_perfect_partners_text_value', $nid, $row);
     $this->getProductsFields('field_data_field_point_value', 'field_point_value_value', $nid, $row);
     $this->getProductsFields('field_data_field_price', 'field_price_value', $nid, $row);
     $this->getProductsFields('field_data_field_customer_questions', 'field_customer_questions_value', $nid, $row);
     $this->getProductsFields('field_data_field_demonstration', 'field_demonstration_value', $nid, $row);
     $this->getProductsFields('field_data_field_display_title', 'field_display_title_value', $nid, $row);
     $this->getProductsFields('field_data_field_end_date', 'field_end_date_value', $nid, $row);
     $this->getProductsFields('field_data_field_story', 'field_story_value', $nid, $row);
     $this->getProductsFields('field_data_field_subtitle', 'field_subtitle_value', $nid, $row);
     $this->getProductsFields('field_data_field_why_there_s_only_one', 'field_why_there_s_only_one_value', $nid, $row);


     $this->getProductsReferenceFields('field_data_field_learning_category', 'field_learning_category_tid', $nid, $row);
     $this->getProductsReferenceFields('field_data_field_product_categories', 'field_product_categories_tid', $nid, $row);
     $this->getProductsReferenceFields('field_data_field_season', 'field_season_tid', $nid, $row);
     $this->getProductsReferenceFields('field_data_field_tags_keywords', 'field_tags_keywords_tid', $nid, $row);

     $this->getProductsReferenceFields('field_data_field_fun_fact_sheet', 'field_fun_fact_sheet_fid', $nid, $row);

     $this->getProductsReferenceFields('field_data_field_product_image', 'field_product_image_fid', $nid, $row);

     $this->getProductsReferenceFields('field_data_field_just_for_you', 'field_just_for_you_target_id', $nid, $row);
     $this->getProductsReferenceFields('field_data_field_perfect_partners', 'field_perfect_partners_target_id', $nid, $row);
     $this->getProductsReferenceFields('field_data_field_related_products', 'field_related_products_target_id', $nid, $row);

     $this->getMarketFields('og_membership', 'gid', 'node', $nid, $row);
     $stick_date = $row->getSourceProperty('field_end_date_value');
     $stick_date = explode(" ", $stick_date[0]);
     $row->setSourceProperty('field_end_date_value', $stick_date[0]);
     $prod_image = $row->getSourceProperty('field_product_image_fid');
     $fact_sheet = $row->getSourceProperty('field_fun_fact_sheet_fid');

     // Get files destination id.
     $destination_ids = $this->getMigratedDestinationId($prod_image);

     $node = Node::load($nid);
     if (!empty($node)) {
       if (!empty($destination_ids[0]->destid1)) {
         $fid = $destination_ids[0]->destid1;
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
         $node->field_field_product_image = $field_image;
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

   protected function getProductsFields($table_name, $field_name, $nid, $row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.entity_id', $nid, '=')
       ->condition('tn.bundle', 'product_detail', '=');
     $result = $query->execute()->fetchAll();
     foreach ($result as $record) {
       $product_fields[] = $record[$field_name];
     }
     $row->setSourceProperty($field_name, $product_fields);
     return $row;
   }

   protected function getProductsReferenceFields($table_name, $field_name, $nid, $row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.entity_id', $nid, '=')
       ->condition('tn.bundle', 'product_detail', '=')
       ->condition('tn.entity_type', 'node', '=');
     $result = $query->execute()->fetchAll();
     foreach ($result as $record) {
       $product_fields[] = $record[$field_name];
     }
     $row->setSourceProperty($field_name, $product_fields);
     return $row;
   }

   protected function getMarketFields($table_name, $field_name, $type, $nid, $row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.etid', $nid, '=')
       ->condition('tn.entity_type', $type, '=');
     $result = $query->execute()->fetchAll();
     foreach ($result as $record) {
       $user_market[] = $record[$field_name];
     }
     $row->setSourceProperty('products_market', $user_market);
     return $row;
   }

 }

?>
