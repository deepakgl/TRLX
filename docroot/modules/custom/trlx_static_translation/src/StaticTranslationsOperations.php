<?php

namespace Drupal\trlx_static_translation;

use Drupal\taxonomy\Entity\Term;

/**
 * Bulk mail to user.
 */
class StaticTranslationsOperations {

  /**
   * Batch operation.
   *
   * This is the function that is called on each operation in batch.
   */
  public static function import(array $val, $operation_details, &$context) {
    $entitytype_manager = \Drupal::service('entity_type.manager');
    $storageTerm = $entitytype_manager->getStorage('taxonomy_term');

    $term = $storageTerm->loadByProperties(['name' => $val['name']]);
    $term = reset($term);
    if (empty($term)) {
      $term = Term::create([
        'parent' => [],
        'name' => $val['name'],
        'field_translation_key' => $val['string_translation'],
        'vid' => 'static_translation',
      ]);
      $term->save();
    }
    elseif ($term && !$term->hasTranslation($val['language'])) {
      $entity_array = $term->toArray();
      $translated_fields = [];
      $translated_fields['name'] = $val['name'];
      $translated_fields['string_translation'] = $val['string_translation'];
      $translated_entity_array = array_merge($entity_array, $translated_fields);
      $translated_entity_array['field_translation_key'][0]['value'] = $val['string_translation'];
      $term->addTranslation($val['language'], $translated_entity_array)->save();
    }
    $context['results']['processed'][] = $val['name'];
    $context['message'] = t('Running Batch "@id" @details',
    ['@id' => $val['name'], '@details' => $operation_details]);
  }

  /**
   * Batch 'finished' callback.
   */
  public function finishedCallback($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
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

}
