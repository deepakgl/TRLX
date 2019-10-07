<?php

namespace Drupal\trlx_lang_translation\Utility;

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
    catch (Exception $e) {
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
    $query = db_select('node_field_data', 'n')
      ->fields('n', ['langcode'])
      ->condition('n.nid', $nid, '=')
      ->execute();
    $results = $query->fetchAll();
    $results = array_column($results, 'langcode');

    return $results;
  }

}
