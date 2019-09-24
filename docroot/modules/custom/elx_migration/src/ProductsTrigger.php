<?php

namespace Drupal\elx_migration;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

class ProductsTrigger {

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
    if (!empty($nid)) {
      $node = Node::load($nid);
      setTranslatedContentMapping($translated_nid, $nid, $language_name->getName(), 'translated', $id->type);
      if ($node && !$node->hasTranslation($id->language)) {
        $entity_array = $node->toArray();
        $translated_entity_array = array_merge($entity_array, $translated_fields);
        $node->addTranslation($id->language, $translated_entity_array)->save();
        $context['results']['processed'][] = $nid;
        $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $nid, '@details' => $operation_details]);
      }
      elseif ($node && $node->hasTranslation($id->language)) {
        $node_translation = $node->getTranslation($id->language);
        $node_translation->field_field_product_image = $translated_fields['field_field_product_image'];
        $node->save();
        $context['results']['processed'][] = $nid;
        $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $nid, '@details' => $operation_details]);
      }
    }
    elseif (empty($check_tnid) && empty($nid)) {
      $node = Node::create(['type' => $id->type]);
      foreach ($translated_fields as $key => $translated_field) {
        $node->set($key, $translated_field);
      }
      $node->set('langcode', $id->language);
      $node->set('uid', 1);
      $node->status = $id->status;
      $node->enforceIsNew();
      $node->save();
      setTranslatedContentMapping($translated_nid, $node->id(), $language_name->getName(), 'created', $id->type);
      $context['results']['processed'][] = $translated_nid;
      $context['message'] = t('Running Batch "@id" @details',
      ['@id' => $translated_nid, '@details' => $operation_details]);
    }
    elseif (!empty($check_tnid)) {
      $node = Node::load($check_tnid);
      if ($node && $node->hasTranslation($id->language)) {
        $node_translation = $node->getTranslation($id->language);
        $node_translation->field_field_product_image = $translated_fields['field_field_product_image'];
        $node->save();
        $context['results']['processed'][] = $check_tnid;
        $context['message'] = t('Running Batch "@id" @details',
        ['@id' => $check_tnid, '@details' => $operation_details]);
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

    $translated_fields['field_benefits'] = getFields('field_data_field_benefits', 'field_benefits_value', $translated_nid, $id->type);

    $translated_fields['field_if_she_asks_share'] = getFields('field_data_field_if_she_asks_share', 'field_if_she_asks_share_value', $translated_nid, $id->type);

    $translated_fields['field_markets'] = getMarketFields('og_membership', 'gid', 'node', $translated_nid);

    $translated_fields['field_product_categories'] = getTermFields('field_data_field_product_categories', 'field_product_categories_tid', $translated_nid, $id->type);

    $translated_fields['field_season'] = getTermFields('field_data_field_season', 'field_season_tid', $translated_nid, $id->type);

    $translated_fields['field_perfect_partners_text'] = getFields('field_data_field_perfect_partners_text', 'field_perfect_partners_text_value', $translated_nid, $id->type);

    $translated_fields['field_point_value'] = getFields('field_data_field_point_value', 'field_point_value_value', $translated_nid, $id->type);

    $translated_fields['field_price'] =
    getFields('field_data_field_price', 'field_price_value', $translated_nid, 'product_detail');

    $translated_fields['field_customer_questions'] =
    getFields('field_data_field_customer_questions', 'field_customer_questions_value', $translated_nid, $id->type);

    $translated_fields['field_demonstration'] =
    getFields('field_data_field_demonstration', 'field_demonstration_value', $translated_nid, $id->type);

    $translated_fields['field_display_title'] =
    getFields('field_data_field_display_title','field_display_title_value', $translated_nid, $id->type);

    $translated_fields['field_end_date'] =
    getFields('field_data_field_end_date', 'field_end_date_value', $translated_nid, $id->type);

    $translated_fields['field_story'] =
    getFields('field_data_field_story', 'field_story_value', $translated_nid, $id->type);

    $translated_fields['field_subtitle'] =
    getFields('field_data_field_subtitle', 'field_subtitle_value', $translated_nid, $id->type);

    $translated_fields['field_why_there_s_only_one'] =
    getFields('field_data_field_why_there_s_only_one', 'field_why_there_s_only_one_value', $translated_nid, $id->type);

    $fid = getFields('field_data_field_product_image', 'field_product_image_fid', $translated_nid, $id->type);
    if (!empty($fid)) {
      $product_fid = getMigratedDestinationId('migrate_map_custom_file', $fid);
      if (!empty($product_fid)) {
        $media = Media::create([
          'bundle' => 'image',
          'uid' => 1,
          'langcode' => $id->language,
          'status' => '1',
          'field_media_image' => [
            'target_id' => $product_fid,
          ],
        ]);
        $media->save();
        $media_id = $media->id();
        $translated_fields['field_field_product_image'] = $media_id;
      }
    }

    return $translated_fields;
  }

}
