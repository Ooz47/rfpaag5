<?php

namespace Drupal\entity_pdf\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EntityPdfRenderingEngine item annotation object.
 *
 * @see \Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineManager
 * @see plugin_api
 *
 * @Annotation
 */
class EntityPdfRenderingEngine extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
