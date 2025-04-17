<?php

namespace Drupal\entity_pdf\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_pdf\Service\EntityPdfGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to render a single entity.
 */
class PdfEntityController implements ContainerInjectionInterface {

  /**
   * The entity_pdf.settings configuration.
   */
  protected Config $config;

  /**
   * The Print builder service.
   */
  protected EntityPdfGenerator $entityPdfGenerator;

  /**
   * Creates an PdfEntityController object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityPdfGenerator $entity_pdf_generator) {
    $this->config = $config_factory->get('entity_pdf.settings');
    $this->entityPdfGenerator = $entity_pdf_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_pdf.generator'),
    );
  }

  /**
   * Public function view.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $view_mode
   *   The view mode machine name.
   * @param string|null $langcode
   *   The lang code.
   *
   * @return \Drupal\Core\Cache\CacheableResponse
   *   The Cacheable Response
   */
  public function view(EntityInterface $entity, Request $request, string $view_mode = 'full', ?string $langcode = NULL): CacheableResponse {
    $bubbleableMetadata = new BubbleableMetadata();
    $bubbleableMetadata->addCacheContexts(['languages', 'url.query_args:inline']);

    // Get filename.
    $filename = $this->entityPdfGenerator->getFilename($entity, $langcode, $bubbleableMetadata);

    // Render HTML.
    $output = $this->entityPdfGenerator->renderEntity($entity, $view_mode, $langcode, $bubbleableMetadata);

    // Generate PDF content.
    $content = $this->entityPdfGenerator->generatePdf($output, $entity, $filename, $langcode, $bubbleableMetadata);

    // Decide if content is sent to browser or downloaded.
    $openInBrowser = !!$this->config->get('openInBrowser');
    $contentDisposition = !!$openInBrowser || $request->query->get('inline') == 1 ? 'inline' : 'attachment';
    $headers = [
      'Content-Type' => 'application/pdf',
      'Content-disposition' => $contentDisposition . '; filename="' . $filename . '"',
    ];

    // Prepare response.
    $response = new CacheableResponse($content, 200, $headers);
    $response->addCacheableDependency($bubbleableMetadata);

    return $response;
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $view_mode
   *   The view mode.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, EntityInterface $entity, string $view_mode = 'full'): AccessResultInterface {
    return AccessResult::allowedIf(
      $account->hasPermission('view entity pdf') ||
      $account->hasPermission('view ' . $entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $view_mode . ' pdf')
    );
  }

}
