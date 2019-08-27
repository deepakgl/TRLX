<?php

namespace Drupal\elx_dashboard\Utility;

use Drupal\elx_utility\Utility\CommonUtility;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\file\Entity\File;

/**
 * Purpose of this class is to build dashboard object.
 */
class DashboardUtility {

  /**
   * Load menu by name.
   *
   * @param string $name
   *   Menu name.
   * @param string $flag
   *   Flag name.
   * @param string $version
   *   Version name.
   *
   * @return array
   *   Menu response.
   */
  public function getMenuByName($name, $flag, $version = NULL) {
    $common_utility = new CommonUtility();
    $menu_item = \Drupal::menuTree()->load($name, new MenuTreeParameters());
    $i = 0;
    foreach ($menu_item as $item) {
      if ($item->link->isEnabled()) {
        $uuid = $item->link->getDerivativeId();
        if (!empty($uuid)) {
          $entity = \Drupal::service('entity.repository')
            ->loadEntityByUuid('menu_link_content', $uuid);
          $tid = $entity->getFields()['elx_menu_content']->getString();
          $term_name = $common_utility->getTermName($tid);
          $fid = $entity->link->first()->options['menu_icon']['fid'];
        }
        $type = 'internal';
        $url = $icon_path = '';
        if ($item->link->getUrlObject()->isExternal()) {
          $type = 'external';
          $url = $item->link->getUrlObject()->toString();
        }
        if (!empty($fid)) {
          $file = File::load($fid);
          if (!empty($file)) {
            $icon_path = file_create_url($file->getFileUri());
          }
        }
        $options = $item->link->getUrlObject()->getOptions();
        // Response array for navigation, social and privacy menu.
        if ($flag == 'navigation' || $flag == 'privacy' || $flag == 'social') {
          $menu_result[$i] = [
            'sequenceId' => intval($item->link->getWeight()),
            'content' => $term_name,
            'name' => $item->link->getTitle(),
            'URL' => $url,
            'type' => $type,
            'attributes' => isset($options['attributes']) ? $options['attributes'] : "",
          ];
        }
        // Response array for header menu.
        elseif ($flag == 'header') {
          $menu_result[$i] = [
            'sequenceId' => intval($item->link->getWeight()),
            'content' => str_replace('/', '', $item->link->getUrlObject()->toString()),
            'name' => $item->link->getTitle(),
          ];
        }
        // Add aditional key for social menu.
        if ($flag == 'social') {
          $menu_result[$i]['iconImage'] = $icon_path;
        }
        // Add aditional key for navigation menu.
        if ($version == 'v2') {
          $menu_result[$i]['web'] = (int) $entity->getFields()['elx_menu_web']
            ->getString();
          $menu_result[$i]['otg'] = (int) $entity->getFields()['elx_menu_otg']
            ->getString();
          $menu_result[$i]['otgSequenceId'] = (int) $entity
            ->getFields()['elx_menu_otg_sequence_id']->getString();
          $menu_result[$i]['iconURL'] = $icon_path;
        }
      }
      $i++;
    }

    return $menu_result;
  }

}
