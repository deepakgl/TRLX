<?php

namespace Drupal\trlx_drush\Utility;
use Drupal\taxonomy\Entity\Term;

/**
 * Purpose of this class is to build learning levels object.
 */
class DrushUtility {

  /**
   * Process Terms markets
   *
   * @param array $terms
   *   Terms
   * @param string $type
   *   Type of term
   * @param string $case
   *   Case
   *
   * @return string
   *   String Message
   */
  public function processTerms($terms, $type, $case) {
    $result = [];
    if (isset($terms) && $type === 'markets') {
      foreach ($terms as $term) {

        $term_data = \Drupal::entityTypeManager()
         ->getStorage('taxonomy_term')
         ->loadByProperties([
           'name' => $term->name,
           'vid' => $type,
         ]);

        if ($case === 'country') {
          $parent_term_id = $this->getParentTermId($term->subregionId, $type);
        }
        elseif ($case === 'subregion'){
          $parent_term_id = $this->getParentTermId($term->regionId, $type);
        }

        if (!empty($term_data)) {
          $term_data = array_shift($term_data);
          $field_region_key = $term_data->get('field_region_subreg_country_id')->value;
          if ($field_region_key !== $term->id) {
            $term_data->set('field_region_subreg_country_id', $term->id);
          }

          if (!empty($parent_term_id)) {
            $term_data->parent = ['target_id' => $parent_term_id];
          }
          // Term Save
          if ($term_data->save()) {
            \Drupal::logger('trlx_drush')->info('Market term ' . $term_data->id() . ' updated successfully...');
          }
        }
        else {
          // Add term.
          $term_new = Term::create([
            'name' => $term->name,
            'vid' => $type,
            'field_region_subreg_country_id' => $term->id,
            'parent' => ['target_id' => $parent_term_id],
          ]);

          if ($term_new->save()) {
            \Drupal::logger('trlx_drush')->info('Market term created successfully...');
          }
        }
      }

      return 'SubRegion terms successfully processed.';
    }
  }

  /**
   * Fetch Parent Term Id
   *
   * @param integer $region_id
   *   Region key
   * @param string $type
   *   Type of term
   *
   * @return int
   *   Entity Id
   */
  public function getParentTermId($region_id, $type) {
    $database = \Drupal::database();
    $query = $database->select('taxonomy_term__field_region_subreg_country_id', 'n');
    $query->condition('n.bundle', $type, '=');
    $query->condition('n.field_region_subreg_country_id_value', $region_id, '=');
    $query->fields('n', ['entity_id']);
    $results = $query->execute()->fetchAllAssoc('entity_id');

    if (!empty($results)) {
      return array_shift($results)->entity_id;
    }
    else {
      return '';
    }
  }

  /**
   * Process Brand Terms
   *
   * @param array $termsArr
   *   Terms Array
   * @param string $type
   *   Type of term
   * @param string $termField
   *   Field machine name
   *
   * @return boolean
   *   Boolean true or false
   */
  public function processBrandTerms(array $termsArr = [], string $type = '', string $termField = '') {
    $results = [];
    $termsProcessed = FALSE;
    if (!empty($termsArr) && $type && $termField) {
      foreach ($termsArr as $termObj) {
        // Load term.
        $term = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'name' => $termObj->name,
            'vid' => $type,
          ]);

        // Update term field value in existing term.
        if (!empty($term)) {
          $tid = key($term);
          $term[$tid]->set($termField, $termObj->id);
          $term[$tid]->save();
          $results[] = $tid;
        }
        else {
          // Add term.
          $termCeated = Term::create([
            'parent' => [],
            'name' => $termObj->name,
            'vid' => $type,
            $termField => $termObj->id,
          ])->save();
          if ($termCeated) {
             $results[] = $termObj->name;
          }
        }
      }
    }
    if (!empty($results)) {
      $termsProcessed = TRUE;
    }

    return $termsProcessed;
  }

}
