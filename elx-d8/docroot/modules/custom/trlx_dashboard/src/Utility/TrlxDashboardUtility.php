<?php

namespace Drupal\trlx_dashboard\Utility;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\file\Entity\File;

/**
 * Purpose of this class is to build dashboard object.
 */
class TrlxDashboardUtility {

  /**
   * Load menu by name.
   *
   * @param string $name
   *   Menu name.
   * @param string $flag
   *   Flag name.
   * @param string $version
   *   Version name.
   * @param string $langcode
   *   Language code.
   *
   * @return array
   *   Menu result.
   */
  public function getMenuByName($name, $flag, $version = NULL, $langcode = 'en') {
    $menu_item = \Drupal::menuTree()->load($name, new MenuTreeParameters());
    $i = 0;
    foreach ($menu_item as $item) {
      if ($item->link->isEnabled()) {
        // Response array for navigation, social and privacy menu.
        if ($flag == 'navigation') {
          $menu_result[$i] = $this->createMenuArray($item->link, $langcode);
          if ($item->hasChildren) {
            foreach ($item->subtree as $subitem) {
              if ($subitem->link->isEnabled()) {
                $menu_result[$i]['secondaryNavigationMenu'][] = $this->createMenuArray($subitem->link, $langcode);
              }
            }
          }
        }
      }
      $i++;
    }

    return $menu_result;
  }

  /**
   * Create menu custom array.
   *
   * @param object $menu_item
   *   Menu data.
   * @param string $langcode
   *   Language code.
   *
   * @return array
   *   Menu array.
   */
  public function createMenuArray($menu_item, $langcode) {
    $uuid = $menu_item->getDerivativeId();
    if (!empty($uuid)) {
      $entity = \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $uuid);
      $fid = $entity->link->first()->options['menu_icon']['fid'];
      $type = 'internal';
      $url = $icon_path = '';
      if ($menu_item->getUrlObject()->isExternal()) {
        $type = 'external';
        $url = $menu_item->getUrlObject()->toString();
      }
      // Get menu icon path.
      if (!empty($fid)) {
        $file = File::load($fid);
        if (!empty($file)) {
          $icon_path = file_create_url($file->getFileUri());
        }
      }
      // Get link attributes.
      $options = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode)->getUrlObject()->getOptions() : $entity->getUrlObject()->getOptions();

      $menu_result = [
        'sequenceId' => $entity->hasTranslation($langcode) ? intval($entity->getTranslation($langcode)->getWeight()) : intval($entity->getWeight()),
        'name' => $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode)->getTitle() : $entity->getTitle(),
        'URL' => $url,
        'type' => $type,
        'attributes' => isset($options['attributes']) ? $options['attributes'] : "",
      ];
    }
    return $menu_result;
  }

}
