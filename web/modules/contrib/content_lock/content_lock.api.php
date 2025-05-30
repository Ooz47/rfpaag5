<?php

/**
 * @file
 * Hooks provided by the Content Lock module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Determine whether an entity is lockable.
 *
 * Called from isLockable() which is in turn
 * called from any code which is conditional upon an entity being
 * lockable or not. If this hook returns an affirmative and allows an
 * entity to be locked at one point but later on returns a negative on
 * the same entity, any existing locks for the entity will be ignored. So
 * this hook can control whether content_lock is completely
 * disabled for an entity (such that even recorded locks for an entity can
 * be ignored with this hook).
 *
 * What this hook does NOT do is prevent someone from editing an
 * un-lockable entity. There is not yet a method of doing this without
 * hooking into the entity hooks system yourself.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity.
 * @param array $config
 *   Data from this configuration object.
 * @param string|null $form_op
 *   (optional) The form operation.
 *
 * @return bool
 *   TRUE if the entity should be considered lockable (this should be
 *   the default return value) or FALSE if the entity may not be
 *   considered lockable.
 */
function hook_content_lock_entity_lockable(\Drupal\Core\Entity\EntityInterface $entity, array $config, ?string $form_op = NULL): bool {
  if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'article' && $entity->id() === 1) {
    return FALSE;
  }

  return TRUE;
}

/**
 * @} End of "addtogroup hooks".
 */
