<?php

namespace Drupal\entity_pdf\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the EntityPdfRenderingEngine plugin manager.
 */
class EntityPdfRenderingEngineManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityPdfRenderingEngineManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityPdfRenderingEngine', $namespaces, $module_handler, 'Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineInterface', 'Drupal\entity_pdf\Annotation\EntityPdfRenderingEngine');

    $this->alterInfo('entity_pdf_entity_pdf_rendering_engine_info');
    $this->setCacheBackend($cache_backend, 'entity_pdf_entity_pdf_rendering_engine_plugins');
  }

}
