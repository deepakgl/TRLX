<?php

namespace App\Services\Validation;

use Illuminate\Validation\Validator as IlluminateValidator;
use App\Model\Mysql\ContentModel;

/**
 *
 */
class ValidatorExtended extends IlluminateValidator {

  private $_custom_messages = [
    'numericarray' => 'The :attribute must be numeric array value.',
    'positiveinteger' => 'The :attribute must be positive integer',
    'likebookmarkflag' => 'The :attribute must be either like or bookmark',
    'format' => 'The :attribute must be json',
    'brandid' => 'Brand Id (:input) does not exist.',
    'languagecode' => 'The :attribute is not valid',
    'bookmarklisttype' => 'The type must be either video or myLibrary',
    'leaderboardsection' => 'The :attribute value is not valid',
  ];

  /**
   *
   */
  public function __construct($translator, $data, $rules, $messages = [], $customAttributes = []) {
    parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    $this->_set_custom_stuff();
  }

  /**
   * Setup any customizations etc.
   *
   * @return void
   */
  protected function _set_custom_stuff() {
    // Setup our custom error messages.
    $this->setCustomMessages($this->_custom_messages);
  }

  /**
   * Validate response format.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validateNumericArray($attribute, $value, $parameters, $validator) {
    if (!is_array($value)) {
      return FALSE;
    }

    foreach ($value as $v) {
      if (!preg_match("/^[1-9]\d*$/", $v)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Validate response format.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validatePositiveInteger($attribute, $value, $parameters, $validator) {
    if (preg_match("/^[1-9]\d*$/", $value)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate response format.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validateLikeBookmarkFlag($attribute, $value, $parameters, $validator) {
    if (in_array($value, ['like', 'bookmark'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate response format.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validateFormat($attribute, $value, $parameters, $validator) {
    if ($value == 'json') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate response format.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validateBrandId($attribute, $value, $parameters, $validator) {
    $brands_terms_ids = ContentModel::getBrandTermIds();
    $brand_keys = array_column($brands_terms_ids, 'field_brand_key_value');
    if (in_array($value, $brand_keys)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate language code.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validateLanguageCode($attribute, $value, $parameters, $validator) {
    $trlxUtilityConfig = ContentModel::getTrlxUtilityConfigValues();
    $languages = $trlxUtilityConfig['site_languages'];
    if (array_key_exists($value, $languages)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate bookmark list type.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validateBookmarkListType($attribute, $value, $parameters, $validator) {
    if (in_array($value, ['myLibrary', 'video'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Validate leaderboard section value.
   *
   * @param mixed $attribute
   *   Attribute.
   * @param mixed $value
   *   Value.
   * @param mixed $parameters
   *   Parameters.
   * @param mixed $validator
   *   Validator.
   *
   * @return bool
   *   True/False.
   */
  protected function validateLeaderboardSection($attribute, $value, $parameters, $validator) {
    if (in_array($value, ['world', 'region', 'subregion', 'country', 'location'])) {
      return TRUE;
    }

    return FALSE;
  }

}
