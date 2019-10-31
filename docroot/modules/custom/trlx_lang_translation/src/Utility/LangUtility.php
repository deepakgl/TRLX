<?php

namespace Drupal\trlx_lang_translation\Utility;

use Drupal\elx_user\Utility\UserUtility;
use PHPUnit\Exception;

/**
 * Purpose of this class is to build language object.
 */
class LangUtility {

  /**
   * Fetch String Translation.
   *
   * @param string $lang_code
   *   Language code.
   *
   * @return json
   *   String translation.
   */
  public function getStringTranslation($lang_code) {
    try {
      $translation = db_query(
        'SELECT source as sourceString,  translation
        as languageTranslation FROM {locales_target} lt
        INNER JOIN {locales_source} ls ON ls.lid = lt.lid
        WHERE lt.language = :langcode', [
          ':langcode' => $lang_code,
        ])->fetchAll();

      return $translation;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Fetch translation languages by nid.
   *
   * @param int $nid
   *   Node id.
   *
   * @return array
   *   Translation language.
   */
  public function getTranslationLanguageByNid($nid) {
    try {
      $query = db_select('node_field_data', 'n')
        ->fields('n', ['langcode'])
        ->condition('n.nid', $nid, '=')
        ->execute();
      $results = $query->fetchAll();
      $results = array_column($results, 'langcode');

      return $results;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Fetch primary and secondary language by market tid.
   *
   * @param int $market_id
   *   Market tid.
   * @param string $flag
   *   Flag name.
   *
   * @return array
   *   Market primary and secondary language.
   */
  public function getMarketPrimaryAndSecondaryLanguage($market_id, $flag = NULL) {
    try {
      $query = \Drupal::database();
      $query = $query->select('taxonomy_term__field_primary_language', 'pl');
      $query->distinct();
      $query->leftjoin('taxonomy_term__field_secondary_language',
        'sl', 'pl.entity_id = sl.entity_id');
      $query->fields('pl', ['field_primary_language_target_id']);
      $query->fields('sl', ['field_secondary_language_target_id']);
      $query->condition('pl.entity_id', $market_id, 'IN');
      $results = $query->execute()->fetchAll();
      if (!empty($flag)) {
        return $results;
      }
      $primary_lang = array_column(
        $results, 'field_primary_language_target_id');
      $secondary_lang = array_column(
        $results, 'field_secondary_language_target_id');
      $lang_code = array_unique(array_merge(
        $primary_lang, $secondary_lang));
      $response = [];
      foreach ($lang_code as $key => $value) {
        if (!empty($value)) {
          $language = \Drupal::languageManager()->getLanguage($value);
          $response[$value] = $language->getName();
        }
      }
      return $response;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Fetch Market languages by user id.
   *
   * @return array
   *   User primary and secondary language.
   */
  public function getMarketLanguageByUserId() {
    $config = \Drupal::config('trlx_utility.settings');
    // Return all languages for global admin role.
    $language = $config->get('site_languages');

    return $language;
  }

}
