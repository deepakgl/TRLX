<?php

namespace Drupal\trlx_comment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\trlx_comment\Utility\CommentUtility;

/**
 * Controller to delete the comment.
 */
class DeleteCommentController extends ControllerBase {

  /**
   * Controller method to delete the comment.
   */
  public function delete($comment_id, $comment_lang_code) {
    $this->commentUtility = new CommentUtility();
    // Delete comment in db.
    $this->commentUtility->deleteComment($comment_id, $comment_lang_code);

    \Drupal::messenger()->addStatus('Comment deleted successfully.');
    return $this->redirect('view.comment_dashboard.page_1');
  }

}
