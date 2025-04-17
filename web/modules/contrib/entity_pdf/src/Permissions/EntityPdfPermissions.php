<?php

namespace Drupal\entity_pdf\Permissions;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Entity Pdf Permissions.
 */
class EntityPdfPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor of Entity Pdf Permissions.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Returns Entity Pdf Permissions array.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions(): array {
    $permissions = [];

    try {
      $storage = $this->entityTypeManager->getStorage('entity_view_display');
      $view_displays = $storage->loadMultiple();
      foreach ($view_displays as $view_display) {
        if ($view_display instanceof EntityViewDisplay) {
          $permissions += [
            'view ' . $view_display->id() . ' pdf' => [
              'title' => $this->t('View PDF for <em>@id</em>', ['@id' => $view_display->id()]),
            ],
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->getLogger('entity_pdf')->error($e->getMessage());
    }
    return $permissions;
  }

}
