<?php

namespace Drupal\trlx_comment\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_comment\Utility\CommentUtility;
use Symfony\Component\HttpFoundation\Response;

/**
 * Helps to delete comment from database.
 *
 * @RestResource(
 *   id = "comment_delete",
 *   label = @Translation("Comment Delete API"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/comment/delete/{language}/{commentId}"
 *   }
 * )
 */
class DeleteComment extends ResourceBase {

  /**
   * Save comment data in database.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Brands category listing.
   */
  public function delete(Request $request) {
    global $_userData;
    $uri_array = explode('/', $request->getpathInfo());
    $this->commonUtility = new CommonUtility();
    $this->commentUtility = new CommentUtility();
    $_format = $request->get('_format');
    // Check for valid _format type.
    $response = $this->commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }
    $comment_id = (int) $uri_array[6];
    if (isset($comment_id) && !empty($comment_id)) {
      $response = $this->commonUtility->validatePositiveValue($comment_id);
      if (!($response->getStatusCode() === Response::HTTP_OK)) {
        return $response;
      }
    }
    else {
      return $this->commonUtility->errorResponse($this->t('Comment id is required.'), Response::HTTP_BAD_REQUEST);
    }

    if (isset($uri_array[5]) && !empty($uri_array[5])) {
      // Checkfor valid language code.
      $response = $this->commonUtility->validateLanguageCode($uri_array[5], $request, TRUE);
      if (!($response->getStatusCode() === Response::HTTP_OK)) {
        return $response;
      }
    }
    else {
      return $this->commonUtility->errorResponse($this->t('Language code is required.'), Response::HTTP_BAD_REQUEST);
    }
    $comment_lang_code = $uri_array[5];

    // Check comment id valid or not.
    $validated_comment = $this->commentUtility->validateCommentId($comment_id, $comment_lang_code);
    if (empty($validated_comment)) {
      return $this->commonUtility->errorResponse($this->t('Please add valid comment id or language code.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    // Check whether user is deleting own comment.
    if (!empty($validated_comment)) {
      if ($validated_comment[0]->user_id != $_userData->userId) {
        return $this->commonUtility->errorResponse($this->t('You are not allowed to delete the comment.'), Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }
    // Delete comment in db.
    $this->commentUtility->deleteComment($comment_id, $comment_lang_code);

    // Generate response.
    $response = [
      "commentId" => $comment_id,
      "language" => $comment_lang_code,
      "message" => $this->t("Comment successfully deleted."),
    ];

    return $this->commonUtility->successResponse($response, Response::HTTP_OK);
  }

}
