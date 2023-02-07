<?php

namespace Drupal\block_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\block_content\BlockContentTypeInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BlockContentController extends ControllerBase {

  /**
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * The custom block type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentTypeStorage;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('block_content'),
      $entity_type_manager->getStorage('block_content_type'),
      $container->get('theme_handler')
    );
  }

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The custom block storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_type_storage
   *   The custom block type storage.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(EntityStorageInterface $block_content_storage, EntityStorageInterface $block_content_type_storage, ThemeHandlerInterface $theme_handler) {
    $this->blockContentStorage = $block_content_storage;
    $this->blockContentTypeStorage = $block_content_type_storage;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Displays add custom block links for available types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the custom block types that can be added or
   *   if there is only one custom block type defined for the site, the function
   *   returns the custom block add page for that custom block type.
   */
  public function add(Request $request) {
    $types = $this->blockContentTypeStorage->loadMultiple();
    uasort($types, [$this->blockContentTypeStorage->getEntityType()->getClass(), 'sort']);
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any block types yet. Go to the <a href=":url">block type creation page</a> to add a new block type.', [
          ':url' => Url::fromRoute('block_content.type_add')->toString(),
        ]),
      ];
    }

    return ['#theme' => 'block_content_add_list', '#content' => $types];
  }

  /**
   * Presents the custom block creation form.
   *
   * @param \Drupal\block_content\BlockContentTypeInterface $block_content_type
   *   The custom block type to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function addForm(BlockContentTypeInterface $block_content_type, Request $request) {
    $block = $this->blockContentStorage->create([
      'type' => $block_content_type->id(),
    ]);
    if (($theme = $request->query->get('theme')) && in_array($theme, array_keys($this->themeHandler->listInfo()))) {
      // We have navigated to this page from the block library and will keep track
      // of the theme for redirecting the user to the configuration page for the
      // newly created block in the given theme.
      $block->setTheme($theme);
    }
    return $this->entityFormBuilder()->getForm($block);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\block_content\BlockContentTypeInterface $block_content_type
   *   The custom block type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(BlockContentTypeInterface $block_content_type) {
    return $this->t('Add %type custom block', ['%type' => $block_content_type->label()]);
  }

  /**
   * Provides a redirect to the list of custom block types.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
   *   /admin/structure/block-content directly instead of
   *   /admin/structure/block/block-content/types.
   *
   * @see https://www.drupal.org/node/3320855
   */
  public function blockContentTypeRedirect(): RedirectResponse {
    @trigger_error('The path /admin/structure/block/block-content/types is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use /admin/structure/block-content. See https://www.drupal.org/node/3320855.', E_USER_DEPRECATED);
    $route = 'entity.block_content_type.collection';
    $params = [
      '%old_path' => Url::fromRoute("$route.bc")->toString(),
      '%new_path' => Url::fromRoute($route)->toString(),
      '%change_record' => 'https://www.drupal.org/node/3320855',
    ];
    $warning_message = $this->t('You have been redirected from %old_path. Update links, shortcuts, and bookmarks to use %new_path.', $params);
    $this->messenger()->addWarning($warning_message);
    $this->getLogger('block_content')->warning('A user was redirected from %old_path to %new_path. This redirect will be removed in a future version of Drupal. Update links, shortcuts, and bookmarks to use %new_path. See %change_record for more information.', $params);

    return $this->redirect($route, [], [], 301);
  }

}
