<?php

namespace Drupal\trlx_utility\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\trlx_utility\Utility\CommonUtility;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("trlx_comment_content_type")
 */
class CommentContentTypeField extends FieldPluginBase {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity_id = $values->node_field_data_trlx_comment_nid;
    $langcode = $values->trlx_comment_langcode;
    $common_utility = new CommonUtility();
    $langcode = empty($langcode) ? 'en' : $langcode;
    $node_data = $common_utility->getNodeData($entity_id, $langcode);
    if ($node_data != FALSE) {
      return $node_data->type->entity->label();
    }
    else {
      return '';
    }
  }

}
