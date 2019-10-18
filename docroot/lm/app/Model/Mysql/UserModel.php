<?php

namespace App\Model\Mysql;

use Illuminate\Support\Facades\DB;

/**
 * Purpose of this class is to fetch user information.
 */
class UserModel {

  /**
   * Fetch user information by uid and key.
   *
   * @param int $uid
   *   User id.
   * @param mixed $key
   *   User information to fetch.
   *
   * @return array
   *   User array with required information.
   */
  public static function getUserInfoByUid($uid, $key) {
    if (!is_array($key)) {
      $key = [$key];
    }
    if (!is_array($uid)) {
      $uid = [$uid];
    }

    $users = $select = [];
    $query = DB::table('users as u');
    if (in_array('name', $key)) {
      // Get user first & last name.
      $select[] = 'fn.field_first_name_value as firstname';
      $select[] = 'ln.field_last_name_value as lastname';
      $query->leftJoin('user__field_first_name as fn', 'u.uid', '=', 'fn.entity_id');
      $query->leftJoin('user__field_last_name as ln', 'u.uid', '=', 'ln.entity_id');
    }
    if (in_array('image', $key)) {
      // Get user picture.
      $select[] = 'fm.uri as image';
      $query->leftJoin('user__user_picture as up', 'u.uid', '=', 'up.entity_id');
      $query->leftJoin('file_managed as fm', 'up.user_picture_target_id', '=', 'fm.fid');
    }
    if (in_array('market', $key)) {
      // Get user market.
      $select[] = 'um.field_default_market_target_id as market';
      $query->leftJoin('user__field_default_market as um', 'u.uid', '=', 'um.entity_id');
    }
    if (in_array('language', $key)) {
      // Get user language.
      $select[] = 'u.langcode as language';
    }
    if (in_array('store', $key)) {
      // Get user store.
      $select[] = 'ud.field_door_value as store';
      $query->leftJoin('user__field_door as ud', 'u.uid', '=', 'ud.entity_id');
    }
    if (in_array('retailer', $key)) {
      // Get user account name.
      $select[] = 'ua.field_account_name_value as retailer';
      $query->leftJoin('user__field_account_name as ua', 'u.uid', '=', 'ua.entity_id');
    }
    if (in_array('state', $key)) {
      // Get user state.
      $select[] = 'us.field_state_value as state';
      $query->leftJoin('user__field_state as us', 'u.uid', '=', 'us.entity_id');
    }
    $query->whereIn('u.uid', $uid);
    $result = $query->select($select)->get();

    return $result;
  }

  /**
   * Fetch user market by user object.
   *
   * @return array
   *   User market.
   */
  public static function getMarketByUserData() {
    global $_userData;
    $regions = $_userData->region;
    $subregions = $_userData->subregion;
    $country = $_userData->country;
    // Get region, subregion or country from token if array is not empty.
    if (!empty($country)) {
      $ref_keys = $country;
    }
    elseif (!empty($subregions)) {
      $ref_keys = $subregions;
    }
    elseif (!empty($regions)) {
      $ref_keys = $regions;
    }
    // Get current user markets.
    $markets = array_column(self::getMarketByReferenceId($ref_keys), 'entity_id');

    return $markets;
  }

  /**
   * Fetch user market by user region, subregion or country reference id.
   *
   * @param int $region_subregion_country_ids
   *   Region, subregion or country reference ids.
   *
   * @return array
   *   User market.
   */
  public static function getMarketByReferenceId($region_subregion_country_ids) {
    $query = DB::table('taxonomy_term__field_region_subreg_country_id as tfrsc');
    $select[] = 'tfrsc.entity_id';
    $select[] = 'tfrsc.field_region_subreg_country_id_value';
    $query->whereIn('tfrsc.field_region_subreg_country_id_value', $region_subregion_country_ids);
    $result = $query->select($select)->get()->all();

    return $result;
  }

}
