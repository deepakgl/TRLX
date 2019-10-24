<?php

namespace Drupal\trlx_comment\Utility;

use Drupal\Component\Serialization\Json;
use Elasticsearch\ClientBuilder;

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
      ])
      ->values([
        'user_id' => $_userData->userId,
        'entity_id' => $data['nid'],
        'pid' => $data['parentId'],
        'comment_body' => $data['comment'],
        'comment_tags' => !empty($data['tags']) ? Json::encode($data['tags']) : '',
        'langcode' => $langcode,
        'comment_timestamp' => REQUEST_TIME,
      ])
      ->execute();

    if (!empty($data['tags'])) {
      $notification_index = trlx_notification_comment_user_tags($data['nid'], $langcode, $data['tags']);
      // Send data to the queue.
      save_data_in_queue($notification_index);
    }

    return TRUE;
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
    } catch (\Exception $e ) {
      $result = [];
    }

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
          'comment_timestamp',
        ])
        ->condition('tc.entity_id', $nid, '=')
        ->orderBy('tc.comment_timestamp', 'DESC')
        ->execute()->fetchAll();
    } catch (\Exception $e ) {
     $result = [];
    }

    if (!empty($result) && $updateTags) {
      foreach ($result as $comment) {
        $comment->comment_tags = $this->updateTags($comment->comment_tags);
      }
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
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Update user data in tags.
   *
   * @param array $tags
   *   Tagged user data.
   * @param boolean $decode
   *   Boolean to decide whether to decode tags.
   */
  public function updateTags($tags = [], $decode = TRUE) {
    if (!empty($tags)) {

      // Decode tags.
      if ($decode) {
        $tags = Json::decode($tags, TRUE);
      }

      $client = self::getElasticClient();
      foreach ($tags as $delta => $tag) {
        // Fetch user data from elastic.
        $elasticUserData = self::getElasticUserData($tag['id'], $client);

        // Update user tag data.
        // First Name.
        $tags[$delta]['firstName'] = !empty($elasticUserData['_source']['firstName']) ? $elasticUserData['_source']['firstName'] : $tag['firstName'];
        // Last Name.
        $tags[$delta]['lastName'] = !empty($elasticUserData['_source']['lastName']) ? $elasticUserData['_source']['lastName'] : $tag['lastName'];
        // Email.
        $tags[$delta]['workEmailAddress'] = !empty($elasticUserData['_source']['email']) ? $elasticUserData['_source']['email'] : $tag['workEmailAddress'];
      }

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
}
