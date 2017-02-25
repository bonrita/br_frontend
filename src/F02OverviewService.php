<?php

/**
 * @file
 *   This file contains functionality that gets all news items of a domain.
 */

namespace Drupal\br_frontend;


use DateTime;
use Drupal\br_country\CurrentCountryInterface;
use Drupal\br_ips\IndustryManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Class F02OverviewService
 *
 * @package Drupal\br_frontend
 */
class F02OverviewService extends OverviewServiceBase {
  protected $cache;

  public function __construct(\Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager, \Drupal\Core\Language\LanguageManagerInterface $language_manager, \Drupal\Core\Session\AccountProxyInterface $current_user, \Symfony\Component\HttpFoundation\RequestStack $request_stack, \Drupal\br_ips\IndustryManagerInterface $industry_manager, \Drupal\br_country\CurrentCountryInterface $current_country, \Drupal\Core\Cache\CacheBackendInterface $cache) {
    parent::__construct($entity_type_manager, $language_manager, $current_user, $request_stack, $industry_manager, $current_country);
    $this->cache = $cache;
  }

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
        '#content_type' => 'press_release',
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
   * Return the array of the available press release years.
   *
   * @return array
   *   Array of year numbers.
   */
  public function getPressReleaseYears() {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $cid = 'f02_overview_years_list:' . $this->currentCountry->getSlug();
    $cache = $this->cache->get($cid);
    $items = [];
    if (!$cache) {
      $account = $this->currentUser->getAccount();

      $this->buildYearsQuery($current_langcode);

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
          $type = $node->getType();
          // Had to add this because solr returning press_release_overviews too.
          //if ('press_release' == $type) {
          /** @var \Drupal\field_collection\Plugin\Field\FieldType\FieldCollection $field_collection */
          $field_collection = $node->field_press_release_headline->first();
          if ($field_collection) {
            /** @var \Drupal\field_collection\Entity\FieldCollectionItem $field_collection_item */
            $field_collection_item = $field_collection->getFieldCollectionItem();
            if ($field_collection_item) {
              $date = $field_collection_item->field_press_release_date->value;
              $year_num = substr($date, 0, 4);
              if (!in_array($year_num, $items)) {
                $items[$year_num] = $year_num;
                /* $a = \Drupal::cache();
                 $b = $this->cache->get();*/
              }
            }
          }
          //}
        }
      }

      asort($items);
      $this->cache->set($cid, $items);
    }
    else {
      $items = $cache->data;
    }
    return $items;
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
        'year' => $this->request->query->get('year', ''),
        'keyword' => $this->request->query->get('keyword', ''),
      ];
    }
    else {
      // Reset the module values when loading for the first time.
      $keys = [
        'page' => 0,
        'year' => '',
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

    $this->query->addCondition('type', 'press_release');

    // Get the current country.
    $current_country = $this->currentCountry->getSlug();
    $domain = "country_{$current_country}";
    $this->query->addCondition('field_domain_access', $domain);

    $this->query->addCondition('search_api_language', [
      $current_langcode,
      LanguageInterface::LANGCODE_NOT_SPECIFIED
    ], 'IN');

    if (!empty($keys['keyword'])) {
      $this->query->keys($keys['keyword']);
      $this->query->setFulltextFields(['title', 'field_news_intro']);
    }

    if (!empty($keys['year'])) {
      $year = intval($keys['year']);
      $date = new DateTime();
      $date->setDate($year, 1, 1);
      $start = $date->format('Y-m-d');
      $date->setDate(($year + 1), 1, 1);
      $end = $date->format("Y-m-d");
      $conditionGroup = $this->query->createConditionGroup('AND');
      $conditionGroup->addCondition('field_press_release_date', $start, '>=');
      $conditionGroup->addCondition('field_press_release_date', $end, '<');
      $this->query->addConditionGroup($conditionGroup);
    }

    $this->query->sort('field_press_release_date', QueryInterface::SORT_DESC);
  }

  /**
   * Build the search api query.
   *
   * @param string $current_langcode
   *   The language code.
   */
  protected function buildYearsQuery($current_langcode) {

    $this->query = $this->searchIndex->query();
    $this->query->addCondition('type', 'press_release');

    // Get the current country.
    $current_country = $this->currentCountry->getSlug();
    $domain = "country_{$current_country}";
    $this->query->addCondition('field_domain_access', $domain);

    $this->query->addCondition('search_api_language', [
      $current_langcode,
      LanguageInterface::LANGCODE_NOT_SPECIFIED
    ], 'IN');

    $this->query->sort('field_press_release_date', QueryInterface::SORT_DESC);
  }

}
