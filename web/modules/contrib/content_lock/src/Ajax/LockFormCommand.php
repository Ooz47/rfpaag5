<?php

namespace Drupal\content_lock\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to lock current form.
 *
 * @ingroup ajax
 */
class LockFormCommand implements CommandInterface {

  protected $lockable;

  protected $lock;

  /**
   * LockFormCommand constructor.
   *
   * @param bool $lockable
   *   Whether the form is lockable.
   * @param bool $lock
   *   Whether to lock the form.
   */
  public function __construct($lockable = FALSE, $lock = FALSE) {
    $this->lockable = $lockable;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'lockForm',
      'selector' => '',
      'lockable' => $this->lockable,
      'lock' => $this->lock,
    ];
  }

}
