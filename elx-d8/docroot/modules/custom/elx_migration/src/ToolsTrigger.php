<?php

namespace Drupal\elx_migration;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

class ToolsTrigger {

  /**
   * Batch operation.
   * This is the function that is called on each operation in batch.
   */
  public static function start($id, $operation_details, &$context) {
    $language_name = \Drupal::languageManager()->getLanguage($id->language);
    $translated_nid = $id->nid;
    $nid = '';
    $check_tnid = getTranslatedContentMapping($translated_nid, $language_name->getName());
    $context['results']['total'][] = $id->nid;
    if ($id->tnid > 0) {
      $nid = getMigratedDestinationId('migrate_map_custom_products', $id->tnid);
    }
    $translated_fields = self::buildFields($translated_nid, $id);
    $elx_content_type = $translated_fields['content_type'];
    if ($elx_content_type == $id->legacy_content_type) {
      unset($translated_fields['content_type']);
      if (!empty($nid)) {
        $node_obj = Node::load($nid);
        setTranslatedContentMapping($translated_nid, $nid, $language_name->getName(), 'translated', $elx_content_type);
        if ($node_obj && !$node_obj->hasTranslation($id->language)) {
          $entity_array = $node_obj->toArray();
          $translated_entity_array = array_merge($entity_array, $translated_fields);
          $node_obj->addTranslation($id->language, $translated_entity_array)->save();
          $context['results']['processed'][] = $nid;
          $context['message'] = t('Running Batch "@id" @details',
          ['@id' => $nid, '@details' => $operation_details]);
        }
        elseif ($node_obj && $node_obj->hasTranslation($id->language)) {
          $node_translation = $node->getTranslation($id->language);
          $node_translation->field_featured_image = $translated_fields['field_featured_image'];
          $node_translation->field_tool_thumbnail = $translated_fields['field_tool_thumbnail'];
          if ($elx_content_type == 'tools') {
            $node_translation->field_tool_pdf = $translated_fields['field_tool_pdf'];
          }
          elseif ($elx_content_type == 'tools-pdf') {
            $node_translation->field_tool_media_pdf = $translated_fields['field_tool_media_pdf'];
          }
          $node->save();
          $context['results']['processed'][] = $nid;
          $context['message'] = t('Running Batch "@id" @details',
          ['@id' => $nid, '@details' => $operation_details]);
        }
      }
      elseif (empty($check_tnid) && empty($nid)) {
        $node = Node::create(['type' => $elx_content_type]);
        foreach ($translated_fields as $key => $translated_field) {
          $node->set($key, $translated_field);
        }
        $node->set('langcode', $id->language);
        $node->set('uid', 1);
        $node->status = $id->status;
        $node->enforceIsNew();
        $node->save();
        setTranslatedContentMapping($translated_nid, $node->id(), $language_name->getName(), 'created', $elx_content_type);
        $context['results']['processed'][] = $translated_nid;
        $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $translated_nid, '@details' => $operation_details]);
      }
      elseif (!empty($check_tnid)) {
        $node = Node::load($check_tnid);
        if ($node && $node->hasTranslation($id->language)) {
          $node_translation = $node->getTranslation($id->language);
          $node_translation->field_featured_image = $translated_fields['field_featured_image'];
          $node_translation->field_tool_thumbnail = $translated_fields['field_tool_thumbnail'];
          if ($elx_content_type == 'tools') {
            $node_translation->field_tool_pdf = $translated_fields['field_tool_pdf'];
          }
          elseif ($elx_content_type == 'tools-pdf') {
            $node_translation->field_tool_media_pdf = $translated_fields['field_tool_media_pdf'];
          }
          $node->save();
          $context['results']['processed'][] = $check_tnid;
          $context['message'] = t('Running Batch "@id" @details',
          ['@id' => $check_tnid, '@details' => $operation_details]);
        }
      }
    }
  }

  /**
   * Batch 'finished' callback.
   */
  function finished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('Total results @count', ['@count' => count($results['total'])]));
      $messenger->addMessage(t('@count results processed.', ['@count' => count($results['processed'])]));
      $messenger->addMessage(t('The final result was "%final"', ['%final' => end($results)]));
    }
    else {
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  public static function buildFields($translated_nid, $id) {
    $translated_fields = [];
    $translated_fields['uid'] = 1;
    $translated_fields['title'] = $id->title;

    $translated_fields['field_tool_description'] = getFields('field_data_field_tool_description', 'field_tool_description_value', $translated_nid, $id->type);

    $translated_fields['field_featured'] = getFields('field_data_field_featured', 'field_featured_value', $translated_nid, $id->type);

    $translated_fields['field_access_by_role'] = getTermFields('field_data_field_tool_by_role', 'field_tool_by_role_tid', $translated_nid, $id->type);

    $translated_fields['field_markets'] = getMarketFields('og_membership', 'gid', 'node', $translated_nid);

    $translated_fields['field_featured_on_elx_specialty'] = getFields('field_data_field_featured_on_elx_specialty', 'field_featured_on_elx_specialty_value', $translated_nid, $id->type);

    $translated_fields['field_point_value'] = getFields('field_data_field_point_value', 'field_point_value_value', $translated_nid, $id->type);

    $translated_fields['field_display_title'] = getFields('field_data_field_display_title', 'field_display_title_value', $translated_nid, $id->type);

    $translated_fields['field_headline'] = getFields('field_data_field_headline', 'field_headline_value', $translated_nid, $id->type);

    $featured_image = getFields('field_data_field_featured_image', 'field_featured_image_fid', $translated_nid, $id->type);

    $thumbnail = getFields('field_data_field_tool_thumbnail', 'field_tool_thumbnail_fid', $translated_nid, $id->type);

    $fid = getFields('field_data_field_tool_pdf', 'field_tool_pdf_fid', $translated_nid, $id->type);

    if (!empty($featured_image)) {
      $tools_featured_image = getMigratedDestinationId('migrate_map_custom_file', $featured_image);
      if (!empty($tools_featured_image)) {
        $media = Media::create([
          'bundle' => 'image',
          'uid' => 1,
          'langcode' => $id->language,
          'status' => '1',
          'field_media_image' => [
            'target_id' => $tools_featured_image,
          ],
        ]);
        $media->save();
        $media_id = $media->id();
        $translated_fields['field_featured_image'] = $media_id;
      }
    }

    if (!empty($thumbnail)) {
      $tools_thumbnail = getMigratedDestinationId('migrate_map_custom_file', $thumbnail);
      if (!empty($tools_thumbnail)) {
        $media = Media::create([
          'bundle' => 'image',
          'uid' => 1,
          'langcode' => $id->language,
          'status' => '1',
          'field_media_image' => [
            'target_id' => $tools_thumbnail,
          ],
        ]);
        $media->save();
        $media_id = $media->id();
        $translated_fields['field_tool_thumbnail'] = $media_id;
      }
    }
    $translated_fields['content_type'] = 'tools-pdf';
    if (!empty($fid)) {
      $tools_fid = getMigratedDestinationId('migrate_map_custom_file', $fid);
      if (!empty($tools_fid)) {
        $file_type = getFileType('file_managed', 'type', $fid);
        if ($file_type == 'video') {
          $video_category_id = getFileType('field_data_field_video_category', 'field_video_category_tid', $fid, 'entity_id');
          if (!empty($video_category_id)) {
            $video_category_dest_id = getMigratedDestinationId('migrate_map_d7_taxonomy_term__videos', $video_category_id);
            $translated_fields['field_video_category'] = $video_category_dest_id;
          }
          $media = Media::create([
            'bundle' => 'video',
            'uid' => 1,
            'langcode' => $id->language,
            'status' => '1',
            'field_media_video_file' => [
              'target_id' => $tools_fid,
            ],
          ]);
          $media->save();
          $media_id = $media->id();
          $translated_fields['field_tool_pdf'] = $media_id;
          $translated_fields['content_type'] = 'tools';
        }
        elseif ($id->legacy_content_type == 'tools-pdf') {
          $media = Media::create([
            'bundle' => 'file',
            'uid' => 1,
            'langcode' => $id->language,
            'status' => '1',
            'field_media_file' => [
              'target_id' => $tools_fid,
            ],
          ]);
          $media->save();
          $media_id = $media->id();
          $translated_fields['field_tool_media_pdf'] = $media_id;
        }
      }
    }

    return $translated_fields;
  }

}
