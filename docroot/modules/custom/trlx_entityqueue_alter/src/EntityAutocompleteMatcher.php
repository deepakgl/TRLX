<?php

namespace Drupal\trlx_entityqueue_alter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;

/**
 * Entity auto complete matcher.
 */

class EntityAutocompleteMatcher extends \Drupal\Core\Entity\EntityAutocompleteMatcher {

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = [];
    $options = [
      'target_type'      => $target_type,
      'handler'          => $selection_handler,
      'handler_settings' => $selection_settings,
    ];
    $referer = \Drupal::request()->headers->get('referer');
    $market_id = explode('?market=', $referer);
    preg_match('#\((.*?)\)#', $market_id[1], $match);
    $handler = $this->selectionManager->getInstance($options);
    $refereral = explode('admin/structure/', $referer);
    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);
      $modules_id = $this->checkIfEntityReferenced($match[1]);
      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          if ($target_type == 'node' && !empty($match[1]) &&
           (strpos($refereral[1], '_market_wise') !== FALSE)) {
            if (in_array($entity_id, $modules_id)) {
              $matches[] = $this->prepareRow($entity_id, $target_type, $label);
            }
          }
          else {
            $matches[] = $this->prepareRow($entity_id, $target_type, $label);
          }
        }
      }
    }

    return $matches;
  }

  /**
   * Get node id attached to Referenced node.
   */
  public function checkIfEntityReferenced($market_id) {
    $type = ['product_detail', 'level_interactive_content', 'stories', 'tools'];
    $query = db_select('node_field_data', 'nd');
    $query->fields('nd', ['nid']);
    $query->join('node__field_markets', 'nm', 'nm.entity_id = nd.nid');
    $query->condition('nd.type', $type, 'IN');
    $query->condition('nm.field_markets_target_id', $market_id);
    $result = $query->execute()->fetchAll();
    $results = array_column($result, 'nid');

    return $results;
  }

  /**
   * Prepare response for the autocomplete search.
   *
   * @param int $entity_id
   *   Id of the entity.
   * @param string $target_type
   *   Type of entity.
   * @param string $label
   *   Label of the entity.
   *
   * @return array
   *   Prepare autocomplete output.
   */
  public function prepareRow($entity_id, $target_type, $label) {
    $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
    $entity = \Drupal::entityManager()->getTranslationFromContext($entity);
    $type = !empty($entity->type->entity) ? $entity->type->entity->label() : $entity->bundle();
    $status = '';
    if ($entity->getEntityType()->id() == 'node') {
      $status = ($entity->isPublished()) ? ", Published" : ", Unpublished";
    }
    $key = $label . ' (' . $entity_id . ')';
    // Strip things like starting/trailing white spaces, line breaks and tags.
    $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
    // Names containing commas or quotes must be wrapped in quotes.
    $key = Tags::encode($key);
    $label = $label . ' (' . $entity_id . ') [' . $type . $status . ']';
    $matches = ['value' => $key, 'label' => $label];

    return $matches;
  }

}
