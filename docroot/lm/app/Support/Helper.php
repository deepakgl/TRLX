<?php

namespace App\Support;

use Illuminate\Http\Response;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Model\Elastic\FlagModel;

/**
 * Purpose of this class is to prepare all the global methods.
 */
class Helper {

  /**
   * Build json response.
   *
   * @param mixed $data
   *   Dynamic first argument.
   * @param mixed $meta
   *   Dynamic second argument.
   *
   * @return json
   *   Status object
   */
  public static function jsonSuccess($data, $meta = NULL) {
    $response = [
      'status' => 'success',
      'data' => $data,
    ];
    if ($meta) {
      $response['meta'] = $meta;
    }
    $headers = [
      'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ];

    return new Response($response, 200, $headers);
  }

  /**
   * Build json response.
   *
   * @param mixed $data
   *   Dynamic first argument.
   * @param int $error_code
   *   Error code.
   *
   * @return json
   *   Status object
   */
  public static function jsonError($data, $error_code) {
    $response = [
      'status' => 'error',
      'data' => $data,
    ];
    return new Response($response, $error_code);
  }

  /**
   * Get the user id on the basis of Oauth token.
   *
   * @param mixed $request
   *   Rest resource query parameters.
   *
   * @return mixed
   *   User uid.
   */
  public static function getJtiToken($request) {
    if (preg_match('/Bearer\s(\S+)/', $request->header('Authorization'), $matches)) {
      $token = $matches[1];
      // Get the jti token from Oauth token.
      try {
        $jti = (new Parser())->parse($token)->getHeader('jti');
        $query = DB::table('oauth2_token as ot')
          ->select('ot.auth_user_id')
          ->where('ot.value', '=', $jti)
          ->where('ot.status', '=', 1)
          ->where('ot.expire', '>', time())
          ->first();

        return $query->auth_user_id;
      }
      catch (\Exception $e) {
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Check whether elastic client exists or not.
   *
   * @return array
   *   Elastic client.
   */
  public static function checkElasticClient() {
    $hosts = [
      [
        'host' => getenv("ELASTIC_URL"),
        'port' => getenv("ELASTIC_PORT"),
        'scheme' => getenv("ELASTIC_SCHEME"),
        'user' => getenv("ELASTIC_USERNAME"),
        'pass' => getenv("ELASTIC_PASSWORD"),
      ],
    ];
    try {
      $client = ClientBuilder::create()->setHosts($hosts)->build();

      return $client;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Fetch user flag activities.
   *
   * @param int $nid
   *   Node id.
   * @param int $uid
   *   User uid.
   *
   * @return json
   *   Object of user activities.
   */
  public static function getUserFlagActivities($nid, $uid) {
    $nid_decode = unserialize($nid);
    $output = [];
    $client = self::checkElasticClient();
    if (!$client) {
      return FALSE;
    }
    $fav_status = $bookmark_status = FALSE;
    $fav_count = $bookmark_count = $download_count = 0;
    // Fetch flag data of node.
    $response = FlagModel::fetchMultipleElasticNodeData($nid_decode, $client);
    foreach ($response['docs'] as $key => $value) {
      // If data found in elastic.
      if ($value['found'] == 1) {
        // If key exists in array, update the respective flag status.
        if (array_key_exists('favorites_by_user', $value['_source'])) {
          $fav_count = count($value['_source']['favorites_by_user']);
          if (in_array($uid, $value['_source']['favorites_by_user'])) {
            $fav_status = TRUE;
          }
        }
        if (array_key_exists('bookmarks_by_user', $value['_source'])) {
          $bookmark_count = count($value['_source']['bookmarks_by_user']);
          if (in_array($uid, $value['_source']['bookmarks_by_user'])) {
            $bookmark_status = TRUE;
          }
        }
        if (array_key_exists('downloads_by_user', $value['_source'])) {
          $download_count = count($value['_source']['downloads_by_user']);
          if (in_array($uid, $value['_source']['downloads_by_user'])) {
            $download_status = TRUE;
          }
        }
        $output[] = [
          "nid" => $value['_id'],
          "favourites" => $fav_count,
          "bookmarks" => $bookmark_count,
          "downloadCount" => $download_count,
          "userFavouriteStatus" => $fav_status,
          "userBookmarkStatus" => $bookmark_status,
        ];
      }
    }
    $user_activities['userActivities'] = $output;

    return self::jsonSuccess($user_activities);
  }

  /**
   * Modify the hmac so it's safe to use in URLs.
   *
   * @param mixed $data
   *   Image style name with uri.
   * @param mixed $key
   *   Drupal system private key.
   *
   * @return string
   *   Image itok.
   */
  public static function searchHmacBase64($data, $key) {
    $hmac = base64_encode(hash_hmac('sha256', $data, $key, TRUE));

    return str_replace(['+', '/', '='], ['-', '_', ''], $hmac);
  }

  /**
   * Get the file id from media id.
   *
   * @param mixed $media_id
   *   Media id.
   *
   * @return array
   *   The file id.
   */
  public static function getUriByMediaId($media_id) {
    $query = DB::table('media__field_media_image as mf')
      ->join('file_managed as fm', 'fm.fid', '=',
       'mf.field_media_image_target_id')
      ->select('mf.entity_id', 'fm.uri')
      ->whereIn('mf.entity_id', array_filter($media_id))
      ->get();
    $result = [];
    foreach ($query as $key => $value) {
      $result[$value->entity_id] = $value->uri;
    }

    return $result;
  }

  /**
   * Get the file url from fid.
   *
   * @param mixed $fid
   *   Media id.
   *
   * @return array
   *   The file url.
   */
  public static function getUriByFid($fid) {
    $query = DB::table('file_managed as mf')
      ->select('mf.uri', 'mf.fid')
      ->whereIn('mf.fid', array_filter($fid))
      ->get();
    $result = [];
    foreach ($query as $key => $value) {
      $result[$value->fid] = $value->uri;
    }

    return $result;
  }

  /**
   * Fetch stamps image styles by fid and file uri.
   *
   * @param mixed $fids
   *   File id's.
   * @param mixed $image_uris
   *   Relative image path.
   *
   * @return array
   *   Image styles array.
   */
  public static function buildStampsImageStyles($fids, $image_uris) {
    $result = [];
    foreach ($fids as $fileData) {
      if (array_key_exists($fileData['imageId'], $image_uris)) {
        $path = str_replace("public://", NULL, $image_uris[$fileData['imageId']]);
        // Generate the respective image styles.
        $result[$fileData['tid']]['large'] = getenv("SITE_URL")
        . "/sites/default/files/styles/stamp_detail/public/"
        . $path
        . '?itok='
        . self::getPathToken(
          'search_listings_tablet',
          $image_uris[$fileData['imageId']]
        );

        $result[$fileData['tid']]['medium'] = getenv("SITE_URL")
        . "/sites/default/files/styles/stamp_tablet/public/" . $path
        . '?itok='
        . self::getPathToken(
          'search_listings_desktop',
          $image_uris[$fileData['imageId']]
        );

        $result[$fileData['tid']]['small'] = getenv("SITE_URL")
        . "/sites/default/files/styles/stamp_mobile/public/" . $path
        . '?itok='
        . self::getPathToken(
          'search_listings_mobile',
          $image_uris[$fileData['imageId']]
        );
      }
    }

    return $result;
  }

  /**
   * Generate itok for the images.
   *
   * @param string $style_name
   *   Image style name.
   * @param string $image_uri
   *   Image uri.
   *
   * @return string
   *   Image token.
   */
  public static function getPathToken($style_name, $image_uri) {
    return substr(self::searchHmacBase64($style_name . ':' . $image_uri, self::getSystemPrivateKey() . getenv('DRUPAL_HASH_SALT')), 0, 8);
  }

  /**
   * Fetch system private key.
   *
   * @return string
   *   System private key.
   */
  public static function getSystemPrivateKey() {
    $query = DB::table('key_value as elx')
      ->select('elx.value')
      ->where('elx.name', 'system.private_key')
      ->get();
    $query = (array) $query;
    try {
      return unserialize($query[key($query)][0]->value);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Fetch image styles by fid and file uri.
   *
   * @param mixed $fids
   *   File id's.
   * @param mixed $image_uris
   *   Relative image path.
   *
   * @return array
   *   Image styles array.
   */
  public static function buildImageStyles($fids, $image_uris) {
    $result = [];
    foreach ($fids as $fileData) {
      if (array_key_exists($fileData['imageId'], $image_uris)) {
        $path = str_replace("public://", NULL, $image_uris[$fileData['imageId']]);
        // Generate the respective image styles.
        $result[$fileData['nid']]['large'] = getenv("SITE_URL")
        . "/sites/default/files/styles/search_listings_desktop/public/"
        . $path
        . '?itok='
        . self::getPathToken(
          'search_listings_tablet',
          $image_uris[$fileData['imageId']]
        );

        $result[$fileData['nid']]['medium'] = getenv("SITE_URL")
        . "/sites/default/files/styles/search_listings_tablet/public/" . $path
        . '?itok='
        . self::getPathToken(
          'search_listings_desktop',
          $image_uris[$fileData['imageId']]
        );

        $result[$fileData['nid']]['small'] = getenv("SITE_URL")
        . "/sites/default/files/styles/search_listings_mobile/public/" . $path
        . '?itok='
        . self::getPathToken(
          'search_listings_mobile',
          $image_uris[$fileData['imageId']]
        );
      }
    }

    return $result;
  }

  /**
   * Prepare image styles response.
   *
   * @param mixed $result
   *   Data with respective image styles.
   * @param int $nid
   *   Node id.
   *
   * @return array
   *   Image styles array.
   */
  public static function buildImageResponse($result, $nid) {
    $response = [
      'imageLarge' => !empty($result[$nid]['large']) ? $result[$nid]['large'] : '',
      'imageMedium' => !empty($result[$nid]['medium']) ? $result[$nid]['medium'] : '',
      'imageSmall' => !empty($result[$nid]['small']) ? $result[$nid]['small'] : '',
    ];

    return $response;
  }

}
