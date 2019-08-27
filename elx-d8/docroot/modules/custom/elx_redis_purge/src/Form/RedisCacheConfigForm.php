<?php

namespace Drupal\elx_redis_purge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\elx_utility\RedisClientBuilder;

/**
 * RedisCacheConfigForm class.
 */
class RedisCacheConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elx_redis_cache_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['redis_cache_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Purge Redis Cache'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $env = \Drupal::config('elx_utility.settings')->get('elx_environment');
    $redis_obj = RedisClientBuilder::getRedisClientObject('check');
    try {
      $result = $redis_obj->deleteKeyPattern([$env . '*']);
    }
    catch (\Exception $e) {

    }

    drupal_set_message('Redis cache purged ');
  }

}
