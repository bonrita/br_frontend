<?php

/**
 * @file
 * The interface for F06OverviewService.
 */
namespace Drupal\br_frontend;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Interface F06OverviewServiceInterface
 *
 * @package Drupal\br_frontend
 */
interface OverviewServiceInterface extends ContainerInjectionInterface {

  /**
   * Get all news items of the current domain.
   *
   * @return array $build
   *   The render array.
   */
  public function overview();

  /**
   * Get a list of terms.
   *
   * @param string $vid
   *   The machine name of a vocabulary.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface | bool $cache
   *   The cache instance.
   *
   * @return array
   *   A list of terms.
   */
  public function getTaxonomyTerms($vid, $cache = FALSE);

}
