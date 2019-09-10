<?php

namespace Drupal\trlx_utility\Utility;

use Drupal\views\Views;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\trlx_utility\RedisClientBuilder;
// fixMe.
use Drupal\elx_user\Utility\UserUtility;
use Symfony\Component\HttpFoundation\Response;

/**
 * Purpose of this class is to build view response, fetch & set the view.
 * response in redis.
 */
class EntityUtility {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->userUtility = new UserUtility();
    // fixMe.
    $this->config = \Drupal::config('elx_utility.settings');
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
   *
   * @return markup
   *   View markup.
   */
  public function getViewContent($view_name, $view_display, $arguments, $data = NULL, $type = NULL) {
    $limit = $offset = '';
    if (is_array($arguments) && $this->isAssoc($arguments)) {
      $args = [];
      foreach ($arguments as $key => $argument) {
        // Set value for $limit & $offset if respective value is available
        if (in_array($key, ['limit', 'offset'])) {
          $$key = $argument;
        }
        else {
          $args[] = (count($argument) > 1) ? implode("+", $argument) : $argument;
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

    // set view pager limit (items_per_page)
    if (!empty($limit)) {
      $view->setItemsPerPage($limit);
    }

    // set view pager offset
    if (!empty($offset)) {
      $view->setOffset($offset);
    }

    $view->execute();
    $view_result = \Drupal::service('renderer')->renderRoot($view->render());
    $view_results = JSON::decode($view_result->jsonSerialize(), TRUE);
    if ((isset($view_results['results']) && empty($view_results['results']))
    || empty($view_results)) {
      // No results found.
      return [[], Response::HTTP_NO_CONTENT];
    }
    // Build the response of listings and details respectively.
    $response = isset($view_results['results']) ? $this
      ->buildListingResponse($view_results, $data) : $this
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
    global $base_url;
    $result = str_replace(
      '"/sites/default/files', '"' . $base_url . '/sites/default/files', $str
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
   *
   * @return json
   *   View object.
   */
  public function fetchApiResult($key = NULL, $view_name, $current_display, $data = NULL, $filter = NULL, $type = NULL) {
    if (!is_array($filter)) {
      $filter = [$filter];
    }
    // fixMe.
    $key = $this->config->get('elx_environment') . $key;
    $redis_key = explode(':', $key);
    // Get current user roles.
    // fixMe.
    $roles = $this->userUtility->getUserRoles(\Drupal::currentUser()->id());
    if ($roles && !empty($redis_key[1])) {
      try {
        // Creating Redis connection object.
        list($cached_data, $redis_client) =
        RedisClientBuilder::getRedisClientObject($key);
        // Get the data from the redis cache with key value.
        if (!empty($cached_data)) {
          return [$cached_data, 200];
        }
      }
      catch (\Exception $e) {
        // Fetch result from respective view.
        list($view_results, $status_code) = $this->getViewContent($view_name, $current_display, $filter, $data, $type);

        return [$view_results, $status_code];
      }
      // Fetch result from respective view.
      list($view_results, $status_code) = $this->getViewContent($view_name, $current_display, $filter, $data, $type);
      // Only set redis cache if there is some data.
      $decode = array_filter(JSON::decode($view_results, TRUE));
      if (!empty($decode) && !empty($redis_key[1])) {
        $redis_client->set($view_results, $redis_key[0], $redis_key[1], $redis_key[2]);
      }

      return [$view_results, $status_code];
    }
    // Fetch result from respective view.
    list($view_results, $status_code) = $this->getViewContent($view_name, $current_display, $filter, $data, $type);

    return [$view_results, $status_code];
  }

  /**
   * Prepare listings api response.
   *
   * @param mixed $output
   *   View output.
   * @param array $data
   *   Response key value pair.
   *
   * @return json
   *   API response.
   */
  public function buildListingResponse($output, $data) {
    if (!empty($data)) {
      foreach ($output['results'] as $view_key => $result) {
        foreach ($data as $key => $value) {
          if ($value == 'decode') {
            $output['results'][$view_key][$key] = Html::decodeEntities($result[$key]);
          }
          elseif ($value == 'int') {
            $output['results'][$view_key][$key] = (int) $result[$key];
          }
          elseif ($value == 'string_replace') {
            $output['results'][$view_key][$key] = $this->stringReplace($result[$key]);
          }
          // Set value for boolean fields without value (if it is unselected by default)
          elseif ($value == 'boolean') {
            $output['results'][$view_key][$key] = empty($result[$key]) ? "false" : "true";
          }
          else {
            $output['results'][$view_key] = $result;
            if (isset($output['results'][$view_key][$key])) {
              $output['results'][$view_key][$key] = $result[$key];
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
          // Set value for boolean fields without value (if it is unselected by default)
          elseif ($value == 'boolean') {
            $output[$view_key][$key] = empty($result[$key]) ? "false" : "true";
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
