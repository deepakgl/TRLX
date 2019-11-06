<?php

namespace Drupal\trlx_consumer\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\trlx_utility\Utility\CommonUtility;
use Drupal\trlx_utility\Utility\EntityUtility;
use Drupal\trlx_utility\Utility\UserUtility;

/**
 * Provides a Consumer listing resource.
 *
 * @RestResource(
 *   id = "consumer_listing",
 *   label = @Translation("Consumer Listing"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/consumerListing"
 *   }
 * )
 */
class ConsumerListing extends ResourceBase {

  /**
   * Rest resource for listing Consumer content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Rest resource query parameters.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Resource response.
   */
  public function get(Request $request) {
    $commonUtility = new CommonUtility();
    $entityUtility = new EntityUtility();

    // Required parameters.
    $requiredParams = [
      '_format',
      'language',
      'categoryId',
    ];

    // Check for required parameters.
    $missingParams = [];
    foreach ($requiredParams as $param) {
      $$param = $request->query->get($param);
      if (empty($$param)) {
        $missingParams[] = $param;
      }
    }

    // Report missing required parameters.
    if (!empty($missingParams)) {
      return $commonUtility->invalidData($missingParams);
    }

    // Checkfor valid _format type.
    $response = $commonUtility->validateFormat($_format, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Checkfor valid language code.
    $response = $commonUtility->validateLanguageCode($language, $request);
    if (!($response->getStatusCode() === Response::HTTP_OK)) {
      return $response;
    }

    // Checkfor valid category id.
    if (empty($commonUtility->isValidTid($categoryId))) {
      return $commonUtility->errorResponse($this->t('Category id does not exist.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    list($limit, $offset, $errorResponse) = $commonUtility->getPagerParam($request);
    if (!empty($errorResponse)) {
      return $errorResponse;
    }

    // Fetch respective learning_category term(s).
    $term_results = $this->fetchConsumerLevels($commonUtility::CONSUMER, $language, $categoryId);

    // Fetch learning_category term response.
    /*list($term_view_results, $term_status_code) = $entityUtility->fetchApiResult(
    '',
    'consumer_categories',
    'rest_export_consumer_learning_level_listing',
    '',
    ['language' => $language, 'categoryId' => $categoryId]
    );*/

    // Fetch stories bundle content response.
    list($content_view_results, $term_status_code) = $entityUtility->fetchApiResult(
      '',
      'consumer',
      'rest_export_consumer_stories_listing',
      '',
      ['language' => $language, 'categoryId' => $categoryId]
    );

    // Intialize variables.
    $results = $view_results = [];
    $count = 0;

    if (empty($limit)) {
      $limit = 10;
    }

    if (empty($offset)) {
      $offset = 0;
    }

    // Fetch Learning Level Consumer term(s).
    if (!empty($term_results)) {
      $results = $term_results;
      $count = count($term_results);
    }

    // Fetch Stories Consumer Content(s).
    if (!empty($content_view_results['results'])) {
      $results = array_merge($results, $content_view_results['results']);
      $count = $count + count($content_view_results['results']);
    }

    if (!empty($results)) {
      // Sort merged array using "timestamp" key.
      usort($results, function ($timestamp1, $timestamp2) {
        return $timestamp2['timestamp'] <=> $timestamp1['timestamp'];
      });

      // Slice array as per passed limit & offset.
      $results = array_slice($results, $offset, $limit);
    }

    if (!empty($results)) {
      $pagerCount = round($count - $offset);
      $pages = round(($pagerCount / $limit), 0);
      $pager['count'] = $pagerCount;
      $pager['pages'] = $pages;
      $pager['items_per_page'] = $limit;
      $pager['current_page'] = 0;
      $pager['next_page'] = ($pages > 1) ? 1 : 0;

      // Set results, pager & status code.
      $view_results['results'] = $results;

      $data = [
        'id' => 'int',
        'displayTitle' => 'decode',
        'subTitle' => 'decode',
        'pointValue' => 'int',
        'pointValueLevel' => 'point_value_level',
        'body' => 'string_replace',
        'categoryId' => 'int',
      ];

      $view_results = $entityUtility->buildListingResponse($view_results, $data, [], ['timestamp', 'pointValueLevel']);

      $view_results['pager'] = $pager;
      $status_code = Response::HTTP_OK;
    }

    // Fetch consumer cateogory image.
    $categoryImage = [];
    list($category_image_view) = $entityUtility->fetchApiResult(
      '',
      'consumer_categories',
      'rest_export_consumer_category_image',
      '',
      ['categoryId' => $categoryId, 'language' => $language]
    );
    if (!empty($category_image_view)) {
      $categoryImage['categoryImage'] = $category_image_view;
    }

    // Check for empty / no result from views.
    if (empty($view_results) && empty($categoryImage)) {
      return $commonUtility->successResponse([], Response::HTTP_OK);
    }

    if (!empty($categoryImage) && empty($view_results)) {
      $status_code = Response::HTTP_OK;
      $view_results['results'] = [];
      $view_results['pager'] = "{}";
    }

    return $commonUtility->successResponse($view_results['results'], $status_code, $view_results['pager'], '', [], [], $categoryImage);
  }

  /**
   * Function to fetch consumer levels attached to content.
   *
   * @param string $sectionKey
   *   Section Key.
   * @param string $language
   *   Language code.
   * @param int $categoryId
   *   Consumer Category Term Id.
   *
   * @return array
   *   Array or levels data.
   */
  private function fetchConsumerLevels(string $sectionKey, string $language, int $categoryId) {
    global $_userData;
    $userUtility = new UserUtility();
    // Get current user markets.
    $markets = $userUtility->getMarketByUserData($_userData);
    unset($userUtility);

    // Database connection.
    $connection = \Drupal::database();

    // Tables.
    try {
      $query = db_select('taxonomy_term_field_data', 'tfd');
      $query->addJoin('', 'taxonomy_term__field_content_section', 'fcs', 'fcs.entity_id = tfd.tid');
      $query->addJoin('', 'taxonomy_term__field_sub_title', 'fst', 'fst.entity_id = tfd.tid');
      $query->addJoin('', 'taxonomy_term__field_content_section_key', 'fcsk', 'fcsk.entity_id = fcs.field_content_section_target_id');
      $query->addJoin('', 'taxonomy_term__field_consumer_category', 'fcc', 'fcc.entity_id = tfd.tid');
      $query->addJoin('', 'node__field_learning_category', 'flc', 'flc.field_learning_category_target_id = tfd.tid');
      $query->addJoin('', 'node_field_data', 'fd', 'fd.nid = flc.entity_id');
      $query->addJoin('', 'node__field_point_value', 'fpv', 'fpv.entity_id = fd.nid');
      $query->addJoin('LEFT', 'taxonomy_term__field_image', 'tfi', 'tfi.entity_id = tfd.tid');
      $query->addJoin('LEFT', 'media_field_data', 'mfd', 'mfd.mid = tfi.field_image_target_id');
      $query->addJoin('LEFT', 'media__field_media_image', 'mfmi', 'mfmi.entity_id = mfd.mid');
      $query->addJoin('LEFT', 'file_managed', 'fm', 'fm.fid = mfmi.field_media_image_target_id');
      if (!empty($markets)) {
        $query->addJoin('', 'node__field_markets', 'nfm', 'nfm.entity_id = fd.nid');
      }

      // Conditions.
      // Learning level vocabulary.
      $query->condition('tfd.vid', 'learning_category');
      $query->condition('tfd.langcode', $language);
      $query->condition('tfd.status', 1);
      $query->condition('fst.langcode', $language);
      $query->condition('fcsk.deleted', 0);
      $query->condition('fcsk.field_content_section_key_value', $sectionKey);
      // Level associated content type.
      $query->condition('flc.bundle', 'level_interactive_content');
      $query->condition('fd.status', 1);
      $query->condition('fd.langcode', $language);
      $query->condition('fpv.langcode', $language);
      $query->condition('fcc.field_consumer_category_target_id', $categoryId);
      if (!empty($markets)) {
        $query->condition('nfm.field_markets_target_id', $markets, 'IN');
      }

      // Fields.
      $query->distinct();
      $query->addField('flc', 'field_learning_category_target_id', 'id');
      $query->addField('tfd', 'name', 'displayTitle');
      $query->addField('fst', 'field_sub_title_value', 'subTitle');
      $query->addField('tfd', 'description__value', 'body');
      $query->addField('tfd', 'langcode', 'language');
      $query->addField('tfd', 'content_translation_created', 'timestamp');
      $query->addField('fd', 'nid', 'nid');
      $query->addField('fpv', 'field_point_value_value', 'pointValue');
      $query->addField('tfi', 'field_image_target_id', 'fid');
      $query->addField('tfi', 'langcode', 'fileLanguage');
      $query->addField('fm', 'uri', 'image');
      $query->addField('fcc', 'field_consumer_category_target_id', 'categoryId');

      // Order by.
      $query->orderBy('timestamp');
      $results = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      $results = [];
    }

    $levelsListing = [];
    if (!empty($results)) {

      $commonUtility = new CommonUtility();
      foreach ($results as $result) {
        $result = (array) $result;

        // Filter druplicate records of other field_image languages.
        if (in_array($result['fileLanguage'], [$language, ''])) {
          if (isset($levelsListing[$result['id']])) {
            $levelsListing[$result['id']]['pointValue'] = $levelsListing[$result['id']]['pointValue'] + $result['pointValue'];
          }
          else {
            $levelsListing[$result['id']]['id'] = $result['id'];
            $levelsListing[$result['id']]['displayTitle'] = $result['displayTitle'];
            $levelsListing[$result['id']]['subTitle'] = $result['subTitle'];
            $levelsListing[$result['id']]['body'] = $result['body'];
            $levelsListing[$result['id']]['type'] = 'level';
            $levelsListing[$result['id']]['pointValue'] = $result['pointValue'];
            $levelsListing[$result['id']]['timestamp'] = $result['timestamp'];
            $levelsListing[$result['id']]['categoryId'] = $result['categoryId'];

            $levelsListing[$result['id']]['imageSmall'] = $levelsListing[$result['id']]['imageMedium'] = $levelsListing[$result['id']]['imageLarge'] = '';

            // Create image urls for three different display screens.
            if (!empty($result['image'])) {
              $levelsListing[$result['id']]['imageSmall'] = $commonUtility->getImageStyleBasedUrl('stories_level_listing_mobile', $result['image']);
              $levelsListing[$result['id']]['imageMedium'] = $commonUtility->getImageStyleBasedUrl('stories_level_listing_tablet', $result['image']);
              $levelsListing[$result['id']]['imageLarge'] = $commonUtility->getImageStyleBasedUrl('stories_level_listing_desktop', $result['image']);
            }
          }
        }

      } // end foreach

      unset($commonUtility);
    } // end if

    return $levelsListing;
  }

}
