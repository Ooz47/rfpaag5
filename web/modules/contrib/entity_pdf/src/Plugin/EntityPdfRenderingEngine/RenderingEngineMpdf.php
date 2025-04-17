<?php

namespace Drupal\entity_pdf\Plugin\EntityPdfRenderingEngine;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineBase;
use Drupal\entity_pdf\Service\EntityPdfGenerator;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin definition for the MPDF Engine plugin.
 *
 * @EntityPdfRenderingEngine(
 *   id = "entity_pdf_engine_mpdf",
 *   label = @Translation("MPdf 8"),
 * )
 */
class RenderingEngineMpdf extends EntityPdfRenderingEngineBase {

  use LoggerChannelTrait;

  /**
   * The Print builder service.
   */
  protected EntityPdfGenerator $entityPdfGenerator;

  /**
   * The Module Handler Service.
   */
  protected ?ModuleHandlerInterface $moduleHandler = NULL;

  /**
   * The Mpdf object instance.
   */
  protected Mpdf $mpdf;

  /**
   * The current request.
   */
  protected Request $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityPdfGenerator $entity_pdf_generator, ModuleHandlerInterface $module_handler, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_pdf_generator);
    $this->moduleHandler = $module_handler;
    $this->request = $request;

    $mpdf_config = [];
    $mpdf_config['tempDir'] = $this->entityPdfGenerator->getTempDir();
    $defaultConfig = (new ConfigVariables())->getDefaults();
    $mpdf_config['fontDir'] = $defaultConfig['fontDir'];
    $defaultFontConfig = (new FontVariables())->getDefaults();
    $mpdf_config['fontdata'] = $defaultFontConfig['fontdata'];
    $mpdf_config['autoScriptToLang'] = TRUE;
    $mpdf_config['autoLangToFont'] = TRUE;

    // Allow other modules to alter (with hook_mpdf_config_alter) the mpdf
    // configuration.
    $this->moduleHandler->alter('mpdf_config', $mpdf_config);

    try {
      // Build and return the pdf.
      $this->mpdf = new Mpdf($mpdf_config);
      $this->mpdf->autoScriptToLang = $mpdf_config['autoScriptToLang'];
      $this->mpdf->autoLangToFont = $mpdf_config['autoLangToFont'];
      $this->mpdf->SetBasePath($this->request->getSchemeAndHttpHost());
    }
    catch (\Exception $e) {
      $this->getLogger('entity_pdf')->error($e->getMessage());
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_pdf.generator'),
      $container->get('module_handler'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function generatePdf(string $output, ?EntityInterface $entity = NULL, ?string $filename = NULL, ?string $langcode = NULL, ?BubbleableMetadata $bubbleableMetadata = NULL): string {
    $this->mpdf->SetTitle($filename);
    try {
      $this->mpdf->WriteHTML($output);
      return $this->mpdf->Output($filename, Destination::STRING_RETURN);
    }
    catch (\Exception $e) {
      $this->getLogger('entity_pdf')->error($e->getMessage());
      return '';
    }

  }

  /**
   * {@inheritdoc}
   */
  public function streamPdf(?string $filename = NULL): void {
    $this->mpdf->SetTitle($filename);
    try {
      $this->mpdf->Output($filename, Destination::DOWNLOAD);
    }
    catch (\Exception $e) {
      $this->getLogger('entity_pdf')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addPage(): void {
    $this->mpdf->AddPage();
  }

  /**
   * {@inheritdoc}
   */
  public function addContent(string $content): void {
    try {
      $this->mpdf->WriteHTML($content);
    }
    catch (\Exception $e) {
      $this->getLogger('entity_pdf')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPrintObject(): Mpdf {
    return $this->mpdf;
  }

}
