<?php

namespace Drupal\br_frontend;

use Drupal\br_country\CurrentCountryInterface;
use Drupal\br_ips\IndustryManagerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\system\PathBasedBreadcrumbBuilder as CorePathBasedBreadcrumbBuilder;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Provides a custom node breadcrumb builder that takes into account path prefixes.
 */
class PathBasedBreadcrumbBuilder extends CorePathBasedBreadcrumbBuilder {

  /**
   * The menu link content storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $menuLinkContentStorage;

  /**
   * The menu active trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The industry manager service.
   *
   * @var \Drupal\br_ips\IndustryManagerInterface
   */
  protected $industryManager;

  /**
   * The the current domain.
   *
   * @var string
   */
  protected $currentDomain;

  /**
   * The the menu name.
   *
   * @var string
   */
  protected $menuName;

  /**
   * The the current language id.
   *
   * @var string
   */
  protected $currentLanguageId;

  /**
   * @inheritDoc
   */
  public function __construct(
    RequestContext $context,
    AccessManagerInterface $access_manager,
    RequestMatcherInterface $router,
    InboundPathProcessorInterface $path_processor,
    ConfigFactoryInterface $config_factory,
    TitleResolverInterface $title_resolver,
    AccountInterface $current_user,
    CurrentPathStack $current_path,
    LanguageManagerInterface $language_manager,
    CurrentCountryInterface $current_country,
    EntityTypeManagerInterface $entity_type_manager,
    MenuActiveTrailInterface $menu_active_trail,
    IndustryManagerInterface $industry_manager
  ) {
    parent::__construct($context, $access_manager, $router, $path_processor, $config_factory, $title_resolver, $current_user, $current_path);
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->menuActiveTrail = $menu_active_trail;
    $this->currentDomain = $current_country->getSlug();
    $this->currentLanguageId = $language_manager->getCurrentLanguage()->getId();
    $this->menuName = "main-$this->currentDomain";
    $this->industryManager = $industry_manager;
  }

  /**
   * @inheritDoc
   */
  public function build(RouteMatchInterface $route_match) {
    // On admin pages use the original builder.
    $current_path_is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route_match->getRouteObject());

    if($current_path_is_admin) {
      return parent::build($route_match);
    }

    $breadcrumb = new Breadcrumb();
    $exclude = array();

    // Don't show a link to the front-page path.
    $front = $this->config->get('page.front');
    $exclude[$front] = TRUE;

    // /user is just a redirect, so skip it.
    // @todo Find a better way to deal with /user.
    $exclude['/user'] = TRUE;

    // Add the url.path.parent cache context. This code ignores the last path
    // part so the result only depends on the path parents.
    $breadcrumb->addCacheContexts(['url.path.parent']);

    $links = $this->getLinks($breadcrumb);

    // If the link is only one then do not show it
    // e.g when on the industry page.
    if(count($links) == 1) {
      $links = [];
    }

    return $breadcrumb->setLinks($links);
  }

  /**
   * @param \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
   *   The breadcrumb instance.
   *
   * @return array
   *   The list of links.
   */
  protected function getLinks(Breadcrumb $breadcrumb) {
    $links = [];
    $this->generateLinks($links, $breadcrumb);
    $this->removeDuplicatedLinks($links);
    return $links;
  }

  /**
   * Generate links.
   *
   * @param $links
   *   A list of renderable links.
   *
   * @param \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
   *   The breadcrumb instance.
   */
  protected function generateLinks(&$links, Breadcrumb $breadcrumb) {
    $path = str_replace("/$this->currentDomain/$this->currentLanguageId/", '', $this->context->getPathInfo());

    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $active_link */
    $active_link = $this->menuActiveTrail->getActiveLink($this->menuName);

    if ($active_link) {
      $active_trail_ids = $this->menuActiveTrail->getActiveTrailIds($this->menuName);

      // Get the menu links in the active trail.
      $menu_links = [];

      foreach ($active_trail_ids as $id) {
        if (empty($id)) {
          continue;
        }
        // Get the UUID of the parent menu links.
        // For the parent l only have access to the UUID.
        $uuid = explode(':', $id);

        // Get the menu link contents.
        $menu_link_content = $this->menuLinkContentStorage->loadByProperties(array('uuid' => $uuid[1]));
        $menu_links[] = reset($menu_link_content);
      }

      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
      // Reverse the list so that the first index is the super parent.
      foreach (array_reverse($menu_links) as $menu_link) {
        $breadcrumb->addCacheableDependency($menu_link);
        $path = $menu_link->getUrlObject()->toString();

        $link = $this->getLink($breadcrumb, $path);
        if ($link) {
          $links[] = $link;
        }
      }

    }
    else {

      $link = '';

      // Add the industrial home link.
      $node = $this->industryManager->getCurrentNode();

      if ($node) {
        $links[] = $this->getLink($breadcrumb, "/node/{$node->id()}");

        $path = "/$path";
        $link = $this->getLink($breadcrumb, $path);
      }

      if ($link) {
        $links[] = $link;
      }
    }
  }

  /**
   * Remove the last link that is a duplicate.
   *
   * For example Home > About > Overview > Overview.
   *
   * @param array $links
   *   A list of renderable links.
   */
  protected function removeDuplicatedLinks(&$links) {
    if (count($links) > 2) {
      $links_duplicate = $links;
      $last_link_instance = array_pop($links_duplicate);
      $last_link = $last_link_instance->getUrl()->toUriString();
      $second_last_link_instance = array_pop($links_duplicate);
      $second_last_link = $second_last_link_instance->getUrl()->toUriString();

      if ($last_link == $second_last_link) {
        array_pop($links);
      }
    }
  }

  /**
   * Get the link.
   *
   * @param \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
   *   The breadcrumb instance.
   *
   * @param string $path
   *   The path.
   *
   * @return \Drupal\Core\Link|null
   *   The link instance.
   */
  protected function getLink(Breadcrumb $breadcrumb, $path) {
    $link = NULL;
    $exclude = [];
    $front = $this->config->get('page.front');
    $exclude[$front] = TRUE;

    // /user is just a redirect, so skip it.
    // @todo Find a better way to deal with /user.
    $exclude['/user'] = TRUE;
    $route_request = $this->getRequestForPath($path, $exclude);

    if ($route_request) {
      $route_match = RouteMatch::createFromRequest($route_request);
      $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
      // The set of breadcrumb links depends on the access result, so merge
      // the access result's cacheability metadata.
      $breadcrumb->addCacheableDependency($access);
      if ($access->isAllowed()) {
        $title = $this->titleResolver->getTitle($route_request, $route_match->getRouteObject());
        $url = Url::fromRouteMatch($route_match);
        $link = new Link($title, $url);
      }
    }

    return $link;
  }

}
