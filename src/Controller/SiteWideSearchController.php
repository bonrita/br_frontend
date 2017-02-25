<?php

namespace Drupal\br_frontend\Controller;


use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\search_api_page\Entity\SearchApiPage;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class SiteWideSearchController.
 *
 * @package Drupal\br_frontend\Controller
 */
class SiteWideSearchController extends ControllerBase {

  /**
   * Redirect to the site wide search route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function search(Request $request) {
    $search_value = $request->get('search');

    /* @var $search_api_page \Drupal\search_api_page\SearchApiPageInterface */
    $search_api_page = SearchApiPage::load('site_wide');

    $langcode = $this->languageManager()
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    $route_name = 'search_api_page.' . $langcode . '.' . $search_api_page->id();
    return $this->redirect($route_name, ['keys' => $search_value]);
  }

}
