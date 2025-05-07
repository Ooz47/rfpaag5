<?php

namespace Drupal\content_lock\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Builds up the content lock routes on all content entities.
 *
 * @package Drupal\content_lock\Routing
 */
class ContentLockRoutes implements ContainerInjectionInterface {

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Creates routes for each entity type that uses content locking.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   The array of routes.
   */
  public function routes(): array {
    $routes = [];

    $definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $routes['content_lock.break_lock.' . $definition->id()] = new Route(
          '/admin/lock/break/' . $definition->id() . '/{entity}/{langcode}/{form_op}',
          [
            '_form' => $definition->getHandlerClass('break_lock_form'),
            '_title' => 'Break lock',
          ],
          [
            '_custom_access' => $definition->getHandlerClass('break_lock_form') . '::access',
          ],
          [
            '_admin_route' => TRUE,
            'parameters' => [
              'entity' => [
                'type' => 'entity:' . $definition->id(),
              ],
            ],
          ]
        );
        $routes['content_lock.create_lock.' . $definition->id()] = new Route(
          '/admin/lock/create/' . $definition->id() . '/{entity}/{langcode}/{form_op}',
          [
            '_controller' => '\Drupal\content_lock\Controller\ContentLockController::createLockCall',
          ],
          [
            '_custom_access' => '\Drupal\content_lock\Controller\ContentLockController::access',
          ],
          [
            'parameters' => [
              'entity' => [
                'type' => 'entity:' . $definition->id(),
              ],
            ],
          ]
        );
        $routes['content_lock.release_lock.' . $definition->id()] = new Route(
          '/admin/lock/release/' . $definition->id() . '/{entity}/{langcode}/{form_op}',
          [
            '_controller' => '\Drupal\content_lock\Controller\ContentLockController::releaseCall',
          ],
          [
            '_custom_access' => '\Drupal\content_lock\Controller\ContentLockController::access',
          ],
          [
            'parameters' => [
              'entity' => [
                'type' => 'entity:' . $definition->id(),
              ],
            ],
          ]
        );
      }
    }
    return $routes;
  }

}
