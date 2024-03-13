<?php

namespace Drupal\pdf_generator\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pdf_generator\DomPdfGenerator;
use Drupal\views\Plugin\views\display\PathPluginBase;
use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface;
use Drupal\views\Views;

/**
 * The plugin that handles a feed, such as RSS or atom.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "pdf_generator_views_display",
 *   title = @Translation("PDF"),
 *   help = @Translation("Display the view as a pdf."),
 *   uses_route = TRUE,
 *   admin = @Translation("PDF"),
 *   theme = "views_view_pdf_generator",
 *   returns_response = TRUE
 * )
 */
class Pdf extends PathPluginBase implements ResponseDisplayPluginInterface, ContainerFactoryPluginInterface {

  // @todo This will be modified to get pdf generators from plugins.
  /**
   * The date formatter service.
   *
   * @var \Drupal\pdf_generator\DomPdfGenerator
   */
  protected $pdfGenerator;

  /**
   * Constructs a new Date instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\pdf_generator\DomPdfGenerator $pdfGenerator
   *   The pdf generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, StateInterface $state, DomPdfGenerator $pdfGenerator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider, $state);
    $this->pdfGenerator = $pdfGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('state'),
      $container->get('pdf_generator.dompdf_generator')
    );
  }

  /**
   * Whether the display allows the use of AJAX or not.
   *
   * @var bool
   */
  protected $ajaxEnabled = FALSE;

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @var bool
   */
  protected $usesPager = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'pdf_geneartor';
  }

  /**
   * {@inheritdoc}
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    // $build = static::buildBasicRenderable($view_id, $display_id, $args);
    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $view->setArguments($args);
    $build = $view->render($display_id);
    $renderer = \Drupal::service('renderer');
    $output = [
      '#markup' => (string) $renderer->renderRoot($build),
    ];
    if ($view->display_handler->getOption('sitename_title') === 1) {
      $config = \Drupal::config('system.site');
      $titleValue = $config->get('name');
    }
    elseif ($view->display_handler->getOption('field_title') === 1) {
      $fieldName = $view->display_handler->getOption('select_field_title');
      $entityTypeManager = \Drupal::entityTypeManager();
      $node = $entityTypeManager->getStorage('node')->load(reset($args));
      $titleValue = $node->get($fieldName)->value;
    }
    else {
      $titleValue = $view->getTitle();
    }
    $title = [
      '#markup' => $titleValue,
      '#allowed_tags' => Xss::getHtmlTagList()
    ];
    $pageSize = !empty($view->display_handler->getOption('paper_size')) ?
      $view->display_handler->getOption('paper_size') :
      'a4';
    $showPagination = !empty($view->display_handler->getOption('show_pagination')) ?
      $view->display_handler->getOption('show_pagination') :
      0;
    $paginationX = !empty($view->display_handler->getOption('pagination_x')) ?
      $view->display_handler->getOption('pagination_x') :
      0;
    $paginationY = !empty($view->display_handler->getOption('pagination_y')) ?
      $view->display_handler->getOption('pagination_y') :
      0;
    $disposition = !empty($view->display_handler->getOption('paper_disposition')) ?
      $view->display_handler->getOption('paper_disposition') :
      'portrait';
    $textCss = !empty($view->display_handler->getOption('inline_css')) ?
      $view->display_handler->getOption('inline_css') :
      NULL;
    $fileCss = !empty($view->display_handler->getOption('file_css')) ?
      $view->display_handler->getOption('file_css') :
      NULL;
    $forceDownload = !empty($view->display_handler->getOption('force_download')) ?
      $view->display_handler->getOption('force_download') :
      FALSE;

    $response = \Drupal::service('pdf_generator.dompdf_generator')->getResponse(
      $title,
      $output,
      FALSE,
      [],
      $pageSize,
      $showPagination,
      $paginationX,
      $paginationY,
      $disposition,
      $textCss,
      $fileCss,
      $forceDownload
    );
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    parent::execute();

    return $this->view->render();
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    $view = $this->view;
    $build = $view->render($view->current_display);
    $renderer = \Drupal::service('renderer');
    $output = [
      '#markup' => (string) $renderer->renderRoot($build),
    ];
    if ($view->display_handler->getOption('sitename_title') === 1) {
      $config = \Drupal::config('system.site');
      $titleValue = $config->get('name');
    }
    elseif ($view->display_handler->getOption('field_title') === 1) {
      $fieldName = $view->display_handler->getOption('select_field_title');
      $entityTypeManager = \Drupal::entityTypeManager();
      $node = $entityTypeManager->getStorage('node')->load(reset($view->args));
      $titleValue = $node->get($fieldName)->value;
    }
    else {
      $titleValue = $view->getTitle();
    }
    $title = [
      '#markup' => $titleValue,
      '#allowed_tags' => Xss::getHtmlTagList()
    ];
    $pageSize = !empty($view->display_handler->getOption('paper_size')) ?
      $view->display_handler->getOption('paper_size') :
      'a4';
    $showPagination = !empty($view->display_handler->getOption('show_pagination')) ?
      $view->display_handler->getOption('show_pagination') :
      0;
    $paginationX = !empty($view->display_handler->getOption('pagination_x')) ?
      $view->display_handler->getOption('pagination_x') :
      0;
    $paginationY = !empty($view->display_handler->getOption('pagination_y')) ?
      $view->display_handler->getOption('pagination_y') :
      0;
    $disposition = !empty($view->display_handler->getOption('paper_disposition')) ?
      $view->display_handler->getOption('paper_disposition') :
      'portrait';
    $textCss = !empty($view->display_handler->getOption('inline_css')) ?
      $view->display_handler->getOption('inline_css') :
      NULL;
    $fileCss = !empty($view->display_handler->getOption('file_css')) ?
      $view->display_handler->getOption('file_css') :
      NULL;
    $forceDownload = FALSE;

    $response = \Drupal::service('pdf_generator.dompdf_generator')->getResponse(
      $title,
      $output,
      TRUE,
      [],
      $pageSize,
      $showPagination,
      $paginationX,
      $paginationY,
      $disposition,
      $textCss,
      $fileCss,
      $forceDownload
    );
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultableSections($section = NULL) {
    $sections = parent::defaultableSections($section);
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['displays'] = ['default' => []];
    $options['style']['contains']['type']['default'] = 'pdf_generator_views_style_default';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // Since we're childing off the 'path' type, we'll still *call* our
    // category 'page' but let's override it so it says feed settings.
    $categories['page'] = [
      'title' => $this->t('PDF settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    if ($this->getOption('sitename_title')) {
      $options['title']['value'] = $this->t('Using the site name');
    }

    if ($this->getOption('field_title')) {
      $options['title']['value'] = $this->t('Using field value');
    }

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      $displays = $this->view->storage->get('display');
      if (!empty($displays[$display])) {
        $attach_to = $displays[$display]['display_title'];
      }
    }

    if (!isset($attach_to)) {
      $attach_to = $this->t('None');
    }

    $options['displays'] = [
      'category' => 'page',
      'title' => $this->t('Attach to'),
      'value' => $attach_to,
    ];

    $options['inline_css'] = [
      'category' => 'page',
      'title' => $this->t('Inline CSS'),
      'value' => !empty($this->getOption('inline_css')) ? $this->t('Yes') : $this->t('No'),
    ];

    $options['file_css'] = [
      'category' => 'page',
      'title' => $this->t('File CSS'),
      'value' => !empty($this->getOption('file_css')) ? $this->getOption('file_css') : $this->t('None'),
    ];

    $dispositions = $this->pdfGenerator->availableDisposition();
    $options['paper_disposition'] = [
      'category' => 'page',
      'title' => $this->t('Disposition'),
      'value' => !empty($this->getOption('paper_disposition')) ? $dispositions[$this->getOption('paper_disposition')] : $this->t('None'),
    ];

    $sizes = $this->pdfGenerator->pageSizes();
    $options['paper_size'] = [
      'category' => 'page',
      'title' => $this->t('Paper size'),
      'value' => !empty($this->getOption('paper_size')) ? $sizes[$this->getOption('paper_size')] : $this->t('None'),
    ];

    $options['show_pagination'] = [
      'category' => 'page',
      'title' => $this->t('Show pagination'),
      'value' => !empty($this->getOption('show_pagination')) ? ($this->getOption('show_pagination') == 1 ? $this->t('Page number (X)') : $this->t('Page number and total (X of Y)')) : $this->t('None'),
      1 => $this->t('Page number (X)'),
      2 => $this->t('Page number and total (X of Y)'),

    ];

    $options['force_download'] = [
      'category' => 'page',
      'title' => $this->t('Force Download'),
      'value' => !empty($this->getOption('force_download')) ?  $this->t('Yes') : $this->t('No'),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // It is very important to call the parent function here.
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'title':
        $title = $form['title'];
        // A little juggling to move the 'title' field beyond our checkbox.
        unset($form['title']);
        $form['sitename_title'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use the site name for the title'),
          '#default_value' => $this->getOption('sitename_title'),
        ];
        $labels = ['' => $this->t('- None -')];
        $fieldsLabels = $this->view->display_handler->getFieldLabels();
        $fieldsLabels = array_merge($labels, $fieldsLabels);
        $form['field_title'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use field value as title'),
          '#default_value' => $this->getOption('field_title'),
        ];
        $form['select_field_title'] = [
          '#type' => 'select',
          '#title' => $this->t('Select field for the title.'),
          '#options' => $fieldsLabels,
          '#default_value' => $this->getOption('select_field_title'),
        ];
        $form['select_field_title']['#states'] = [
          'visible' => [
            ':input[name="field_title"]' => ['checked' => TRUE],
          ],
        ];
        $form['title'] = $title;
        $form['title']['#states'] = [
          'visible' => [
            ':input[name="sitename_title"]' => ['checked' => FALSE],
            ':input[name="field_title"]' => ['checked' => FALSE],
          ],
        ];
        $form['field_title']['#states'] = [
          'visible' => [
            ':input[name="sitename_title"]' => ['checked' => FALSE],
          ],
        ];
        $form['sitename_title']['#states'] = [
          'visible' => [
            ':input[name="field_title"]' => ['checked' => FALSE],
          ],
        ];
        break;

      case 'displays':
        $form['#title'] .= $this->t('Attach to');
        $displays = [];
        foreach ($this->view->storage->get('display') as $display_id => $display) {
          // @todo The display plugin should have display_title and id as well.
          if ($this->view->displayHandlers->has($display_id) && $this->view->displayHandlers->get($display_id)->acceptAttachments()) {
            $displays[$display_id] = $display['display_title'];
          }
        }
        $form['displays'] = [
          '#title' => $this->t('Displays'),
          '#type' => 'checkboxes',
          '#description' => $this->t('The feed icon will be available only to the selected displays.'),
          '#options' => array_map('\Drupal\Component\Utility\Html::escape', $displays),
          '#default_value' => $this->getOption('displays'),
        ];
        break;

      case 'inline_css':
        $form['inline_css'] = [
          '#title' => $this->t('Inline CSS'),
          '#type' => 'textarea',
          '#description' => $this->t('These styles are attached to the pdf.'),
          '#default_value' => $this->getOption('inline_css'),
        ];
        break;

      case 'file_css':
        $form['file_css'] = [
          '#title' => $this->t('File CSS'),
          '#type' => 'textfield',
          '#description' => $this->t('The file will be read and attached to the pdf.'),
          '#default_value' => $this->getOption('file_css'),
        ];

        break;

      case 'paper_disposition':
        $form['paper_disposition'] = [
          '#title' => $this->t('Disposition'),
          '#type' => 'select',
          '#options' => $this->pdfGenerator->availableDisposition(),
          '#required' => TRUE,
          '#description' => $this->t('The disposition of each page of the PDF.'),
          '#default_value' => $this->getOption('paper_disposition'),
        ];

        break;

      case 'paper_size':
        $form['paper_size'] = [
          '#title' => $this->t('Paper size'),
          '#type' => 'select',
          '#options' => $this->pdfGenerator->pageSizes(),
          '#required' => TRUE,
          '#description' => $this->t('The disposition of each page of the PDF.'),
          '#default_value' => !empty($this->getOption('paper_size')) ? $this->getOption('paper_size') : 'a4',
        ];

        break;

      case 'show_pagination':
        $form['show_pagination'] = [
          '#title' => $this->t('Show pagination'),
          '#type' => 'select',
          '#options' => [
            0 => $this->t('None'),
            1 => $this->t('Page number (X)'),
            2 => $this->t('Page number and total (X of Y)'),
          ],
          '#required' => TRUE,
          '#description' => $this->t('The disposition of each page of the PDF.'),
          '#default_value' => !empty($this->getOption('show_pagination')) ? $this->getOption('show_pagination') : 0,
        ];
        $form['pagination_x'] = [
          '#title' => $this->t('Position X'),
          '#type' => 'number',
          '#required' => FALSE,
          '#description' => $this->t('The horizontal position of paginator. In A4 pager size the max X is 595.28.'),
          '#default_value' => !empty($this->getOption('pagination_x')) ? $this->getOption('pagination_x') : 0,
        ];
        $form['pagination_y'] = [
          '#title' => $this->t('Position Y'),
          '#type' => 'number',
          '#required' => FALSE,
          '#description' => $this->t('The vertical position of paginator. In A4 pager size the max X is 841.89.'),
          '#default_value' => !empty($this->getOption('pagination_y')) ? $this->getOption('pagination_y') : 0,
        ];

        break;

      case 'force_download':
        $form['force_download'] = [
          '#title' => $this->t('Force Download'),
          '#type' => 'checkbox',
          '#required' => FALSE,
          '#description' => $this->t('If enabled the PDF file is forced to download and do not open on browser.'),
          '#default_value' => !empty($this->getOption('force_download')) ? $this->getOption('force_download') : FALSE,
        ];

        break;


      case 'path':
        $form['path']['#description'] = $this->t('This view will be displayed by visiting this path on your site. It is recommended that the path be something like "path/%/%/feed" or "path/%/%/rss.xml", putting one % in the path for each contextual filter you have defined in the view.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'title':
        if ($form_state->getValue('sitename_title') === 1) {
          $this->setOption('sitename_title', $form_state->getValue('sitename_title'));
        }
        elseif ($form_state->getValue('field_title') === 1) {
          $this->setOption('field_title', $form_state->getValue('field_title'));
          $this->setOption('select_field_title', $form_state->getValue('select_field_title'));
        }
        else {
          $this->setOption('sitename_title', 0);
          $this->setOption('field_title', 0);
        }
        break;

      case 'displays':
        $this->setOption($section, $form_state->getValue($section));
        break;

      case 'inline_css':
        $this->setOption('inline_css', $form_state->getValue('inline_css'));
        break;

      case 'file_css':
        $this->setOption('file_css', $form_state->getValue('file_css'));
        break;

      case 'paper_disposition':
        $this->setOption('paper_disposition', $form_state->getValue('paper_disposition'));
        break;

      case 'paper_size':
        $this->setOption('paper_size', $form_state->getValue('paper_size'));
        break;

      case 'show_pagination':
        $this->setOption('show_pagination', $form_state->getValue('show_pagination'));
        $this->setOption('pagination_x', $form_state->getValue('pagination_x'));
        $this->setOption('pagination_y', $form_state->getValue('pagination_y'));
        break;

      case 'force_download':
        $this->setOption('force_download', $form_state->getValue('force_download'));
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $clone, $display_id, array &$build) {
    $displays = $this->getOption('displays');
    if (empty($displays[$display_id])) {
      return;
    }

    // Defer to the feed style; it may put in meta information, and/or
    // attach a feed icon.
    $clone->setArguments($this->view->args);
    $clone->setDisplay($this->display['id']);
    $clone->buildTitle();
    if ($plugin = $clone->display_handler->getPlugin('style')) {
      $plugin->attachTo($build, $display_id, $clone->getUrl(), $clone->getTitle());
      foreach ($clone->feedIcons as $feed_icon) {
        $this->view->feedIcons[] = $feed_icon;
      }
    }

    // Clean up.
    $clone->destroy();
    unset($clone);
  }

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return TRUE;
  }

}
