<?php

namespace Drupal\br_frontend;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\br_country\CurrentCountryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\br_ips\IndustryManagerInterface;

abstract class OverviewServiceBase implements OverviewServiceInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The product search index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  protected $searchIndex;

  /**
   * The product search server.
   *
   * @var \Drupal\search_api\Entity\Server
   */
  protected $searchServer;

  /**
   * Search API query interface.
   *
   * @var \Drupal\search_api\Query\QueryInterface
   */
  protected $query;

  /**
   * The industry manager.
   *
   * @var \Drupal\br_ips\IndustryManagerInterface
   */
  protected $industryManager;

  /**
   * The current country.
   *
   * @var \Drupal\br_country\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * The machine name of the content Search API index.
   */
  const CONTENT_SEARCH_INDEX = 'content';

  /**
   * The machine name of the content Search API server.
   */
  const CONTENT_SEARCH_SERVER = 'site_wide';

  /**
   * F06OverviewService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\br_ips\IndustryManagerInterface $industry_manager
   * @param \Drupal\br_country\CurrentCountryInterface $current_country
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, AccountProxyInterface $current_user, RequestStack $request_stack, IndustryManagerInterface $industry_manager, CurrentCountryInterface $current_country) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->currentCountry = $current_country;
    $this->industryManager = $industry_manager;
    $this->request = $request_stack->getCurrentRequest();
    $this->searchIndex = $this->entityTypeManager->getStorage('search_api_index')
      ->load(self::CONTENT_SEARCH_INDEX);
    $this->searchServer = $this->entityTypeManager->getStorage('search_api_server')
      ->load(self::CONTENT_SEARCH_SERVER);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('br_ips.industry_manager'),
      $container->get('br_country.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxonomyTerms($vid, $cache = FALSE) {
    $options = [];

    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree($vid, 0, NULL, TRUE);

    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($terms as $tid => $term) {

      // Translate the term to the current language.
      if ($term->language()->getId() != $current_language) {
        if ($term->hasTranslation($current_language)) {
          $term = $term->getTranslation($current_language);
        }
      }

      if ($cache instanceof CacheableDependencyInterface) {
        $cache->addCacheableDependency($term);
      }

      $options[$term->id()] = $term->label();
    }

    return $options;
  }

}
