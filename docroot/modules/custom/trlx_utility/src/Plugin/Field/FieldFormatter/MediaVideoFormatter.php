<?php

namespace Drupal\trlx_utility\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileMediaFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'file_video' formatter.
 *
 * @FieldFormatter(
 *   id = "trlx_utility_video",
 *   label = @Translation("Media Video"),
 *   description = @Translation("Display the file using an HTML5 video tag."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class MediaVideoFormatter extends FileMediaFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'video';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'muted' => FALSE,
      'width' => 350,
      'height' => 200,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'muted' => [
        '#title' => $this->t('Muted'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('muted'),
      ],
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#default_value' => $this->getSetting('width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
        '#required' => TRUE,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#default_value' => $this->getSetting('height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Muted: %muted', ['%muted' => $this->getSetting('muted') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Size: %width x %height pixels', [
      '%width' => $this->getSetting('width'),
      '%height' => $this->getSetting('height'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAttributes(array $additional_attributes = []) {
    return parent::prepareAttributes(['muted'])
      ->setAttribute('width', $this->getSetting('width'))
      ->setAttribute('height', $this->getSetting('height'));
  }

   /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $source_files = parent::getSourceFiles($items, $langcode);
    if (empty($source_files)) {
      return $elements;
    }

    $attributes = $this->prepareAttributes();
    foreach ($source_files as $delta => $files) {
      $elements[$delta] = [
        '#theme' => parent::getPluginId(),
        '#attributes' => $attributes,
        '#files' => $files,
        '#cache' => ['tags' => []],
      ];

      $cache_tags = [];
      foreach ($files as $file) {
        $cache_tags = Cache::mergeTags($cache_tags, $file['file']->getCacheTags());
      }
      $elements[$delta]['#cache']['tags'] = $cache_tags;
    }
   // print_r($elements);exit;


    return $elements;
  }

}