<?php

namespace Drupal\entity_pdf\Plugin\DsField\Node;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin that renders the 'pdf' link of a node.
 *
 * @todo make this more generic so it can work with any entity.
 *
 * @DsField(
 *   id = "node_pdf_link",
 *   title = @Translation("PDF link"),
 *   entity_type = "node",
 *   provider = "node"
 * )
 */
class NodePDFLink extends Link {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * Constructs a new NodePDFLink instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityDisplayRepositoryInterface $displayRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityDisplayRepository = $displayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Checks if the current user has access to view PDFs.
   *
   * @return bool
   *   The current user has access to view the PDF.
   */
  public function hasAccess(): bool {
    // Check for permission.
    return $this->currentUser->hasPermission('view entity pdf');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    if ($this->hasAccess()) {
      $config = $this->getConfiguration();

      // Easiest and probably most portable way of outputting the correct URL
      // is by overriding the entity url in the build array from the parent.
      // This will need to be kept in sync with any changes to
      // \Drupal\ds\Plugin\DsField\Link between core updates. (if any)
      $build = parent::build();
      if ($build['#context']['is_link']) {
        $route_parameters = [
          'entity' => $this->entity()->id(),
          'view_mode' => $config['view mode'],
        ];
        $entity_url = new Url('entity_pdf.node', $route_parameters);
        if (!empty($config['link class'])) {
          $entity_url->setOption('attributes', ['class' => explode(' ', $config['link class'])]);
        }
        $build['#context']['entity_url'] = $entity_url;
      }
    }
    else {
      $build = [];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    // @todo dependency injection.
    $view_mode_options = $this->entityDisplayRepository->getViewModeOptionsByBundle($this->getEntityTypeId(), $this->bundle());

    $form['view mode'] = [
      '#type' => 'select',
      '#title' => 'View mode',
      '#default_value' => $config['view mode'],
      '#options' => $view_mode_options,
    ];

    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings): array {
    $config = $this->getConfiguration();

    $summary = [];
    if (!empty($config['view mode'])) {
      $summary[] = 'View mode: ' . $config['view mode'];
    }

    return parent::settingsSummary($settings) + $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();

    $configuration['link text'] = 'Download PDF';
    $configuration['view mode'] = 'default';

    return $configuration;
  }

}
