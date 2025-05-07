<?php

namespace Drupal\content_lock\Controller;

use Drupal\content_lock\Ajax\LockFormCommand;
use Drupal\content_lock\ContentLock\ContentLockInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Content Lock.
 *
 * @package Drupal\content_lock\Controller
 */
class ContentLockController extends ControllerBase {

  public function __construct(protected ContentLockInterface $lockService) {
  }

  /**
   * Custom callback for the create lock route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param string $langcode
   *   The langcode.
   * @param string $form_op
   *   The form operation.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   *
   * @see \Drupal\content_lock\Routing\ContentLockRoutes::routes()
   */
  public function createLockCall(Request $request, ContentEntityInterface $entity, string $langcode, string $form_op): AjaxResponse {
    $response = new AjaxResponse();

    // Not lockable entity or entity creation.
    if (!$this->lockService->isLockable($entity, $form_op) || is_null($entity->id())) {
      $lockable = FALSE;
      $lock = FALSE;
    }
    else {
      $entity = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity;
      $lockable = TRUE;
      $destination = $request->query->get('destination') ?: $entity->toUrl('edit-form')->toString();
      $lock = $this->lockService->locking($entity, $form_op, (int) $this->currentUser()->id(), FALSE, $destination);

      // Render status messages from locking service.
      $response->addCommand(new PrependCommand('', ['#type' => 'status_messages']));

      if ($lock && !$this->lockService->isJsUnlock($entity->getEntityTypeId())) {
        $language = $this->languageManager()->getLanguage($langcode);
        $url = $entity->toUrl('canonical', ['language' => $language]);
        $unlock_button = $this->lockService->unlockButton($entity, $form_op, $url->toString());
        $response->addCommand(new AppendCommand('.content-lock-actions.form-actions', $unlock_button));
      }
    }
    $response->addCommand(new LockFormCommand($lockable, $lock));

    return $response;
  }

  /**
   * Custom callback for the release lock route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The locked entity.
   * @param string $form_op
   *   The form op.
   *
   * @see \Drupal\content_lock\Routing\ContentLockRoutes::routes()
   */
  public function releaseCall(ContentEntityInterface $entity, string $form_op) {
    $this->lockService->release($entity, $form_op, $this->currentUser()->id());
    return [];
  }

  /**
   * Custom access checker for the create lock requirements route.
   *
   * @see \Drupal\content_lock\Routing\ContentLockRoutes::routes()
   */
  public function access(ContentEntityInterface $entity, AccountInterface $account): AccessResultInterface {
    return $entity->access('update', $account, TRUE);
  }

}
