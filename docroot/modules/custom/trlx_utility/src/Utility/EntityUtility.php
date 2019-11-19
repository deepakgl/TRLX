<?php

namespace Drupal\trlx_utility\Utility;

use Drupal\views\Views;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\trlx_utility\RedisClientBuilder;
// fixMe.
use Drupal\elx_user\Utility\UserUtility;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Site\Settings;

/**
 * Purpose is to build view response, fetch & set the view. Response in redis.
 */
class EntityUtility {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->userUtility = new UserUtility();
    // fixMe.
    $this->config = \Drupal::config('elx_utility.settings');
    $this->configuration = \Drupal::config('trlx_utility.settings');
    $this->commonUtility = new CommonUtility();
  }

  /**
   * Fetch content from view.
   *
   * @param string $view_name
   *   View name.
   * @param string $view_display
   *   View display name.
   * @param mixed $arguments
   *   Arguments.
   * @param mixed $data
   *   Response key value pair.
   * @param string $type
   *   Content type.
   * @param array $field_replace
   *   Fields to replace.
   *
   * @return markup
   *   View markup.
   */
  public function getViewContent($view_name, $view_display, $arguments, $data = NULL, $type = NULL, $field_replace = []) {
    $limit = $offset = '';
    if (is_array($arguments) && $this->isAssoc($arguments)) {
      $args = [];
      foreach ($arguments as $key => $argument) {
        // Set value for $limit & $offset if respective value is available.
        if (in_array($key, ['limit', 'offset'])) {
          $$key = $argument;
        }
        else {
          $args[] = (is_array($argument) && count($argument) > 1) ? implode("+", $argument) : $argument;
        }
      }
      $arguments = $args;
    }
    else {
      $arguments = count(array_filter($arguments)) > 1 ? [implode("+", $arguments)] : $arguments;
    }
    $view = Views::getView($view_name);
    $view->setDisplay($view_display);
    if (!empty(array_filter($arguments))) {
      $view->setArguments($arguments);
    }

    // Set view pager limit (items_per_page)
    if (!empty($limit)) {
      $view->setItemsPerPage($limit);
    }

    // Set view pager offset.
    if (!empty($offset)) {
      $view->setOffset($offset);
    }

    $view->execute();
    $view_result = \Drupal::service('renderer')->renderRoot($view->render());
    $view_results = (is_object($view_result)) ? JSON::decode($view_result->jsonSerialize(), TRUE) : [];
    if ((isset($view_results['results']) && empty($view_results['results']))
    || empty($view_results)) {
      // No results found.
      return [[], Response::HTTP_NO_CONTENT];
    }

    // Views listing response without pager e.g. /api/v1/consumerCategories.
    if (!empty($view_results) && ($view->getItemsPerPage() == 0) && !isset($view_results['results']) && !isset($view_results['pager'])) {
      $view_results_without_pager = $view_results;
      unset($view_results);
      $view_results['results'] = $view_results_without_pager;
    }

    // Build the response of listings and details respectively.
    $response = isset($view_results['results']) ? $this
      ->buildListingResponse($view_results, $data, $field_replace) : $this
      ->buildDetailResponse($view_results, $data, $type);

    return [$response, Response::HTTP_OK];
  }

  /**
   * Add base url in ckeditor file content.
   *
   * @param string $str
   *   Ckeditor field content.
   *
   * @return string
   *   Updated ckeditor content.
   */
  public function stringReplace($str) {
    $filePublicUrl = Settings::get('cdn_file_public_base_url');
    $result = str_replace(
      '"/sites/default/files', '"' . $filePublicUrl, $str
    );

    return $result;
  }

  /**
   * Prepare respective api response.
   *
   * @param string $key
   *   Redis key.
   * @param mixed $view_name
   *   View name.
   * @param mixed $current_display
   *   View current display.
   * @param array $data
   *   Response key value pair.
   * @param mixed $filter
   *   View contextual filter.
   * @param string $type
   *   Content type.
   * @param array $field_replace
   *   Fields to replace.
   *
   * @return json
   *   View object.
   */
  public function fetchApiResult($key = NULL, $view_name, $current_display, $data = NULL, $filter = NULL, $type = NULL, $field_replace = []) {
    if (!is_array($filter)) {
      $filter = [$filter];
    }
    // fixMe.
    $key = $this->config->get('elx_environment') . $key;
    $redis_key = explode(':', $key);

    if (!empty($redis_key[1])) {
      try {
        // Creating Redis connection object.
        list($cached_data, $redis_client) =
        RedisClientBuilder::getRedisClientObject($key);
        // Get the data from the redis cache with key value.
        if (!empty($cached_data)) {
          return [JSON::decode($cached_data), 200];
        }
      }
      catch (\Exception $e) {
        // Fetch result from respective view.
        list($view_results, $status_code) = $this->getViewContent($view_name, $current_display, $filter, $data, $type, $field_replace);

        // Only set redis cache if there is some data.
        $this->setRedisCache($redis_key, $redis_client, $view_results);

        return [$view_results, $status_code];
      }
      // Fetch result from respective view.
      list($view_results, $status_code) = $this->getViewContent($view_name, $current_display, $filter, $data, $type, $field_replace);

      // Only set redis cache if there is some data.
      $this->setRedisCache($redis_key, $redis_client, $view_results);

      return [$view_results, $status_code];
    }
    // Fetch result from respective view.
    list($view_results, $status_code) = $this->getViewContent($view_name, $current_display, $filter, $data, $type, $field_replace);
    // Convert pager count value to int.
    if (isset($view_results['pager']['count'])) {
      $view_results['pager']['count'] = (int) $view_results['pager']['count'];
    }
    return [$view_results, $status_code];
  }

  /**
   * Private function to set Redis Cache if data is available.
   *
   * @param array $redis_key
   *   Redis Key.
   * @param object $redis_client
   *   Redis Client Object.
   * @param array $view_results
   *   View Results.
   *
   * @return boolean
   *   TRUE or FALSE.
   */
  private function setRedisCache($redis_key, $redis_client, $view_results) {
    // Only set redis cache if there is some data.
    $decode = array_filter(JSON::decode($view_results, TRUE));
    if (!empty($redis_key[1])) {
      $response = JSON::encode($view_results);
      if (is_object($response)) {
        $response = $response->getContent();
      }
      $redis_client->set($response, $redis_key[0], $redis_key[1], $redis_key[2]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Prepare listings api response.
   *
   * @param mixed $output
   *   View output.
   * @param array $data
   *   Response key value pair.
   * @param array $field_replace
   *   Fields to replace.
   * @param array $field_remove
   *   Fields to remove.
   *
   * @return json
   *   API response.
   */
  public function buildListingResponse($output, $data, $field_replace = [], $field_remove = []) {
    if (!empty($data)) {
      foreach ($output['results'] as $view_key => $result) {
        foreach ($data as $key => $value) {
          if ($value == 'decode') {
            $output['results'][$view_key][$key] = Html::decodeEntities($result[$key]);
          }
          elseif ($value == 'int') {
            if (isset($result[$key])) {
              $output['results'][$view_key][$key] = (int) $result[$key];
            }
          }
          elseif ($value == 'string_replace') {
            $output['results'][$view_key][$key] = $this->stringReplace($result[$key]);
          }
          elseif ($value == 'append_host') {
            $output['results'][$view_key][$key] = !empty($result[$key]) ? \Drupal::request()->getSchemeAndHttpHost() . $result[$key] : $result[$key];
          }
          // Set value for boolean by default unselected fields.
          elseif ($value == 'boolean') {
            $output['results'][$view_key][$key] = empty($result[$key]) ? FALSE : TRUE;
          }
          // Set point value specific to section from config.
          elseif ($value == 'point_value_' . $this->commonUtility::INSIDER_CORNER) {
            $pointValue = $this->configuration->get($value);
            $output['results'][$view_key][$key] = !empty($pointValue) ? $pointValue : $result[$key];
          }
          // Set point value specific to section from config.
          elseif ($value == 'point_value_' . $this->commonUtility::TREND) {
            $pointValue = $this->configuration->get($value);
            $output['results'][$view_key][$key] = !empty($pointValue) ? $pointValue : $result[$key];
          }
          // Set point value specific to section from config.
          elseif ($value == 'point_value_' . $this->commonUtility::SELLING_TIPS) {
            $pointValue = $this->configuration->get($value);
            $output['results'][$view_key][$key] = !empty($pointValue) ? $pointValue : $result[$key];
          }
          // Calculate point value for "Learning Level".
          // Based on associated "Level Interactive Content".
          elseif ($value == 'point_value_level') {
            if (isset($output['results'][$view_key][$key])) {
              // Calculate aggregate point value.
              $output['results'][$view_key][$key] = $this->commonUtility->getLearningLevelPointValue($result[$key]);
              $output['results'][$view_key]['pointValue'] = $output['results'][$view_key][$key];
            }
          }
          else {
            $output['results'][$view_key] = $result;
            if (isset($output['results'][$view_key][$key])) {
              $output['results'][$view_key][$key] = $result[$key];
            }
          }
        }
      }
      // Manage the multiple fields.
      if (!empty($field_replace)) {
        foreach ($field_replace as $field_key => $replace_field) {
          foreach ($output['results'] as $view_key => $result) {
            // Replace the field.
            if (empty($output['results'][$view_key][$field_key]) && !empty($output['results'][$view_key][$replace_field])) {
              $output['results'][$view_key][$field_key] = $output['results'][$view_key][$replace_field];
            }
            // We don't need this data in response.
            unset($output['results'][$view_key][$replace_field]);
          }
        }
      }
      // Remove unwanted fields.
      if (!empty($field_remove)) {
        foreach ($field_remove as $field_key) {
          foreach ($output['results'] as $view_key => $result) {
            if (isset($output['results'][$view_key][$field_key])) {
              unset($output['results'][$view_key][$field_key]);
            }
          }
        }
      }
    }
    // $response = JSON::encode($output);
    $response = $output;
    if (is_object($response)) {
      $response = $response->getContent();
    }

    return $response;
  }

  /**
   * Prepare details api response.
   *
   * @param mixed $output
   *   View output.
   * @param array $data
   *   Response key value pair.
   * @param string $type
   *   Content type.
   *
   * @return json
   *   API response.
   */
  public function buildDetailResponse($output, $data = NULL, $type = NULL) {
    if (!empty($data)) {
      foreach ($output as $view_key => $result) {
        foreach ($data as $key => $value) {
          if ($value == 'decode') {
            $output[$view_key][$key] = Html::decodeEntities($result[$key]);
          }
          elseif ($value == 'int') {
            $output[$view_key][$key] = (int) $result[$key];
          }
          elseif ($value == 'string_replace') {
            $output[$view_key][$key] = $this->stringReplace($result[$key]);
          }
          elseif ($value == 'append_host') {
            $output[$view_key][$key] = !empty($result[$key]) ? \Drupal::request()->getSchemeAndHttpHost() . $result[$key] : $result[$key];
          }
          // Set value for boolean by default unselected fields.
          elseif ($value == 'boolean') {
            $output[$view_key][$key] = empty($result[$key]) ? FALSE : TRUE;
          }
          // Set point value specific to section from config.
          elseif ($value == 'point_value_' . $this->commonUtility::INSIDER_CORNER) {
            $pointValue = $this->configuration->get($value);
            $output[$view_key][$key] = !empty($pointValue) ? $pointValue : $result[$key];
          }
          // Set point value specific to section from config.
          elseif ($value == 'point_value_' . $this->commonUtility::TREND) {
            $pointValue = $this->configuration->get($value);
            $output[$view_key][$key] = !empty($pointValue) ? $pointValue : $result[$key];
          }
          // Set point value specific to section from config.
          elseif ($value == 'point_value_' . $this->commonUtility::SELLING_TIPS) {
            $pointValue = $this->configuration->get($value);
            $output[$view_key][$key] = !empty($pointValue) ? $pointValue : $result[$key];
          }
          elseif ($value == 'social_media_handles') {
            // Fetch social media handles for Insider Corner section.
            $socialMediaHandles = $this->commonUtility->getSocialMediaHandles($result[$key]);
            $output[$view_key][$key] = $socialMediaHandles;
          }
          else {
            $output[$view_key] = $result;
            if (isset($output[$view_key][$key])) {
              $output[$view_key][$key] = $result[$key];
            }
          }
        }
      }
    }

    $response = (isset($output[0])) ? $output[0] : $output;

    return $response;
  }

  /**
   * Check if the array is associative array (keys non numeric)
   *
   * @param array $arr
   *   Array to be checked.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isAssoc(array $arr) {
    if ([] === $arr) {
      return FALSE;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

}
