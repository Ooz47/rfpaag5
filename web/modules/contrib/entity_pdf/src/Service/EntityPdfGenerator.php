<?php

namespace Drupal\entity_pdf\Service;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineInterface;
use Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineManager;
use Drupal\file\FileRepositoryInterface;

/**
 * The EntityPdfGenerator Service.
 */
class EntityPdfGenerator {

  use LoggerChannelTrait;

  /**
   * The entity_pdf.settings configuration.
   */
  protected Config $config;

  /**
   * The Entity Pdf Rendering Engine plugin manager.
   */
  protected EntityPdfRenderingEngineManager $entityPdfRenderingEngineManager;

  /**
   * The File Repository service.
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * The Module Handler service.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The Entity type manager service.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The renderer service.
   */
  protected RendererInterface $renderer;

  /**
   * The Token service.
   */
  protected Token $token;

  /**
   * List of available rendering engines.
   *
   * @var \Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineInterface[]
   */
  protected ?array $renderingEngines = [];

  /**
   * The rendering engine being used.
   */
  protected ?EntityPdfRenderingEngineInterface $renderingEngine = NULL;

  /**
   * Constructor of EntityPdfGenerator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityPdfRenderingEngineManager $entity_pdf_rendering_engine_manager, FileRepositoryInterface $file_repository, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, Token $token) {
    $this->config = $config_factory->get('entity_pdf.settings');
    $this->entityPdfRenderingEngineManager = $entity_pdf_rendering_engine_manager;
    $this->fileRepository = $file_repository;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->token = $token;
  }

  /**
   * Return config.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration.
   */
  public function getConfig(): Config {
    return $this->config;
  }

  /**
   * Return configured filename for a specific entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string|null $langcode
   *   The lang code.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleableMetadata
   *   The BubbleableMetadata.
   *
   * @return string
   *   The filename.
   */
  public function getFilename(EntityInterface $entity, ?string $langcode = NULL, ?BubbleableMetadata $bubbleableMetadata = NULL): string {
    $filename = $this->config->get('filename');
    $this->moduleHandler->alter('entity_pdf_filename', $filename, $entity, $langcode);
    if ($bubbleableMetadata instanceof CacheableDependencyInterface) {
      $bubbleableMetadata->addCacheableDependency($this->config);
      $bubbleableMetadata->addCacheableDependency($entity);
      $bubbleableMetadata->addCacheContexts(['languages']);
    }
    return $this->token->replace($filename, [$entity->getEntityTypeId() => $entity], ['langcode' => $langcode], $bubbleableMetadata);
  }

  /**
   * Return configured temporary path.
   *
   * @return string
   *   The path string
   */
  public function getTempDir(): string {
    return DRUPAL_ROOT . '/' . $this->config->get('tempDir');
  }

  /**
   * Get the list of defined Rendering Engines.
   *
   * @return \Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineInterface[]
   *   The rendering engines list.
   */
  public function getRenderingEngines(): array {
    if (empty($this->renderingEngines)) {
      $this->renderingEngines = [];
      $definitions = $this->entityPdfRenderingEngineManager->getDefinitions();
      $pluginCollection = new DefaultLazyPluginCollection($this->entityPdfRenderingEngineManager, $definitions);
      foreach ($definitions as $plugin_id => $definition) {
        $plugin = $pluginCollection->get($plugin_id);
        if ($plugin instanceof EntityPdfRenderingEngineInterface) {
          $this->renderingEngines[$plugin->getPluginId()] = $plugin;
        }
      }
    }
    return $this->renderingEngines;
  }

  /**
   * Get the currently selected Rendering engine.
   *
   * @return \Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineInterface
   *   The rendering engine object/plugin.
   *
   * @throws \Exception
   */
  public function getRenderingEngine(): ?EntityPdfRenderingEngineInterface {
    if ($this->renderingEngine === NULL) {
      $engines = $this->getRenderingEngines();
      $current = $this->config->get('renderingEngine');
      if (!empty($engines)) {
        $this->renderingEngine = $engines[$current] ?? reset($engines);
      }
    }
    if ($this->renderingEngine) {
      return $this->renderingEngine;
    }
    else {
      throw new \Exception('The rendering engine could not be generated.');
    }
  }

  /**
   * Render an entity with bubbleable metadata.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode.
   * @param string|null $langcode
   *   The langcode (optional).
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleableMetadata
   *   The BubbleableMetadata.
   *
   * @return string
   *   The output content.
   */
  public function renderEntity(EntityInterface $entity, string $view_mode = 'full', ?string $langcode = NULL, ?BubbleableMetadata $bubbleableMetadata = NULL): string {
    global $base_url;

    $context = new RenderContext();
    $renderer = $this->renderer;
    $entityTypeManager = $this->entityTypeManager;
    $output = $renderer->executeInRenderContext($context, function () use ($entityTypeManager, $renderer, $entity, $view_mode, $langcode, $base_url) {
      $content = $entityTypeManager
        ->getViewBuilder($entity->getEntityTypeId())
        ->view($entity, $view_mode);
      $content['#entity_type'] = $entity->getEntityTypeId();
      $content['#' . $content['#entity_type']] = $entity;

      $build = [
        '#theme' => 'htmlpdf',
        '#title' => $entity->label(),
        '#content' => $content,
        '#base_url' => $base_url,
        '#langcode' => $langcode,
      ];

      return $renderer->render($build);
    });
    // Handle any bubbled cacheability metadata.
    if ($bubbleableMetadata instanceof CacheableDependencyInterface) {
      if (!$context->isEmpty()) {
        $metadata = $context->pop();
        $bubbleableMetadata->merge($metadata);
      }
      $bubbleableMetadata->addCacheableDependency($entity);
      $bubbleableMetadata->addCacheContexts(['languages']);
    }

    return $output;
  }

  /**
   * Returns generated pdf.
   *
   * @param string $output
   *   (HTML string)
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   * @param string|null $filename
   *   The file name.
   * @param string|null $langcode
   *   The langcode (optional).
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleableMetadata
   *   The BubbleableMetadata.
   *
   * @return string
   *   The generated pdf content.
   */
  public function generatePdf(string $output, ?EntityInterface $entity = NULL, ?string $filename = NULL, ?string $langcode = NULL, ?BubbleableMetadata $bubbleableMetadata = NULL): string {
    $filename = $filename ?: ($entity ? $this->getFilename($entity, $langcode, $bubbleableMetadata) : 'content.pdf');
    try {
      return $this->getRenderingEngine()->generatePdf($output, $entity, $filename, $langcode, $bubbleableMetadata);
    }
    catch (\Exception $e) {
      $this->getLogger('entity_pdf')->error($e->getMessage());
      return '';
    }
  }

  /**
   * Render any content entity as a PDF Stream to the client.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities array list.
   * @param string $view_mode
   *   The view mode machine name.
   * @param string|null $langcode
   *   The lang code.
   *
   * @throws \Exception
   */
  public function streamPdf(array $entities, string $view_mode = 'full', ?string $langcode = NULL): void {
    try {
      $rendering_engine = $this->getRenderingEngine();
      if ($rendering_engine instanceof EntityPdfRenderingEngineInterface) {
        $this->generatePagedPdf($entities, $rendering_engine, $view_mode, $langcode);
        // Calculate the filename.
        $first_entity_title = $this->getFilename(reset($entities), $langcode);
        // Eventually remove the .pdf extension, so to re-add it at the end of
        // the concat filename.
        $first_entity_title = preg_replace('/\.pdf$/i', '', $first_entity_title);
        $filename = count($entities) > 1 ? $first_entity_title . ' ... and ' . (count($entities) - 1) . ' others.pdf' : $first_entity_title . '.pdf';
        $rendering_engine->streamPdf($filename);
      }
    }
    catch (\Exception $e) {
      $this->getLogger('entity_pdf')->error($e->getMessage());
      throw new \Exception('The Pdf could not be streamed to the client.');
    }
  }

  /**
   * Configure the print engine with the passed entities.
   *
   * @param array $entities
   *   The entities array list.
   * @param \Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineInterface $rendering_engine
   *   The rendering engine object.
   * @param string $view_mode
   *   The view mode machine name.
   * @param string|null $langcode
   *   The lang code.
   */
  protected function generatePagedPdf(array $entities, EntityPdfRenderingEngineInterface $rendering_engine, string $view_mode = 'full', ?string $langcode = NULL): void {
    if (empty($entities)) {
      throw new \InvalidArgumentException('You must pass at least 1 entity');
    }

    $counter = 0;
    foreach ($entities as $entity) {
      $bubbleableMetadata = new BubbleableMetadata();
      $bubbleableMetadata->addCacheContexts(['languages']);

      $content = $this->renderEntity($entity, $view_mode, $langcode, $bubbleableMetadata);
      // Add Content to the Rendering Engine.
      $rendering_engine->addContent($content);

      // Add Page to the Rendering Engine, but not at the end of the latest
      // entity.
      if ($counter < count($entities) - 1) {
        $rendering_engine->addPage();
      }
      $counter++;
    }
  }

}
