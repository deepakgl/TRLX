<?php

namespace Drupal\trlx_comment\Utility;

/**
 * Purpose of this class is to build common object.
 */
class CommentUtility {

  /**
   * To save comment data in database.
   *
   * @param array $data
   *   Comment data to be inserted in database.
   *
   * @return bool
   *   True or false.
   */
  public function saveComment(array $data) {
    global $_userData;
    $query = \Drupal::database();
    $result = $query->insert('trlx_comment')
      ->fields([
        'user_id',
        'entity_id',
        'pid',
        'comment_body',
        'comment_timestamp',
      ])
      ->values([
        'user_id' => $_userData->userId,
        'entity_id' => $data['nid'],
        'pid' => $data['parentId'],
        'comment_body' => $data['comment'],
        'comment_timestamp' => REQUEST_TIME,
      ])
      ->execute();
    return TRUE;
  }

  /**
   * To get latest comment from database.
   *
   * @return mixed
   *   Latest comment data.
   */
  public function getLatestComment() {
    $query = \Drupal::database();
    $result = $query->select('trlx_comment', 'tc')
      ->fields('tc', [
        'id',
        'user_id',
        'entity_id',
        'pid',
        'comment_body',
        'comment_timestamp',
      ])
      ->orderBy('tc.comment_timestamp', 'DESC')->range(0, 1)
      ->execute()->fetch();
    return $result;
  }

  /**
   * To get comments on selected node from database.
   *
   * @param int $nid
   *   Node id.
   *
   * @return mixed
   *   Comment data.
   */
  public function getComments($nid) {
    $query = \Drupal::database();
    $result = $query->select('trlx_comment', 'tc')
      ->fields('tc', [
        'id',
        'user_id',
        'entity_id',
        'pid',
        'comment_body',
        'comment_timestamp',
      ])
      ->condition('tc.entity_id', $nid, '=')
      ->orderBy('tc.comment_timestamp', 'DESC')
      ->execute()->fetchAll();
    return $result;
  }

  /**
   * To get comments reply ids of the selected node.
   *
   * @param int $nid
   *   Node id.
   *
   * @return array
   *   Comment replies id.
   */
  public function getReplyCommentIds($nid) {
    $query = \Drupal::database();
    $result = $query->select('trlx_comment', 'tc')
      ->fields('tc', [
        'id',
        'pid',
      ])
      ->condition('tc.entity_id', $nid, '=')
      ->condition('tc.pid', '0', '!=')
      ->orderBy('tc.comment_timestamp', 'DESC')
      ->execute()->fetchAll();
    return array_column($result, 'id');
  }

}
