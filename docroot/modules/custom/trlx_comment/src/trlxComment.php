<?php

namespace Drupal\trlx_comment;

use Drupal\views\ViewExecutable;
use Drupal\views\ResultRow;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Contains entity getter method.
 */
class trlxComment {

  /**
   * The entity getter method.
   *
   * @param \Drupal\views\ResultRow $row
   *   Views result row.
   * @param string $relationship_id
   *   Id of the view relationship.
   * @param \Drupal\views\ViewExecutable $view
   *   The current view object.
   */
  public static function getEntityFromRow(ResultRow $row, $relationship_id, ViewExecutable $view) {
    $id = $row->id;
    $entity = \Drupal::entityTypeManager()->getStorage('trlx_comment')->load($id);
    return $entity;
  }

}
