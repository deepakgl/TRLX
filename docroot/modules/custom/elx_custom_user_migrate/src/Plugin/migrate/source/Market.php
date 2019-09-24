<?php
namespace Drupal\elx_custom_user_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for market content.
 *
 * @MigrateSource(
 *   id = "custom_market"
 * )
 */
 class Market extends SqlBase {

   /**
    * {@inheritdoc}
    */
   public function query() {
     $query = $this->select('node', 'n')
       ->fields('n')
       ->condition('n.type', 'market', '=')
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
     $title = $row->getSourceProperty('title');
     return parent::prepareRow($row);
   }

 }

?>
