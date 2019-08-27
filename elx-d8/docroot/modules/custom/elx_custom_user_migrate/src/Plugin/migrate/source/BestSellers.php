<?php
namespace Drupal\elx_custom_user_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

/**
 * Source plugin for best seller content.
 *
 * @MigrateSource(
 *   id = "custom_best_sellers"
 * )
 */
 class BestSellers extends SqlBase {
   /**
    * {@inheritdoc}
    */
   public function query() {
     $query = $this->select('node', 'n')
       ->fields('n')
       ->condition('n.type', 'best_sellers', '=')
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
     $vid = $row->getSourceProperty('vid');
     $title = $row->getSourceProperty('title');

     $this->getBestSellerFields('field_data_field_sub_title', 'field_sub_title_value', $nid, $row);
     $this->getBestSellerFields('field_data_field_point_value', 'field_point_value_value', $nid, $row);
     $this->getBestSellerFields('field_data_field_best_seller_title', 'field_best_seller_title_value', $nid, $row);

     $this->getBestSellerReferenceFields('field_data_field_product_categories', 'field_product_categories_tid', $nid, $row);

     $this->getBestSellerReferenceFields('field_data_field_image_home_page', 'field_image_home_page_fid', $nid, $row);

     $prod_family = [
       'field_data_field_best_sellers_three',
       'field_best_sellers_three_value',
       'best_sellers',
       'field_data_field_slides_bs_t3',
       'field_data_field_product_family_description',
       'field_product_family_description_value',
       'product_family_paragraph',
       'field_data_field_image_bs_t3',
       'field_slides_bs_t3_value',
       'field_image_bs_t3_fid',
       ];
     $this->getParagraphsFields($nid, $row, $prod_family);

     $prod_tiles = [
       'field_data_field_paragraph_templates',
       'field_paragraph_templates_value',
       'best_sellers',
       'field_data_field_media_template_two',
       'field_media_template_two_value',
       'field_data_field_video_bs_t2',
       'field_video_bs_t2_fid',
       'best_sellers_tiles',
       'field_data_field_image_bs_t2',
       'field_image_bs_t2_fid',
       'field_data_field_title_bs_t2',
       'field_title_bs_t2_value',
       'best_sellers_template_two',
       'field_data_field_main_image',
       'field_main_image_fid',
       'field_data_field_content',
       'field_content_value'
       ];
     $this->getParagraphTilesFields($nid, $row, $prod_tiles);

     $prod_try_with = [
       'field_data_field_best_sellers_four',
       'field_best_sellers_four_value',
       'best_sellers',
       'field_data_field_slids_bs_t4',
       'field_slids_bs_t4_value',
       'field_data_field_image_first_bs_t4',
       'field_image_first_bs_t4_fid',
       'field_data_field_title_first_bs_t4',
       'field_title_first_bs_t4_value',
       'best_sellers_try_with'
     ];
     $this->getParagraphTryWithFields($nid, $row, $prod_try_with);
     $this->getMarketFields('og_membership', 'gid', 'node', $nid, $row);
     $home_image = $row->getSourceProperty('field_image_home_page_fid');
     // Get files destination id.
     $destination_ids = $this->getMigratedDestinationId($home_image);
     $node = Node::load($nid);
     if (!empty($node)) {
       if (!empty($destination_ids[0]->destid1)) {
        $fid = $destination_ids[0]->destid1;
        $media_id = $node->get('field_image_home_page')->getValue()[0]['target_id'];
        $mediaObj = $this->getMediaId('media_field_data', 'mid', $media_id);
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
        $node->field_image_home_page = $field_image;
        $node->save();
      }
     }
    return parent::prepareRow($row);
  }

   protected function getMigratedDestinationId($sourceid) {
     $query = \Drupal::database()->select('migrate_map_custom_file', 'mp');
     $query->fields('mp', ['destid1']);
     $query->condition('sourceid1', $sourceid, '=');
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

   protected function getBestSellerFields($table_name, $field_name, $nid, &$row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.entity_id', $nid, '=')
       ->condition('tn.bundle', 'best_sellers', '=');
     $result = $query->execute()->fetchAll();
     foreach ($result as $record) {
       $product_fields[] = $record[$field_name];
     }
     $row->setSourceProperty($field_name, $product_fields);
     return $row;
   }

   protected function getBestSellerReferenceFields($table_name, $field_name, $nid, &$row) {
     $query = $this->select($table_name, 'tn')
       ->fields('tn', [ $field_name ])
       ->condition('tn.entity_id', $nid, '=')
       ->condition('tn.bundle', 'best_sellers', '=')
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
     $row->setSourceProperty('best_sellers_market', $user_market);
     return $row;
   }

   protected function getParagraphsFields($nid, &$row, $prod_family) {
     $query = $this->select($prod_family[0], 'tn');
     $query->addJoin('left', $prod_family[3], 'jo', 'jo.entity_id = tn.' . $prod_family[1]);
     $query->addJoin('left', $prod_family[4], 'jo1', 'jo1.entity_id = jo.' . $prod_family[8]);
     $query->addJoin('left', $prod_family[7], 'jo2', 'jo2.entity_id = jo.' . $prod_family[8]);
     $query->addField('jo1', $prod_family[5]);
     $query->addField('jo2', $prod_family[9]);
     $query->condition('tn.entity_id', $nid, '=')
       ->condition('tn.bundle', $prod_family[2], '=');
     $results = $query->execute()->fetchAll();
     foreach ($results as $result) {
       $product_image[] = $result[$prod_family[9]];
       $product_family[] = $result;
     }
     $row->setSourceProperty('best_sellers_prod_image', $product_image);
     $row->setSourceProperty($prod_family[6], $product_family);
     return $row;
   }

   protected function getParagraphTilesFields($nid, &$row, $prod_tiles) {
     $query = $this->select($prod_tiles[0], 'tn');
     $query->addJoin('left', $prod_tiles[3], 'jo', 'jo.entity_id = tn.' . $prod_tiles[1]);
     $query->addJoin('left', $prod_tiles[5], 'jo1', 'jo1.entity_id = jo.' . $prod_tiles[4]);
     $query->addJoin('left', $prod_tiles[8], 'jo2', 'jo2.entity_id = jo.' . $prod_tiles[4]);
     $query->addJoin('left', $prod_tiles[10], 'jo3', 'jo3.entity_id = jo.' . $prod_tiles[4]);
     $query->addJoin('left', $prod_tiles[13], 'jo4', 'jo4.entity_id = tn.' . $prod_tiles[1]);
     $query->addJoin('left', $prod_tiles[15], 'jo5', 'jo5.entity_id = tn.' . $prod_tiles[1]);
     $query->addField('tn', $prod_tiles[1]);
     $query->addField('jo1', $prod_tiles[6]);
     $query->addField('jo2', $prod_tiles[9]);
     $query->addField('jo3', $prod_tiles[11]);
     $query->addField('jo4', $prod_tiles[14]);
     $query->addField('jo5', $prod_tiles[16]);
     $query->condition('tn.entity_id', $nid, '=');
     $query->condition('tn.bundle', $prod_tiles[2], '=');
     $query->condition('jo4.bundle', $prod_tiles[12], '=');
     $query->condition('jo5.bundle', $prod_tiles[12], '=');
     $results = $query->execute()->fetchAll();
     foreach ($results as $key => $result) {
      $tiles[$result['field_main_image_fid']]['field_main_image_fid'] = $result['field_main_image_fid'];
      $tiles[$result['field_main_image_fid']]['field_content_value'] = $result['field_content_value'];
      $tiles[$result['field_main_image_fid']]['media'][] = [
        'field_video_bs_t2_fid' => $result['field_video_bs_t2_fid'],
        'field_image_bs_t2_fid' => $result['field_image_bs_t2_fid'],
        'field_title_bs_t2_value' => $result['field_title_bs_t2_value']
     ];
     }
     $row->setSourceProperty($prod_tiles[7], $tiles);
     return $row;
   }

   protected function getParagraphTryWithFields($nid, &$row, $prod_try_with) {
     $query = $this->select($prod_try_with[0], 'tn');
     $query->addJoin('left', $prod_try_with[3], 'jo', 'jo.entity_id = tn.' . $prod_try_with[1]);
     $query->addJoin('left', $prod_try_with[5], 'jo1', 'jo1.entity_id = jo.' . $prod_try_with[4]);
     $query->addJoin('left', $prod_try_with[7], 'jo2', 'jo2.entity_id = jo.' . $prod_try_with[4]);
     $query->addField('jo1', $prod_try_with[6]);
     $query->addField('jo2', $prod_try_with[8]);
     $query->condition('tn.entity_id', $nid, '=');
     $query->condition('tn.bundle', $prod_try_with[2], '=');
     $results = $query->execute()->fetchAll();
     foreach ($results as $key => $result) {
       $try_with_it[] = $result;
     }
     $row->setSourceProperty($prod_try_with[9], $try_with_it);
     return $row;
   }

 }

?>
