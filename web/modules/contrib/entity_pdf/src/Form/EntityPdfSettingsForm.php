<?php

namespace Drupal\entity_pdf\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\entity_pdf\Service\EntityPdfGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Entity Pdf Setting form.
 */
class EntityPdfSettingsForm extends ConfigFormBase {

  /**
   * The Entity PDF Generator service.
   */
  protected EntityPdfGenerator $entityPdfGenerator;

  /**
   * Constructs a EntityPdfSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\entity_pdf\Service\EntityPdfGenerator $entity_pdf_generator
   *   The Entity PDF Generator service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityPdfGenerator $entity_pdf_generator,
    TypedConfigManagerInterface|null $typedConfigManager = NULL,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityPdfGenerator = $entity_pdf_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_pdf.generator'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'entity_pdf_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'entity_pdf.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('entity_pdf.settings');

    $form['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename for generated PDF documents.'),
      '#default_value' => $config->get('filename') ?: '[node:nid].pdf',
      '#description' => $this->t('You can use node tokens.'),
    ];

    $form['tempDir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Temporary Directory: DRUPAL_ROOT/'),
      '#default_value' => $config->get('tempDir') ?: 'sites/default/files/entity_pdf',
      '#description' => $this->t('Specify the tmp directory to be used for PDF fields generation.<br><b>Note: </b>Do not include a leading slash.'),
      '#placeholder' => "sites/default/files/entity_pdf",
    ];

    $form['customPdfTemplatePath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom PDF Twig template path: DRUPAL_ROOT/'),
      '#default_value' => $config->get('customPdfTemplatePath') ?: '',
      '#description' => $this->t("Specify a different 'DRUPAL_ROOT_RELATIVE_PATH' where to find your customised 'htmlpdf.html.twig' file.<br><b>Note: </b>Do not include a leading slash (and don't include the 'htmlpdf.html.twig' at th end)<br><u>If left empty the 'htmlpdf.html.twig' file bundled in the module will be used.</u>"),
      '#placeholder' => "modules/[my_custom_module]/templates",
    ];

    $form['openInBrowser'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open in browser'),
      '#default_value' => $config->get('openInBrowser') ?: FALSE,
      '#description' => $this->t('Open in the browser instead of downloading. Can be reverted in specific cases by adding ?inline=1 to a pdf links'),
    ];

    $engines = [];
    foreach ($this->entityPdfGenerator->getRenderingEngines() as $engine) {
      /** @var \Drupal\entity_pdf\Plugin\EntityPdfRenderingEngineInterface $engine */
      $engines[$engine->getPluginId()] = $engine->getName();
    }
    $form['renderingEngine'] = [
      '#type' => 'select',
      '#title' => $this->t('Rendering Engine'),
      '#options' => $engines,
      '#default_value' => $config->get('renderingEngine') ?: $this->entityPdfGenerator->getRenderingEngine()->getPluginId(),
      '#description' => $this->t('Select a pdf rendering engine. Modules could provide new rendering engines with EntityPdfRenderingEngine plugins.'),
    ];
    foreach ($this->entityPdfGenerator->getRenderingEngines() as $engine) {
      $configurableRenderingOptions = $engine->getConfigurableOptions();
      if (!empty($configurableRenderingOptions)) {
        $form['renderingEngineOptions'] = $form['renderingEngineOptions'] ?? [
          '#type' => 'vertical_tabs',
          '#title' => $this->t('Settings'),
          '#tree' => TRUE,
        ];
        $form['renderingEngineOptions'][$engine->getPluginId()] = [
          '#type' => 'details',
          '#title' => $this->t('Options for @pdf_rendering_engine', ['@pdf_rendering_engine' => $engine->getName()]),
          '#group' => 'renderingEngineOptions',
          '#tree' => TRUE,
        ];
        $engine->overrideSettingsForm($form['renderingEngineOptions'][$engine->getPluginId()], $form_state);
        $renderingOptions = $engine->getRenderingOptions();
        foreach (Element::children($form['renderingEngineOptions'][$engine->getPluginId()]) as $key) {
          if (!isset($form['renderingEngineOptions'][$engine->getPluginId()][$key]['#default_value']) && isset($renderingOptions[$key])) {
            $form['renderingEngineOptions'][$engine->getPluginId()][$key]['#default_value'] = $renderingOptions[$key];
            $form['renderingEngineOptions'][$engine->getPluginId()][$key]['#description'] = $this->t('Defaults to: @value', ['@value' => $configurableRenderingOptions[$key]]);
          }
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('entity_pdf.settings');
    $config->set('filename', $form_state->getValue('filename'));
    $config->set('tempDir', $form_state->getValue('tempDir'));
    $config->set('customPdfTemplatePath', $form_state->getValue('customPdfTemplatePath'));
    $config->set('openInBrowser', $form_state->getValue('openInBrowser'));
    $config->set('renderingEngine', $form_state->getValue('renderingEngine'));

    $renderingEngineOptions = [];
    foreach ($this->entityPdfGenerator->getRenderingEngines() as $engine) {
      if (!empty($engine->getConfigurableOptions())) {
        $renderingEngineOptions[$engine->getPluginId()] = $form_state->getValue([
          'renderingEngineOptions', $engine->getPluginId(),
        ]);
        $engine->overrideSettingsFormSubmit($form['renderingEngineOptions'][$engine->getPluginId()], $form_state);
      }
    }
    $config->set('renderingEngineOptions', $renderingEngineOptions);

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
