<?php

namespace Drupal\elx_utility\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for serialized output formats with pager.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer_with_pager",
 *   title = @Translation("Serializer with pager"),
 *   help = @Translation("Serializes views row data using the Serializer component with pager."),
 *   display_types = {"data"}
 * )
 */
class SerializerWithPager extends Serializer {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];
    $count = $this->view->pager->getTotalItems();
    $items_per_page = $this->view->pager->options['items_per_page'];
    $pages = ceil($count / $items_per_page);
    $current_page = $this->view->pager->getCurrentPage() ? $this->view->pager->getCurrentPage() : 0;
    $next_page = $current_page + 1;
    if ($next_page == $pages || $pages == 0) {
      $next_page = 0;
    }
    // If the data entity row plugin is used, this will be an array of entities
    // which will pass through serializer to one of the registered normalizers,
    // which will transform it to arrays/scalars. If the data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);
    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }
    $results = [
      'results' => $rows,
    ];
    // Add pager information to the rows.
    $results['pager'] = [
      'count' => $count,
      'pages' => $pages,
      'items_per_page' => $items_per_page,
      'current_page' => $current_page,
      'next_page' => $next_page,
    ];

    return $this->serializer->serialize($results, $content_type, ['views_style_plugin' => $this]);
  }

}
