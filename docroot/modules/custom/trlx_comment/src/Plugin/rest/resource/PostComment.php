<?php

namespace Drupal\trlx_comment\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_comment\Utility\CommentUtility;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;

/**
 * Helps to save comment in database.
 *
 * @RestResource(
 *   id = "comment_post",
 *   label = @Translation("Comment Post API"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/comment",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/comment"
 *   }
 * )
 */
class PostComment extends ResourceBase {

  /**
   * Save comment data in database.
   *
   * @param array $data
   *   Rest resource query parameters.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Brands category listing.
   */
  public function post(array $data, Request $request) {
    global $_userData;
    $this->commonUtility = new CommonUtility();
    $this->commentUtility = new CommentUtility();

    // Required parameters.
    $requiredParams = [
      'nid',
      'comment',
    ];
    $_format = $request->get('_format');
    // Check for valid _format type.
    $response = $this->commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    // Check for required parameters.
    $missingParams = [];
    foreach ($requiredParams as $param) {
      $$param = $data[$param];
      if (empty($$param)) {
        $missingParams[] = $param;
      }
    }

    // Report missing required parameters.
    if (!empty($missingParams)) {
      return $this->commonUtility->invalidData($missingParams);
    }
    if (isset($data['parentId'])) {
      $response = $this->commonUtility->validatePositiveValue($data['parentId']);
      if (!($response->getStatusCode() === Response::HTTP_OK)) {
        return $response;
      }
    }
    else {
      return $this->commonUtility->errorResponse($this->t('Parent id is required.'), Response::HTTP_BAD_REQUEST);
    }
    if (!isset($data['tags'])) {
      return $this->commonUtility->errorResponse($this->t('Tags is required.'), Response::HTTP_BAD_REQUEST);
    }
    if (isset($data['language'])) {
      // Checkfor valid language code.
      $response = $this->commonUtility->validateLanguageCode($data['language'], $request, TRUE);
      if (!($response->getStatusCode() === Response::HTTP_OK)) {
        return $response;
      }
    }

    // Check for valid node id.
    if (empty($this->commonUtility->isValidNid($nid))) {
      return $this->commonUtility->errorResponse($this->t('Node id does not exist.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    // Check parent id valid or not.
    $commentIds = array_column($this->commentUtility->getComments($nid), 'id');
    if (!in_array($data['parentId'], $commentIds) && $data['parentId'] != 0) {
      return $this->commonUtility->errorResponse($this->t('Please add valid parent comment id.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    // Check user not replying on existing reply.
    $replyIds = $this->commentUtility->getReplyCommentIds($nid);
    if (in_array($data['parentId'], $replyIds)) {
      return $this->commonUtility->errorResponse($this->t('Not allowed to reply on existing reply.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    // Save comment in db.
    $this->commentUtility->saveComment($data);
    $saved_data = $this->commentUtility->getLatestComment();

    // Generate response.
    $response = [
      "parentId" => (int) $saved_data->pid,
      "commentId" => (int) $saved_data->id,
      "comment" => $saved_data->comment_body,
      "tags" => !empty($saved_data->comment_tags) ? Json::decode($saved_data->comment_tags, TRUE) : [],
      "language" => $saved_data->langcode,
      "commentTime" => (int) $saved_data->comment_timestamp,
      "message" => $this->t("Comment successfully added."),
    ];

    return $this->commonUtility->successResponse($response, Response::HTTP_CREATED);
  }

}
