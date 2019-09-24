<?php

namespace App\Services\Validation;

use Illuminate\Validation\Validator as IlluminateValidator;

class ValidatorExtended extends IlluminateValidator {

  private $_custom_messages = [
    'numericarray' => 'The :attribute must be numeric array value.',
    'positiveinteger' => 'The :attribute must be positive integer',
    'likebookmarkflag' => 'The :attribute must be either like or bookmark.',
    'format' => 'The :attribute must be json'
  ];

  public function __construct($translator, $data, $rules, $messages = array(), $customAttributes = array()) {
    parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    $this->_set_custom_stuff();
  }

  /**
   * Setup any customizations etc
   *
   * @return void
   */
  protected function _set_custom_stuff() {
    //setup our custom error messages
    $this->setCustomMessages($this->_custom_messages);
  }

  /**
   * Allow only integer values in the array.
   *
   * @param mixed $attribute
   * @param mixed $value
   * @param mixed $parameters
   * @param mixed $validator
   * @return bool
   */
  protected function validateNumericArray($attribute, $value, $parameters, $validator) {
    if (!is_array($value)) {
      return false;
    }

    foreach ($value as $v) {
      if (!preg_match("/^[1-9]\d*$/", $v)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Allow only positive integer values.
   * 
   * @param $attribute
   * @param $value
   * @param $parameters
   * @param $validator
   * @return bool
   */
  protected function validatePositiveInteger($attribute, $value, $parameters, $validator) {
    if (preg_match("/^[1-9]\d*$/", $value)) {
      return true;
    }
    return false;
  }

  /**
   * Allow only like bookmark flag.
   * 
   * @param $attribute
   * @param $value
   * @param $parameters
   * @param $validator
   * @return bool
   */
  protected function validateLikeBookmarkFlag($attribute, $value, $parameters, $validator) {
    if (in_array($value, ['like', 'bookmark'])) {
      return true;
    }

    return false;
  }
  
  /**
   * Validate response format.
   * 
   * @param $attribute
   * @param $value
   * @param $parameters
   * @param $validator
   * @return bool
   */
  protected function validateFormat($attribute, $value, $parameters, $validator) {
    if ($value == 'json') {
      return true;
    }

    return false;
  }  

}
