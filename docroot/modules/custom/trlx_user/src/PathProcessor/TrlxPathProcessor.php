<?php

namespace Drupal\trlx_user\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MyModulePathProcessor.
 */
class TrlxPathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (($path == 'dashboard/published') || ($path == 'dashboard/unpublished'))  {
      unset($options['query']['destination']);
    }
    return $path;
  }

}