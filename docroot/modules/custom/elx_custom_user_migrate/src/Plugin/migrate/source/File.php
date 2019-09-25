<?php

/**
 * @file
 * Contains \Drupal\elx_custom_user_migrate\Plugin\migrate\source.
 */
namespace Drupal\elx_custom_user_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Extract files from database.
 *
 * @MigrateSource(
 *   id = "custom_file"
 * )
 */
class File extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $prod_image_query = $this->select('field_data_field_product_image', 'fpm')
      ->fields('fpm', ['field_product_image_fid'])
      ->condition('fpm.language', 'en', '=');
    $prod_image_results = $prod_image_query->execute()->fetchAll();
    $prod_image_files = array_column($prod_image_results, 'field_product_image_fid');

    $prod_file_query = $this->select('field_data_field_fun_fact_sheet', 'ffs')
      ->distinct()
      ->fields('ffs', ['field_fun_fact_sheet_fid'])
      ->condition('ffs.language', 'en', '=');
    $prod_file_results = $prod_file_query->execute()->fetchAll();
    $prod_file_fid = array_column($prod_file_results, 'field_fun_fact_sheet_fid');

    $files = array_merge($prod_image_files, $prod_file_fid);

    $prod_user_file_query = $this->select('users', 'u')
      ->distinct()
      ->fields('u', ['picture']);
    $prod_user_file_results = $prod_user_file_query->execute()->fetchAll();
    $prod_user_file_fid = array_column($prod_user_file_results, 'picture');
    $files = array_merge($files, $prod_user_file_fid);

    $home_image_bs_query = $this->select('field_data_field_image_bs_t3', 'fib')
      ->distinct()
      ->fields('fib', ['field_image_bs_t3_fid']);
    $home_image_bs_query_results = $home_image_bs_query->execute()->fetchAll();
    $home_image_bs_fid = array_column($home_image_bs_query_results, 'field_image_bs_t3_fid');
    $files = array_merge($files, $home_image_bs_fid);

    $video_query = $this->select('field_data_field_video_bs_t2', 'fib')
      ->distinct()
      ->fields('fib', ['field_video_bs_t2_fid']);
    $video_query_results = $video_query->execute()->fetchAll();
    $video_query_fid = array_column($video_query_results, 'field_video_bs_t2_fid');
    $files = array_merge($files, $video_query_fid);

    $image_query = $this->select('field_data_field_image_bs_t2', 'fib')
      ->distinct()
      ->fields('fib', ['field_image_bs_t2_fid']);
    $image_query_results = $image_query->execute()->fetchAll();
    $image_query_fid = array_column($image_query_results, 'field_image_bs_t2_fid');
    $files = array_merge($files, $image_query_fid);

    $main_image_query = $this->select('field_data_field_main_image', 'fib')
      ->distinct()
      ->fields('fib', ['field_main_image_fid']);
    $main_image_results = $main_image_query->execute()->fetchAll();
    $main_image_fid = array_column($main_image_results, 'field_main_image_fid');
    $files = array_merge($files, $main_image_fid);

    $tools_file = $this->select('field_data_field_tool_pdf', 'ftp');
    $tools_file->addJoin('left', 'file_managed', 'fm', 'fm.fid = ftp.field_tool_pdf_fid');
    $tools_file->addField('ftp', 'field_tool_pdf_fid');
    $tools_file->condition('fm.type', 'video', '!=');
    $tools_file_results = $tools_file->execute()->fetchAll();
    $tools_file_fid = array_column($tools_file_results, 'field_tool_pdf_fid');
    $files = array_merge($files, $tools_file_fid);

    $tool_thumbnail_query = $this->select('field_data_field_tool_thumbnail', 'ftt')
      ->distinct()
      ->fields('ftt', ['field_tool_thumbnail_fid']);
    $tool_thumbnail_results = $tool_thumbnail_query->execute()->fetchAll();
    $tool_thumbnail_fid = array_column($tool_thumbnail_results, 'field_tool_thumbnail_fid');
    $files = array_merge($files, $tool_thumbnail_fid);

    $featured_image_query = $this->select('field_data_field_featured_image', 'ffm')
      ->distinct()
      ->fields('ffm', ['field_featured_image_fid']);
    $featured_image_results = $featured_image_query->execute()->fetchAll();
    $featured_image_fid = array_column($featured_image_results, 'field_featured_image_fid');
    $files = array_merge($files, $featured_image_fid);

    $featured_best_seller_image = $this->select('field_data_field_image_home_page', 'ffm')
      ->distinct()
      ->fields('ffm', ['field_image_home_page_fid']);
    $featured_best_seller_image_results = $featured_best_seller_image->execute()->fetchAll();
    $featured_best_seller_image_fid = array_column($featured_best_seller_image_results, 'field_image_home_page_fid');
    $files = array_merge($files, $featured_best_seller_image_fid);
    $query = $this->select('file_managed', 'f')
      ->fields('f', [])
      ->condition('f.fid', $files, 'IN');
    $results = $query->execute()->fetchAll();

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields['fid'] = $this->t('File ID');
    $fields['filename'] = $this->t('File name');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'fid' => array(
        'type' => 'integer',
        'alias' => 'f',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get filename property value.
    $filename = str_replace(" ", "%20", $row->getSourceProperty('filename'));
    // Get file uri property value.
    $file_uri = end(array_filter(explode('/', $row->getSourceProperty('uri'))));
    $file_uri = str_replace(" ", "%20", $file_uri);
    $row->setSourceProperty('full_path', 'public://migration-files/' . $file_uri);
    $row->setSourceProperty('file_name', 'public://' . $file_uri);

    return parent::prepareRow($row);
  }

}
