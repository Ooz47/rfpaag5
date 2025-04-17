<?php

namespace Drupal\entity_pdf\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_pdf\Service\EntityPdfGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for EntityPdfRenderingEngine plugins.
 */
abstract class EntityPdfRenderingEngineBase extends PluginBase implements EntityPdfRenderingEngineInterface, ContainerFactoryPluginInterface {

  /**
   * The Print builder service.
   */
  protected EntityPdfGenerator $entityPdfGenerator;

  /**
   * The entity_pdf.settings configuration.
   */
  protected Config $config;

  /**
   * Constructor of a EntityPdfRenderingEngineBase object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityPdfGenerator $entity_pdf_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityPdfGenerator = $entity_pdf_generator;
    $this->config = $this->entityPdfGenerator->getConfig();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_pdf.generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderingOptions(): array {
    $options = $this->config->get('renderingEngineOptions.' . $this->getPluginId()) ?: [];
    foreach ($this->getConfigurableOptions() as $key => $default) {
      $options[$key] = $options[$key] ?? $default;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurableOptions(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettingsForm(array &$form, FormStateInterface $form_state): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettingsFormSubmit(array &$form, FormStateInterface $form_state): array {
    return [];
  }

}
