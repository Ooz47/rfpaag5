<?php

namespace Drupal\content_lock\ContentLock;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\content_lock\Event\ContentLockLockedEvent;
use Drupal\content_lock\Event\ContentLockReleaseEvent;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContentLock.
 *
 * The content lock service.
 */
class ContentLock implements ContentLockInterface {

  use StringTranslationTrait;

  /**
   * The content_lock.settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $currentRequest;

  public function __construct(
    protected Connection $database,
    protected ModuleHandlerInterface $moduleHandler,
    protected DateFormatterInterface $dateFormatter,
    protected AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    RequestStack $requestStack,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MessengerInterface $messenger,
    protected TimeInterface $time,
    #[Autowire(service: 'lock')]
    protected LockBackendInterface $lock,
    protected EventDispatcherInterface $eventDispatcher,
  ) {
    $this->config = $configFactory->get('content_lock.settings');
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function fetchLock(EntityInterface $entity, ?string $form_op = NULL): object|false {
    $langcode = $entity->language()->getId();
    if (!$this->isTranslationLockEnabled($entity->getEntityTypeId())) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }
    if (!$this->isFormOperationLockEnabled($entity->getEntityTypeId())) {
      $form_op = '*';
    }
    $query = $this->database->select('content_lock', 'c');
    $query->leftJoin('users_field_data', 'u', '%alias.uid = c.uid');
    $query->fields('c')
      ->fields('u', ['name'])
      ->condition('c.entity_type', $entity->getEntityTypeId())
      ->condition('c.entity_id', $entity->id())
      ->condition('c.langcode', $langcode);
    if (isset($form_op)) {
      $query->condition('c.form_op', $form_op);
    }

    return $query->execute()->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function displayLockOwner(object $lock, bool $translation_lock): string|TranslatableMarkup {
    $username = $this->entityTypeManager->getStorage('user')->load($lock->uid);
    $date = $this->dateFormatter->formatInterval($this->time->getRequestTime() - $lock->timestamp);

    if ($translation_lock) {
      $message = $this->t('This content translation is being edited by the user @name and is therefore locked to prevent other users changes. This lock is in place since @date.', [
        '@name' => $username->getDisplayName(),
        '@date' => $date,
      ]);
    }
    else {
      $message = $this->t('This content is being edited by the user @name and is therefore locked to prevent other users changes. This lock is in place since @date.', [
        '@name' => $username->getDisplayName(),
        '@date' => $date,
      ]);
    }
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function isLockedBy(EntityInterface $entity, string $form_op, int $uid): bool {
    $langcode = $entity->language()->getId();
    if (!$this->isTranslationLockEnabled($entity->getEntityTypeId())) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }
    if (!$this->isFormOperationLockEnabled($entity->getEntityTypeId())) {
      $form_op = '*';
    }
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('content_lock', 'c')
      ->fields('c')
      ->condition('entity_id', $entity->id())
      ->condition('uid', $uid)
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('langcode', $langcode)
      ->condition('form_op', $form_op);
    $num_rows = $query->countQuery()->execute()->fetchField();
    return (bool) $num_rows;
  }

  /**
   * {@inheritdoc}
   */
  public function release(EntityInterface $entity, ?string $form_op = NULL, ?int $uid = NULL): void {
    $langcode = $entity->language()->getId();
    if (!$this->isTranslationLockEnabled($entity->getEntityTypeId())) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }
    if (!$this->isFormOperationLockEnabled($entity->getEntityTypeId())) {
      $form_op = '*';
    }
    // Delete locking item from database.
    $this->lockingDelete($entity, $form_op, $uid);

    $event = new ContentLockReleaseEvent($entity->id(), $langcode, $form_op, $entity->getEntityTypeId());
    $this->eventDispatcher->dispatch($event, ContentLockReleaseEvent::EVENT_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAllUserLocks(int $uid): void {
    $this->database->delete('content_lock')
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * Save locking into database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $form_op
   *   The entity form operation.
   * @param int $uid
   *   The user uid.
   *
   * @return int|null
   *   One of the following values:
   *   - Merge::STATUS_INSERT: If the entry does not already exist,
   *     and an INSERT query is executed.
   *   - Merge::STATUS_UPDATE: If the entry already exists,
   *     and an UPDATE query is executed.
   */
  protected function lockingSave(EntityInterface $entity, string $form_op, int $uid): ?int {
    $langcode = $entity->language()->getId();
    if (!$this->isTranslationLockEnabled($entity->getEntityTypeId())) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }
    if (!$this->isFormOperationLockEnabled($entity->getEntityTypeId())) {
      $form_op = '*';
    }
    return $this->database->merge('content_lock')
      ->keys([
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'langcode' => $langcode,
        'form_op' => $form_op,
      ])
      ->fields([
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'langcode' => $langcode,
        'form_op' => $form_op,
        'uid' => $uid,
        'timestamp' => $this->time->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * Delete locking item from database.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string|null $form_op
   *   (optional) The entity form operation.
   * @param int|null $uid
   *   (optional) The user uid.
   *
   * @return int
   *   The number of rows affected by the delete query.
   */
  protected function lockingDelete(EntityInterface $entity, ?string $form_op = NULL, ?int $uid = NULL): int {
    $langcode = $entity->language()->getId();
    if (!$this->isTranslationLockEnabled($entity->getEntityTypeId())) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }
    if (!$this->isFormOperationLockEnabled($entity->getEntityTypeId())) {
      $form_op = '*';
    }
    $query = $this->database->delete('content_lock')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('langcode', $langcode);
    if (isset($form_op)) {
      $query->condition('form_op', $form_op);
    }
    if (!empty($uid)) {
      $query->condition('uid', $uid);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function verbose(): bool {
    return $this->config->get('verbose');
  }

  /**
   * {@inheritdoc}
   */
  public function locking(EntityInterface $entity, string $form_op, int $uid, bool $quiet = FALSE, ?string $destination = NULL): bool {
    $translation_lock = $this->isTranslationLockEnabled($entity->getEntityTypeId());
    $langcode = $entity->language()->getId();
    $js_unlock = $this->isJsUnlock($entity->getEntityTypeId());
    if (!$translation_lock) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }
    if (!$this->isFormOperationLockEnabled($entity->getEntityTypeId())) {
      $form_op = '*';
    }

    // Acquire a lock from the lock service to prevent a race condition when
    // checking if a lock exists and update the content lock table with the new
    // information.
    $lock_service_name = "content_lock:{$entity->getEntityTypeId()}:{$entity->id()}";
    $lock_service_acquired = $this->lock->acquire($lock_service_name);
    if (!$lock_service_acquired) {
      $this->lock->wait($lock_service_name, 5);
      $lock_service_acquired = $this->lock->acquire($lock_service_name);
    }

    // Check locking status.
    $lock = $this->fetchLock($entity, $form_op);

    // No lock yet.
    if ($lock_service_acquired && ($lock === FALSE || !is_object($lock))) {
      // Save locking into database.
      $this->lockingSave($entity, $form_op, $uid);

      if ($this->verbose() && !$quiet) {
        if ($translation_lock) {
          $message = 'This content translation is now locked against simultaneous editing.';
          if (!$js_unlock) {
            $message .= ' This content translation will remain locked if you navigate away from this page without saving or unlocking it.';
          }
        }
        else {
          $message = 'This content is now locked against simultaneous editing.';
          if (!$js_unlock) {
            $message .= ' This content will remain locked if you navigate away from this page without saving or unlocking it.';
          }
        }
      $this->messenger->addStatus($this->t($message));         
      }

      // Post locking hook.
      $event = new ContentLockLockedEvent($entity->id(), $langcode, $form_op, $uid, $entity->getEntityTypeId());
      $this->eventDispatcher->dispatch($event, ContentLockLockedEvent::EVENT_NAME);

      // Send success flag.
      $return = TRUE;
    }
    else {
      // Currently locking by other user.
      if (is_object($lock) && $lock->uid != $uid) {
        // Send message.
        $message = $this->displayLockOwner($lock, $translation_lock);
        $this->messenger->addWarning($message);

        // Higher permission user can unblock.
        if ($this->currentUser->hasPermission('break content lock')) {

          $link = Link::createFromRoute(
            $this->t('Break lock'),
            'content_lock.break_lock.' . $entity->getEntityTypeId(),
            [
              'entity' => $entity->id(),
              'langcode' => $langcode,
              'form_op' => $form_op,
            ],
            ['query' => ['destination' => $destination ?? $this->currentRequest->getRequestUri()]]
          )->toString();

          // Let user break lock.
          $this->messenger->addWarning($this->t('Click here to @link', ['@link' => $link]));
        }

        // Return FALSE flag.
        $return = FALSE;
      }
      elseif ($lock_service_acquired) {
        // Save locking into database.
        $this->lockingSave($entity, $form_op, $uid);

        // Locked by current user.
        if ($this->verbose() && !$quiet) {
          if ($translation_lock) {
            $message = 'This content translation is now locked by you against simultaneous editing.';
            if (!$js_unlock) {
              $message .= ' This content translation will remain locked if you navigate away from this page without saving or unlocking it.';
            }
          }
          else {
            $message = 'This content is now locked by you against simultaneous editing.';
            if (!$js_unlock) {
              $message .= ' This content will remain locked if you navigate away from this page without saving or unlocking it.';
            }
          }
          $this->messenger->addStatus($this->t($message));
        }

        // Send success flag.
        $return = TRUE;
      }
      else {
        $this->messenger->addWarning('This content is being edited by another user.');
        // Return FALSE flag.
        $return = FALSE;
      }
    }
    if ($lock_service_acquired) {
      $this->lock->release($lock_service_name);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isLockable(EntityInterface $entity, ?string $form_op = NULL): bool {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $config = $this->config->get("types.$entity_type");

    $allowed = TRUE;
    $this->moduleHandler->invokeAllWith('content_lock_entity_lockable', function (callable $hook) use (&$allowed, $entity, $config, $form_op) {
      if ($allowed && $hook($entity, $config, $form_op) === FALSE) {
        $allowed = FALSE;
      }
    });

    if ($allowed && is_array($config) && (in_array($bundle, $config) || in_array('*', $config))) {
      if (isset($form_op) && $this->isFormOperationLockEnabled($entity_type)) {
        $mode = $this->config->get("form_op_lock.$entity_type.mode");
        $values = $this->config->get("form_op_lock.$entity_type.values");

        if ($mode == self::FORM_OP_MODE_BLACKLIST) {
          return !in_array($form_op, $values);
        }
        elseif ($mode == self::FORM_OP_MODE_WHITELIST) {
          return in_array($form_op, $values);
        }
        $this->messenger->addStatus($this->t($message));
      }
      return TRUE;
    }

    // Always return FALSE.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isJsLock(string $entity_type_id): bool {
    return in_array($entity_type_id, $this->config->get("types_js_lock") ?: [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isJsUnlock(string $entity_type_id): bool {
    return in_array($entity_type_id, $this->config->get("types_js_unlock") ?: [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function unlockButton(EntityInterface $entity, ?string $form_op, ?string $destination): array {
    $unlock_url_options = [];
    if ($destination) {
      $unlock_url_options['query'] = ['destination' => $destination];
    }
    $route_parameters = [
      'entity' => $entity->id(),
      'langcode' => $this->isTranslationLockEnabled($entity->getEntityTypeId()) ? $entity->language()->getId() : LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'form_op' => $this->isFormOperationLockEnabled($entity->getEntityTypeId()) ? $form_op : '*',
    ];
    return [
      '#type' => 'link',
      '#title' => $this->t('Unlock'),
      '#access' => TRUE,
      '#attributes' => [
        'class' => ['button'],
      ],
      '#url' => Url::fromRoute('content_lock.break_lock.' . $entity->getEntityTypeId(), $route_parameters, $unlock_url_options),
      '#weight' => 99,
      '#gin_action_item' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslationLockEnabled(string $entity_type_id): bool {
    return $this->moduleHandler->moduleExists('conflict') && in_array($entity_type_id, $this->config->get("types_translation_lock"));
  }

  /**
   * {@inheritdoc}
   */
  public function hasLockEnabled(string $entity_type_id): bool {
    return !empty($this->config->get("types")[$entity_type_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function isFormOperationLockEnabled(string $entity_type_id): bool {
    return $this->config->get("form_op_lock.$entity_type_id.mode") != self::FORM_OP_MODE_DISABLED;
  }

}
