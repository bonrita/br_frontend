<?php

/**
 * @file
 *   This file contains functionality that gets all news items of a domain.
 */

namespace Drupal\br_frontend;


use Drupal\br_country\CurrentCountryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\br_ips\IndustryManagerInterface;

/**
 * Class F06OverviewService
 *
 * @package Drupal\br_frontend
 */
class F06OverviewService extends OverviewServiceBase {

  /**
   * Number of items per page.
   */
  const ITEMS_PER_PAGE = 6;

  /**
   * The number of pages in the list
   */
  const PAGER_QUANTITY = 3;

  /**
   * {@inheritdoc}
   */
  public function overview() {
    $build = [];
    $items = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $account = $this->currentUser->getAccount();

    $keys = $this->getSeachKeys();
    $this->buildQuery($keys, $current_langcode);

    $this->searchServer->search($this->query);
    $results = $this->query->getResults();
    $result_count = $results->getResultCount();

    if ($result_count) {
      $search_results = $results->getResultItems();

      foreach ($search_results as $item_id => $search_result) {

        // Check content access
        if (!$search_result->checkAccess($account)) {
          unset($search_results[$item_id]);
        }

        $source = $search_result->getDatasource();
        // Result search_result ID: :entity:[entity type]/[entity id]:[entity langcode]
        // ID format: [entity id]:[entity langcode]
        $id = explode('/', $search_result->getId())[1];

        /** @var \Drupal\node\Entity\Node $node */
        $node = $source->load($id)->getValue();
        if ($node->hasTranslation($current_langcode)) {
          $node = $node->getTranslation($current_langcode);
        }

        $items[] = $this->entityTypeManager
          ->getViewBuilder('node')
          ->view($node, 'overview');
      }

      $build['list'] = [
        '#theme' => 'br_frontend_overview_items',
        '#items' => $items,
        '#content_type' => 'news',
      ];

      pager_default_initialize($result_count, self::ITEMS_PER_PAGE);
      $build['pager'] = array(
        '#type' => 'pager',
        '#quantity' => self::PAGER_QUANTITY,
      );

    }
    else {
      $build['empty'] = [
        '#theme' => 'br_frontend_empty_result',
        '#message' => $this->t('There are no search results.'),
      ];
    }

    // Disable caching for this list as it changes with every search
    // combination.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Returns the search keys from the request.
   *
   * @return array
   *   Array of search keys.
   */
  protected function getSeachKeys() {

    if ($this->request->query->has('ajax')) {
      $keys = [
        'page' => $this->request->query->get('page', 0),
        'solution_category' => $this->request->query->get('solution_category', ''),
        'industry' => $this->request->query->get('industry', ''),
        'keyword' => $this->request->query->get('keyword', ''),
      ];
    }
    else {
      // Reset the module values when loading for the first time.
      $keys = [
        'page' => 0,
        'solution_category' => '',
        'industry' => $this->industryManager->getPreferredIndustry(),
        'keyword' => '',
      ];
    }

    return $keys;
  }

  /**
   * Build the search api query.
   *
   * @param array $keys
   *   Search keys. As returned by ::getSeachKeys()
   *
   * @param string $current_langcode
   *   The language code.
   */
  protected function buildQuery($keys, $current_langcode) {

    $this->query = $this->searchIndex->query([
      'limit' => self::ITEMS_PER_PAGE,
      'offset' => $keys['page'] == 0 ? 0 : $keys['page'] * self::ITEMS_PER_PAGE,
    ]);

    if (!empty($keys['keyword'])) {
      $this->query->keys($keys['keyword']);
      $this->query->setFulltextFields(['title', 'field_news_intro']);
    }

    $this->query->addCondition('type', 'news');

    // Get the current country.
    $current_country = $this->currentCountry->getSlug();
    $domain = "country_{$current_country}";
    $this->query->addCondition('field_domain_access', $domain);

    if (!empty($keys['industry'])) {
      $this->query->addCondition('field__term_industry', $keys['industry']);
    }

    if (!empty($keys['solution_category'])) {
      $this->query->addCondition('field__term_category', $keys['solution_category']);
    }

    $this->query->addCondition('search_api_language', [
      $current_langcode,
      LanguageInterface::LANGCODE_NOT_SPECIFIED
    ], 'IN');

    $this->query->sort('search_api_relevance', QueryInterface::SORT_DESC);
  }

}
