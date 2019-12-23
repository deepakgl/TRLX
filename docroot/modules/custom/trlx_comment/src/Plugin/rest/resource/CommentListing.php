<?php

namespace Drupal\trlx_comment\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_comment\Utility\CommentUtility;
use Drupal\Component\Serialization\Json;

/**
 * Provides a comments listing resource.
 *
 * @RestResource(
 *   id = "comments_listing",
 *   label = @Translation("Comments Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/commentsListing"
 *   }
 * )
 */
class CommentListing extends ResourceBase {

  /**
   * Rest resource for listing of comments.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $commentUtility = new CommentUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
      'nid',
    ];

    // Check for required parameters.
    $missingParams = [];
    foreach ($requiredParams as $param) {
      $$param = $request->query->get($param);
      if (empty($$param)) {
        $missingParams[] = $param;
      }
    }

    // Report missing required parameters.
    if (!empty($missingParams)) {
      return $commonUtility->invalidData($missingParams);
    }

    // Checkfor valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Check for valid node id.
    if (empty($commonUtility->isValidNid($nid))) {
      return $commonUtility->errorResponse($this->t('Node id does not exist.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    $response = $commentUtility->getComments($nid, TRUE);
    $commentsArray = $this->getNestedComments($response);
    // Pagination code.
    if (!isset($offset)) {
      $offset = 0;
    }
    if (!isset($limit)) {
      $offset = 10;
    }
    $slicedCommentArray = array_slice($commentsArray, $offset, $limit);
    $pager = [];
    $pagerCount = count($commentsArray) - $offset;
    $pages = ceil($pagerCount / $limit);
    $pager['count'] = $pagerCount;
    $pager['pages'] = $pages;
    $pager['items_per_page'] = (int) $limit;
    $pager['current_page'] = 0;
    $pager['next_page'] = ($pages > 1) ? 1 : 0;
    if (!empty($slicedCommentArray)) {
      return $commonUtility->successResponse($slicedCommentArray, Response::HTTP_OK, $pager);
    }
    else {
      return $commonUtility->successResponse($slicedCommentArray, Response::HTTP_OK);
    }
  }

  /**
   * To get comments nested array.
   *
   * @param mixed $response
   *   Mixed comments data.
   *
   * @return array
   *   Nested comment data.
   */
  public function getNestedComments($response) {
    $comments = [];
    $comment_ids = array_column($response, 'id');
    $i = 0;
    foreach ($response as $comment) {
      $comments[$i]['commentId'] = (int) $comment->id;
      $comments[$i]['parentId'] = (int) $comment->pid;
      $comments[$i]['userId'] = $comment->user_id;
      $comments[$i]['commentTime'] = (int) $comment->comment_timestamp;
      $comments[$i]['comment'] = $comment->comment_body;
      $comments[$i]['comment_edit_flag'] = ($comment->comment_edit_flag == 1) ? TRUE : FALSE;
      $comments[$i]['tags'] = !empty($comment->comment_tags) ? Json::decode($comment->comment_tags) : [];
      $comments[$i]['language'] = $comment->langcode;
      $i++;
    }
    // Group main comments and their replies.
    $grouped = [];
    foreach ($comments as $comment) {
      if (!isset($grouped[$comment['parentId']])) {
        $grouped[$comment['parentId']] = [];
      }
      $grouped[$comment['parentId']][] = $comment;
    }
    // Nested array with main comment and its replies.
    $nestedArray = [];
    foreach ($grouped[0] as $key => $top_level_comment) {
      $nestedArray[$key] = $top_level_comment;
      if (array_key_exists($top_level_comment['commentId'], $grouped)) {
        $nestedArray[$key]['replies'] = $grouped[$top_level_comment['commentId']];
      }
    }
    return $nestedArray;
  }

}
