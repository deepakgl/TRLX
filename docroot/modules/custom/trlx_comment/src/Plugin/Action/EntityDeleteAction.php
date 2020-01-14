<?php

namespace Drupal\trlx_comment\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\trlx_comment\Utility\CommentUtility;

/**
 * Delete comment action with default confirmation form.
 *
 * @Action(
 *   id = "views_bulk_operations_delete_comment",
 *   label = @Translation("Delete selected comments"),
 *   type = "trlx_comment",
 *   confirm = FALSE,
 * )
 */
class EntityDeleteAction extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $commentUtility = new CommentUtility();
    $commentUtility->deleteComment($entity->get('id')->first()->getValue()['value'], $entity->langcode);
    return $this->t('Delete comments');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

}
