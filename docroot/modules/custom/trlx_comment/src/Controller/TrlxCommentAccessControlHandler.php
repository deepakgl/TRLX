<?php

namespace Drupal\trlx_comment\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the PluginRules entity.
 *
 * @see \Drupal\trlx_comment\Entity\TrlxCommentAccessControlHandler.
 */
class TrlxCommentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view trlx comment entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit trlx comment entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete trlx comment entity');
    }
    return AccessResult::allowed();
  }

}