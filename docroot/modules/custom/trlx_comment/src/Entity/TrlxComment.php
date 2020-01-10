<?php

namespace Drupal\trlx_comment\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\trlx_comment\CommentRulesInterface;

/**
 * Defines the Plugin Rules entity.
 *
 * @ContentEntityType(
 *   id = "trlx_comment",
 *   label = @Translation("Trlx Comment"),
 *   handlers = {
 *     "access" = "Drupal\trlx_comment\Controller\TrlxCommentAccessControlHandler",
 *   },
 *   admin_permission = "administer trlx comment entity",
 *   base_table = "trlx_comment",
 *   entity_keys = {
 *     "id" = "id",
 *     "user_id" = "user_id",
 *     "entity_id" = "entity_id",
 *     "pid" ="pid",
 *     "comment_body" = "comment_body",
 *     "comment_timestamp" = "comment_timestamp",
 *     "comment_tags" = "comment_tags",
 *     "langcode" = "langcode",
 *     "comment_edit_flag" = "comment_edit_flagg",
 *     "comment_update_timestamp" = "comment_update_timestamp"
 *   },
 *   config_export = {
 *     "id",
 *     "user_id",
 *     "entity_id",
 *     "pid",
 *     "comment_body",
 *     "comment_timestamp",
 *     "comment_tags",
 *     "langcode",
 *     "comment_edit_flag",
 *     "comment_update_timestamp"
 *   }
 * )
 */
class TrlxComment extends ContentEntityBase implements CommentRulesInterface {

  /**
   * The Comment ID.
   *
   * @var int
   */
  public $id;

  /**
   * The User Id.
   *
   * @var int
   */
  public $user_id;

  /**
   * The Entity Id.
   *
   * @var int
   */
  public $entity_id;

  /**
   * The Parent Comment Id.
   *
   * @var int
   */
  public $pid;

  /**
   * The Comment Body.
   *
   * @var string
   */
  public $comment_body;

  /**
   * The Comment timestamp.
   *
   * @var int
   */
  public $comment_timestamp;

  /**
   * The Comment tags.
   *
   * @var string
   */
  public $comment_tags;

  /**
   * The Comment langcode.
   *
   * @var string
   */
  public $langcode;

  /**
   * The Comment edit flag.
   *
   * @var int
   */
  public $comment_edit_flag;

  /**
   * The Comment Updated timestamp.
   *
   * @var int
   */
  public $comment_update_timestamp;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    //$fields = parent::baseFieldDefinitions($entity_type);
    //$fields += static::ownerBaseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.yes
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Term entity.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Term entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('User ID'))
      ->setDescription(t('User ID'));

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('Entity ID'));

    $fields['pid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent ID'))
      ->setDescription(t('Parent ID'));

    $fields['comment_body'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Comment Body'))
      ->setDescription(t('Comment Body'));

    $fields['comment_timestamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Comment Timestamp'))
      ->setDescription(t('Comment Timestamp'));

    $fields['comment_tags'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Comment Tags'))
      ->setDescription(t('Comment Tags'));

    $fields['comment_edit_flag'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Comment edit flag'))
      ->setDescription(t('Comment edit flag'));

    $fields['comment_update_timestamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Comment Update Timestamp'))
      ->setDescription(t('Comment Update Timestamp'));

    return $fields;
  }

}
