<?php

namespace Drupal\trlx_comment\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\trlx_comment\Utility\CommentUtility;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $commentId;

  /**
   * Language of the item to delete.
   *
   * @var string
   */
  protected $commentLangCode;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $commentId = NULL, string $commentLangCode = NULL) {
    $this->commentId = $commentId;
    $this->commentLangCode = $commentLangCode;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->commentUtility = new CommentUtility();
    // Delete comment in db.
    $this->commentUtility->deleteComment($this->commentId, $this->commentLangCode);

    \Drupal::messenger()->addStatus('Comment deleted successfully.');
    $url = Url::fromRoute('view.comment_dashboard.page_1');
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.comment_dashboard.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete comment %id?', ['%id' => $this->commentId]);
  }

}
