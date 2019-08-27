<?php

namespace Drupal\elx_dashboard\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\elx_user\Utility\UserUtility;
use Drupal\elx_utility\Utility\CommonUtility;

/**
 * Defines a confirmation form to confirm selection of market.
 */
class ArchiveConfirm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * Build form for archive content.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entities = $this->tempStoreFactory->get('user_content_archive')
      ->get($this->currentUser()->id());
    $names = [];
    $form['content_data'] = ['#tree' => TRUE];
    foreach ($entities as $entity) {
      $nid = $entity->id();
      $names[$nid] = $entity->label();
      $form['content_data'][$nid] = [
        '#type' => 'hidden',
        '#value' => $nid,
      ];
    }
    $form['content']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    $user_utility = new UserUtility();
    $common_utility = new CommonUtility();
    $market = $user_utility->getMarketByUserId($this->currentUser()->id(),
    'all');
    $market = array_column($market, 'field_default_market_target_id');
    // Checkbox options.
    foreach ($market as $market) {
      $market_name[$market] = $common_utility->getTermName($market);
    }
    // Build checkboxes.
    $form['select_archive_market'] = [
      '#title' => t('Select Market:'),
      '#type' => 'checkboxes',
      '#default_value' => array_keys($market_name),
      '#options' => $market_name,
      '#required' => TRUE
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Submit form for archive content.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear out the entities from the temp store.
    $this->tempStoreFactory->get('user_content_archive')->delete($this->currentUser()->id());
    if ($form_state->getValue('confirm')) {
      $content_array = $form_state->getValue('content_data');
      $markets = $form_state->getValue('select_archive_market');
      $markets = array_filter(array_values($markets));
      foreach ($content_array as $nid => $value) {
        $node = Node::load($nid);
        if (!empty($node->get('field_archive_and_unarchive')->getValue())) {
          $archive = array_column($node->get('field_archive_and_unarchive')
            ->getValue(), 'target_id');
          $markets = array_unique(array_merge($markets, $archive));
        }
        $node->set('field_archive_and_unarchive', $markets);
        $node->save();
      }
    }
    $form_state->setRedirect('view.dashboard.dashboard_available_for_a_translation');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "elx_dashboard_archive_confirm";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.dashboard.dashboard_available_for_a_translation');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Please select market for below selected content.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Archive');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('');
  }

}
