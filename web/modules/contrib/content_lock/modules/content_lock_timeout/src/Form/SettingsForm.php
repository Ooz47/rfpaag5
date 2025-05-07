<?php

namespace Drupal\content_lock_timeout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a form for configuring the Content Lock Timeout module.
 *
 * @package Drupal\content_lock_timeout\Form
 */
class SettingsForm extends ConfigFormBase {
  use RedundantEditableConfigNamesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_lock_timeout_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['content_lock_timeout'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Lock Timeouts'),
      '#description' => $this->t('Configure automatic stale lock breaking.'),
    ];

    $form['content_lock_timeout']['content_lock_timeout_minutes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lock timeout'),
      '#description' => $this->t('The maximum time in minutes that each lock may be kept. To disable breaking locks after a timeout, please %disable the Content Lock Timeout module.', ['%disable' => Link::fromTextAndUrl($this->t('disable'), Url::fromRoute('system.modules_list'))->toString()]),
      '#maxlength' => 64,
      '#size' => 64,
      '#config_target' => 'content_lock_timeout.settings:content_lock_timeout_minutes',
    ];

    $form['content_lock_timeout']['content_lock_timeout_on_edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Break stale locks on edit'),
      '#description' => $this->t("By default, stale locks will be broken when cron is run. This option enables checking for stale locks when a user clicks a node's Edit link, enabling lower lock timeout values without having to run cron every few minutes."),
      '#config_target' => 'content_lock_timeout.settings:content_lock_timeout_on_edit',
    ];

    return parent::buildForm($form, $form_state);
  }

}
