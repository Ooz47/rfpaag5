<?php

namespace Drupal\entity_pdf\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Defines an interface for EntityPdfRenderingEngine plugins.
 */
interface EntityPdfRenderingEngineInterface extends PluginInspectionInterface {

  /**
   * Gets the EntityPdfRenderingEngine name.
   *
   * @return string
   *   Name of the EntityPdfRenderingEngine.
   */
  public function getName(): string;

  /**
   * Gets the object for this Print engine.
   *
   * Note, it is not advised that you use this method if you want your code to
   * work generically across all print engines.
   *
   * @return object
   *   The implementation specific print object being used.
   */
  public function getPrintObject(): object;

  /**
   * Return PDF content.
   *
   * @param string $output
   *   (HTML string)
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   * @param string|null $filename
   *   The filename.
   * @param string|null $langcode
   *   The langcode.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleableMetadata
   *   The bubbleable Metadata.
   *
   * @return string
   *   The generated PDF html string.
   *
   * @see \Drupal\entity_pdf\Service\EntityPdfGenerator
   */
  public function generatePdf(string $output, ?EntityInterface $entity = NULL, ?string $filename = NULL, ?string $langcode = NULL, ?BubbleableMetadata $bubbleableMetadata = NULL): string;

  /**
   * Stream PDF content to the Client, with a specific file name.
   *
   * @param string|null $filename
   *   The filename (or null).
   *
   * @see \Drupal\entity_pdf\Service\EntityPdfGenerator
   */
  public function streamPdf(?string $filename = NULL);

  /**
   * Return configured options.
   *
   * @return array
   *   The configurations array.
   */
  public function getRenderingOptions(): array;

  /**
   * Return configurable options (with default values)
   *
   * @return array
   *   The configurations array.
   */
  public function getConfigurableOptions(): array;

  /**
   * Add a new page to the Rendering Engine PDF.
   */
  public function addPage();

  /**
   * Add a string of HTML content to the Rendering Engine PDF.
   *
   * @param string $content
   *   The string of HTML to add.
   */
  public function addContent(string $content);

  /**
   * Override entity_pdf settings form.
   *
   * @param array &$form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The override form.
   */
  public function overrideSettingsForm(array &$form, FormStateInterface $form_state): array;

  /**
   * Override entity_pdf settings form.
   *
   * @param array &$form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The override settings form.
   */
  public function overrideSettingsFormSubmit(array &$form, FormStateInterface $form_state): array;

}
