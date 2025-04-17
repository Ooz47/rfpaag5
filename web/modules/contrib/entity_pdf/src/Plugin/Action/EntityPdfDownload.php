<?php

namespace Drupal\entity_pdf\Plugin\Action;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineManager;
use Drupal\entity_pdf\Service\EntityPdfGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads the Entity Pdf.
 *
 * @Action(
 *   id = "entity_pdf_download_action",
 *   label = @Translation("Entity Pdf Download"),
 *   type = "node"
 * )
 */
class EntityPdfDownload extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

  /**
   * Access manager.
   */
  protected AccessManagerInterface $accessManager;

  /**
   * The Print builder service.
   */
  protected EntityPdfGenerator $entityPdfGenerator;

  /**
   * The Entity Pdf Rendering Engine plugin manager.
   */
  protected EntityPdfRenderingEngineManager $entityPdfRenderingEngineManager;

  /**
   * Constructor of EntityPdfDownload Action plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, EntityPdfGenerator $entity_pdf_generator, EntityPdfRenderingEngineManager $entity_pdf_rendering_engine_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->accessManager = $access_manager;
    $this->entityPdfGenerator = $entity_pdf_generator;
    $this->entityPdfRenderingEngineManager = $entity_pdf_rendering_engine_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('entity_pdf.generator'),
      $container->get('plugin.manager.entity_pdf_rendering_engine')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $object;
    $view_display_id = $this->configuration["view_mode"] ?? 'full';
    // - 'view node.rent_monthly_due.pdf pdf'
    $view_display_permission = 'view node.' . $node->bundle() . '.' . $view_display_id . ' pdf';
    // If the user has proper view (display) pdf permissions.
    return $account->hasPermission('view entity pdf') || $account->hasPermission($view_display_permission);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities): void {
    try {
      (new StreamedResponse(function () use ($entities) {
        $this->entityPdfGenerator->streamPdf($entities, $this->configuration["view_mode"]);
      }))->send();
    }
    catch (\Exception $e) {
      $this->messenger()->addError(new FormattableMarkup(Xss::filter($e->getMessage()), []));
      $this->getLogger('entity_pdf')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View Mode'),
      '#options' => [
        'full' => $this->t('full'),
        'pdf' => $this->t('pdf'),
      ],
      '#required' => TRUE,
      '#default_value' => !empty($this->configuration['view_mode']) ? $this->configuration['view_mode'] : 'full',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

}
