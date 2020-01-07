<?php

namespace Drupal\trlx_comment\Utility;

use Drupal\Component\Serialization\Json;
use Elasticsearch\ClientBuilder;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * Purpose of this class is to build common object.
 */
class CommentUtility {

  const DEFAULT_LANGUAGE = 'en';

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

    $langcode = !empty($data['language']) ? $data['language'] : self::DEFAULT_LANGUAGE;

    $query = \Drupal::database();
    $result = $query->insert('trlx_comment')
      ->fields([
        'user_id',
        'entity_id',
        'pid',
        'comment_body',
        'comment_tags',
        'langcode',
        'comment_timestamp',
        'comment_update_timestamp',
      ])
      ->values([
        'user_id' => $_userData->userId,
        'entity_id' => $data['nid'],
        'pid' => $data['parentId'],
        'comment_body' => $data['comment'],
        'comment_tags' => !empty($data['tags']) ? Json::encode($data['tags']) : '',
        'langcode' => $langcode,
        'comment_timestamp' => REQUEST_TIME,
        'comment_update_timestamp' => REQUEST_TIME,
      ])
      ->execute();

    // Push data for notification(s).
    if (!empty($data['tags']) && !empty($result)) {
      // Update id with real user id.
      $data['tags'] = $this->updateTags($data['tags'], FALSE, TRUE);
      // Prepare notification data.
      $notification_index = trlx_notification_comment_user_tags($data['nid'], $langcode, $data['tags']);
      // Send data to the queue.
      save_data_in_queue($notification_index);
    }

    return TRUE;
  }

  /**
   * To save edited comment data in database.
   *
   * @param array $data
   *   Comment data to be inserted in database.
   *
   * @return bool
   *   True or false.
   */
  public function saveEditedComment(array $data) {
    global $_userData;

    $langcode = !empty($data['language']) ? $data['language'] : self::DEFAULT_LANGUAGE;

    $query = \Drupal::database();
    $result = $query->update('trlx_comment')
      ->fields([
        'comment_body' => $data['comment'],
        'comment_tags' => !empty($data['tags']) ? Json::encode($data['tags']) : '',
        'comment_edit_flag' => 1,
        'comment_update_timestamp' => REQUEST_TIME,
      ])
      ->condition('id', $data['commentId'], '=')
      ->condition('pid', $data['parentId'], '=')
      ->condition('langcode', $langcode, '=')
      ->condition('entity_id', $data['nid'], '=')
      ->execute();
    // Push data for notification(s).
    if (!empty($data['tags']) && !empty($result)) {
      // Update id with real user id.
      $data['tags'] = $this->updateTags($data['tags'], FALSE, TRUE);
      // Prepare notification data.
      $notification_index = trlx_notification_comment_user_tags($data['nid'], $langcode, $data['tags']);
      // Send data to the queue.
      save_data_in_queue($notification_index);
    }

    return TRUE;
  }

  /**
   * To get latest edited comment from database.
   *
   * @return mixed
   *   Latest comment data.
   */
  public function getLatestEditedComment() {

    try {
      $query = \Drupal::database();
      $result = $query->select('trlx_comment', 'tc')
        ->fields('tc', [
          'id',
          'user_id',
          'entity_id',
          'pid',
          'comment_body',
          'comment_tags',
          'langcode',
          'comment_timestamp',
          'comment_update_timestamp',
        ])
        ->orderBy('tc.comment_update_timestamp', 'DESC')->range(0, 1)
        ->execute()->fetch();
    }
    catch (\Exception $e) {
      $result = [];
    }

    if (!empty($result)) {
      $commonUtility = new CommonUtility();
      // Update tags data from elastic.
      $result->user_id = $commonUtility->getExternalUserId($result->user_id);
      $result->comment_tags = empty($result->comment_tags) ? [] : $result->comment_tags;
      $result->langcode = empty($result->langcode) ? self::DEFAULT_LANGUAGE : $result->langcode;
      // Unset variable.
      unset($commonUtility);
    }

    return $result;
  }

  /**
   * To get latest comment from database.
   *
   * @return mixed
   *   Latest comment data.
   */
  public function getLatestComment() {

    try {
      $query = \Drupal::database();
      $result = $query->select('trlx_comment', 'tc')
        ->fields('tc', [
          'id',
          'user_id',
          'entity_id',
          'pid',
          'comment_body',
          'comment_tags',
          'langcode',
          'comment_timestamp',
        ])
        ->orderBy('tc.comment_timestamp', 'DESC')->range(0, 1)
        ->execute()->fetch();
    }
    catch (\Exception $e) {
      $result = [];
    }

    if (!empty($result)) {
      $commonUtility = new CommonUtility();
      // Update tags data from elastic.
      $result->user_id = $commonUtility->getExternalUserId($result->user_id);
      $result->comment_tags = empty($result->comment_tags) ? [] : $result->comment_tags;
      $result->langcode = empty($result->langcode) ? self::DEFAULT_LANGUAGE : $result->langcode;
      // Unset variable.
      unset($commonUtility);
    }

    return $result;
  }

  /**
   * To get comments on selected node from database.
   *
   * @param int $nid
   *   Node id.
   * @param bool $updateTags
   *   Flag to update tag keys.
   *
   * @return mixed
   *   Comment data.
   */
  public function getComments($nid, $updateTags = FALSE) {

    try {
      $query = \Drupal::database();
      $result = $query->select('trlx_comment', 'tc')
        ->fields('tc', [
          'id',
          'user_id',
          'entity_id',
          'pid',
          'comment_body',
          'comment_tags',
          'langcode',
          'comment_edit_flag',
          'comment_timestamp',
        ])
        ->condition('tc.entity_id', $nid, '=')
        ->orderBy('tc.comment_timestamp', 'DESC')
        ->execute()->fetchAll();
    }
    catch (\Exception $e) {
      $result = [];
    }

    if (!empty($result)) {
      $commonUtility = new CommonUtility();

      foreach ($result as $comment) {
        // Fetch external user id.
        $comment->user_id = $commonUtility->getExternalUserId($comment->user_id);
        $comment->comment_tags = empty($comment->comment_tags) ? [] : $comment->comment_tags;
        $comment->langcode = empty($comment->langcode) ? self::DEFAULT_LANGUAGE : $comment->langcode;

        // Update tags data from elastic.
        if ($updateTags) {
          $comment->comment_tags = $this->updateTags($comment->comment_tags);
        }
      }
      // Unset variable.
      unset($commonUtility);
    }

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
    try {
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
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Update user data in tags.
   *
   * @param array $tags
   *   Tagged user data.
   * @param bool $decode
   *   Boolean to decide whether to decode tags.
   * @param bool $updateIdOnly
   *   Flag to only update 'id' key in tags.
   *
   * @return array
   *   Array of comment tags.
   */
  public function updateTags($tags = [], $decode = TRUE, $updateIdOnly = FALSE) {
    if (!empty($tags)) {

      // Decode tags.
      if ($decode) {
        $tags = Json::decode($tags, TRUE);
      }

      $commonUtility = new CommonUtility();

      if (empty($updateIdOnly)) {
        $client = self::getElasticClient();
      }

      foreach ($tags as $delta => $tag) {
        // Fetch user real id referenced in drupal with otm system id.
        $userId = $externalUserId = $tag['id'];
        if (!is_numeric($tag['id'])) {
          $userId = $commonUtility->getUserRealId($tag['id']);
        }

        if (empty($updateIdOnly)) {
          // Fetch user data from elastic.
          $elasticUserData = self::getElasticUserData($userId, $client);

          // Update user tag data.
          // First Name.
          $tags[$delta]['firstName'] = !empty($elasticUserData['_source']['firstName']) ? $elasticUserData['_source']['firstName'] : $tag['firstName'];
          // Last Name.
          $tags[$delta]['lastName'] = !empty($elasticUserData['_source']['lastName']) ? $elasticUserData['_source']['lastName'] : $tag['lastName'];
          // Email.
          $tags[$delta]['workEmailAddress'] = !empty($elasticUserData['_source']['email']) ? $elasticUserData['_source']['email'] : $tag['workEmailAddress'];
        }
        else {
          $tags[$delta]['id'] = $userId;
          $tags[$delta]['externalUserId'] = $externalUserId;
        }
      }

      // Unset variable.
      unset($commonUtility);

      // Encode tags if received encoded.
      if ($decode) {
        $tags = Json::encode($tags);
      }
    }

    return $tags;
  }

  /**
   * Get elastic client.
   *
   * @return object
   *   Elastic client.
   */
  public static function getElasticClient() {
    $config = \Drupal::config('trlx_notification.settings');
    $hosts = [
      [
        'host' => $config->get('host'),
        'port' => $config->get('port'),
        'scheme' => $config->get('scheme'),
      ],
    ];

    return (ClientBuilder::create()->setHosts($hosts)->build());
  }

  /**
   * Fetch user data from elastic.
   *
   * @param int $uid
   *   User id.
   * @param mixed $client
   *   Elastic client.
   *
   * @return array
   *   Elastic user data.
   */
  public static function getElasticUserData($uid, $client) {
    $config = \Drupal::config('trlx_notification.settings');
    $params = [
      'index' => $config->get('user_index'),
      'type' => 'user',
      'id' => 'user_' . $uid,
    ];
    try {
      $response = $client->get($params);
    }
    catch (\Exception $e) {
      return FALSE;
    }
    return $response;
  }

  /**
   * To validate passed comment id.
   *
   * @param int $comment_id
   *   Comment id.
   * @param int $comment_lang_code
   *   Comment lang code.
   *
   * @return mixed
   *   Comment data.
   */
  public function validateCommentId($comment_id, $comment_lang_code) {

    try {
      $query = \Drupal::database();
      $result = $query->select('trlx_comment', 'tc')
        ->fields('tc', [
          'id',
          'user_id',
          'entity_id',
          'pid',
          'comment_body',
          'comment_tags',
          'langcode',
          'comment_timestamp',
        ])
        ->condition('tc.id', $comment_id, '=')
        ->condition('tc.langcode', $comment_lang_code, '=')
        ->execute()->fetchAll();
    }
    catch (\Exception $e) {
      $result = [];
    }
    return $result;
  }

  /**
   * To delete comment.
   *
   * @param int $comment_id
   *   Comment id.
   * @param int $comment_lang_code
   *   Comment lang code.
   *
   * @return mixed
   *   Comment data.
   */
  public function deleteComment($comment_id, $comment_lang_code) {
    $response = $this->validateReplyCommentsExists($comment_id, $comment_lang_code);
    $query = \Drupal::database();
    $query->delete('trlx_comment')
      ->condition('trlx_comment.id', $comment_id, '=')
      ->condition('trlx_comment.langcode', $comment_lang_code, '=')
      ->execute();
    if (!empty($response)) {
      $reply_ids = array_column($response, 'id');
      $query->delete('trlx_comment')
        ->condition('trlx_comment.id', $reply_ids, 'IN')
        ->condition('trlx_comment.langcode', $comment_lang_code, '=')
        ->execute();
    }
  }

  /**
   * To validate reply comments exists or not.
   *
   * @param int $comment_id
   *   Comment id.
   * @param int $comment_lang_code
   *   Comment lang code.
   *
   * @return array
   *   Comment replies id.
   */
  public function validateReplyCommentsExists($comment_id, $comment_lang_code) {
    try {
      $query = \Drupal::database();
      $result = $query->select('trlx_comment', 'tc')
        ->fields('tc', [
          'id',
          'pid',
        ])
        ->condition('tc.pid', $comment_id, '=')
        ->condition('tc.langcode', $comment_lang_code, '=')
        ->execute()->fetchAll();
    }
    catch (\Exception $e) {
      $result = [];
    }
    return $result;
  }

}
