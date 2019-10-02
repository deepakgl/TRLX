<?php

namespace Drupal\trlx_utility\Commands;

use Drush\Commands\DrushCommands;
use MongoDB;
use Drupal\taxonomy\Entity\Term;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class OtmMigrationCommands extends DrushCommands {
  /**
   * Echos back hello with the argument provided.
   *
   * @param string $vocab
   *   Argument provided to the drush command.
   *
   * @command otmdata:region
   * @aliases otm-region
   */
  public function region($vocab) {
  	$path = DRUPAL_ROOT . '/modules/custom/trlx_utility/json/otm-qa-region-brand-master-dump.json';
  	$strJsonFileContents = file_get_contents($path);
  	$string = str_replace('}}','}},',$strJsonFileContents);
  	$regions = preg_split('/}},/', $string,NULL,PREG_SPLIT_NO_EMPTY);
  	foreach ($regions as $key => $bson) {
  		if(strlen($bson) > 1 ) {
  			$bson1 = $bson . "}}";
  			$value = MongoDB\BSON\fromJSON($bson1);
  			$value1 = MongoDB\BSON\toPHP($value);
  			//$this->output()->writeln('Hello ' . $value1->region_name . '!');
  			$term = \Drupal::entityTypeManager()
  				->getStorage('taxonomy_term')
  				->loadByProperties([
  					'name' => $value1->region_name,
  					'vid' => 'markets'
  				]);
  			file_put_contents(DRUPAL_ROOT. '/sites/default/files/terms.txt', $value1->region_name.'##',FILE_APPEND);
  			if (!empty($term)) {
  				$tid = key($term);
  				$term[$tid]->set('field_region_subreg_country_id',$value1->_id);
  				$term[$tid]->save();
				$this->output()->writeln($term[$tid]->getName());
  			} else {
  				Term::create(array(
    			 'parent' => array(),
    			 'name' => $value1->region_name,
    			 'vid' => 'markets',
    			 'field_region_subreg_country_id' => $value1->_id
  				))->save();
  				$this->output()->writeln($value1->region_name);
  			}
  		}
  	}
  }


  /**
   * Echos back hello with the argument provided.
   *
   * @param string $vocab
   *   Argument provided to the drush command.
   *
   * @command otmdata:subregion
   * @aliases otm-subregion
   */
  public function subregion($vocab) {
  	$path = DRUPAL_ROOT . '/modules/custom/trlx_utility/json/otm-qa-region-subregion-master-dump.json';
  	$strJsonFileContents = file_get_contents($path);
  	$string = str_replace('}}','}},',$strJsonFileContents);
  	$regions = preg_split('/}},/', $string,NULL,PREG_SPLIT_NO_EMPTY);
  	foreach ($regions as $key => $bson) {
  		if(strlen($bson) > 1 ) {
  			$bson1 = $bson . "}}";
  			$value = MongoDB\BSON\fromJSON($bson1);
  			$value1 = MongoDB\BSON\toPHP($value);
  			if (!empty($value1)) {
  				$parent = $term = \Drupal::entityTypeManager()
  					->getStorage('taxonomy_term')
  					->loadByProperties([
  						'field_region_subreg_country_id' => $value1->_id,
  						'vid' => 'markets'
  					]);
  				$parentId = key($parent);
  				foreach ($value1->subregions as $sub) {
  					file_put_contents(DRUPAL_ROOT. '/sites/default/files/terms.txt', $value1->region_name.'##',FILE_APPEND);
  					$term = \Drupal::entityTypeManager()
  						->getStorage('taxonomy_term')
  						->loadByProperties([
  							'name' => $sub->name,
  							'vid' => 'markets'
  						]);
  					if (!empty($term)) {
  						$tid = key($term);
  						$term[$tid]->set('field_region_subreg_country_id',$sub->code);
  						$term[$tid]->set('parent', $parentId);
  						$term[$tid]->save();
  						$this->output()->writeln($term[$tid]->getName());
  					} else {
  						Term::create(array(
    			 			'parent' => $parentId,
    			 			'name' => $sub->name,
    			 			'vid' => 'markets',
    			 			'field_region_subreg_country_id' => $sub->code
  							))->save();
  						$this->output()->writeln($parentId);
  					}
  				}
  			}
  		}
  	}
  }

  /**
   * Echos back hello with the argument provided.
   *
   * @param string $vocab
   *   Argument provided to the drush command.
   *
   * @command otmdata:country
   * @aliases otm-country
   */
  public function country($vocab) {
  	$path = DRUPAL_ROOT . '/modules/custom/trlx_utility/json/otm-qa-subregion-country-master-dump.json';
  	$strJsonFileContents = file_get_contents($path);
  	$string = str_replace('}}','}},',$strJsonFileContents);
  	$regions = preg_split('/}},/', $string,NULL,PREG_SPLIT_NO_EMPTY);
  	foreach ($regions as $key => $bson) {
  		if(strlen($bson) > 1 ) {
  			$bson1 = $bson . "}}";
  			$value = MongoDB\BSON\fromJSON($bson1);
  			$value1 = MongoDB\BSON\toPHP($value);
  			if (!empty($value1)) {
  				$parent = $term = \Drupal::entityTypeManager()
  					->getStorage('taxonomy_term')
  					->loadByProperties([
  						'field_region_subreg_country_id' => $value1->_id,
  						'vid' => 'markets'
  					]);
  				$parentId = key($parent);
  				foreach ($value1->countries as $country) {
  					file_put_contents(DRUPAL_ROOT. '/sites/default/files/terms.txt', $value1->region_name.'##',FILE_APPEND);
  					$term = \Drupal::entityTypeManager()
  						->getStorage('taxonomy_term')
  						->loadByProperties([
  							'name' => $country->name,
  							'vid' => 'markets'
  						]);
  					if (!empty($term)) {
  						$tid = key($term);
  						$term[$tid]->set('field_region_subreg_country_id',$country->code);
  						$term[$tid]->set('parent', $parentId);
  						$term[$tid]->save();
  						$this->output()->writeln($term[$tid]->getName());
  					} else {
  						Term::create(array(
    			 			'parent' => $parentId,
    			 			'name' => $country->name,
    			 			'vid' => 'markets',
    			 			'field_region_subreg_country_id' => $country->code
  							))->save();
  						$this->output()->writeln($parentId);
  					}
  				}
  			}
  		}
  	}
  }


  /**
   * Echos back hello with the argument provided.
   *
   * @param string $vocab
   *   Argument provided to the drush command.
   *
   * @command otmdata:brand
   * @aliases otm-brand
   */
  public function brand($vocab) {
  	$path = DRUPAL_ROOT . '/modules/custom/trlx_utility/json/otm-qa-region-brand-master-dump.json';
  	$strJsonFileContents = file_get_contents($path);
  	$string = str_replace('}}','}},',$strJsonFileContents);
  	$regions = preg_split('/}},/', $string,NULL,PREG_SPLIT_NO_EMPTY);
  	foreach ($regions as $key => $bson) {
  		if(strlen($bson) > 1 ) {
  			$bson1 = $bson . "}}";
  			$value = MongoDB\BSON\fromJSON($bson1);
  			$value1 = MongoDB\BSON\toPHP($value);
  			if (!empty($value1)) {
  				foreach ($value1->divisions as $division) {
  					$term = \Drupal::entityTypeManager()
  						->getStorage('taxonomy_term')
  						->loadByProperties([
  							'name' => $division->name,
  							'vid' => 'brands'
  						]);
  					if (!empty($term)) {
  						$tid = key($term);
  						$term[$tid]->set('field_brand_key',$division->code);
  						$term[$tid]->save();
  						$this->output()->writeln($term[$tid]->getName());
  					} else {
  						Term::create(array(
    			 			'parent' => array(),
    			 			'name' => $division->name,
    			 			'vid' => 'brands',
    			 			'field_brand_key' => $division->code
  							))->save();
  						$this->output()->writeln($division->name);
  					}
  				}
  			}
  		}
  	}
  }


  /**
   * Echos back hello with the argument provided.
   *
   * @param string $vocab
   *   Argument provided to the drush command.
   *
   * @command otmdata:cleanup
   * @aliases otm-clean
   */
  public function cleanup($vocab) {
  	$path = DRUPAL_ROOT. '/sites/default/files/terms.txt';
  	$strJsonFileContents = file_get_contents($path);
  	$terms = explode('##', $strJsonFileContents);
  	$search_array = array_map('strtolower', $terms);
  	$migratedterms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('markets');
  	$a = array();
  	foreach ($migratedterms as $term) {
		if(!in_array(strtolower($term->name), $search_array)) {
  			$a[] = $term->name;
  		}
  	}

  	print_r($a);exit;
  }

}