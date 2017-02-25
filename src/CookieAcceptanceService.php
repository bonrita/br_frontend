<?php

namespace Drupal\br_frontend;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\br_country\CurrentCountryInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class CookieAcceptanceService.
 *
 * @package Drupal\br_frontend
 */
class CookieAcceptanceService {

  const COOKIE_CONTENT_TYPE = 'cookie';

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The current country.
   *
   * @var \Drupal\br_country\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * The node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $cookieNode;

  /**
   * The current language code.
   *
   * @var string
   */
  protected $currentLanguageCode;

  /**
   * CookieAcceptanceService constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The entity query.
   * @param \Drupal\br_country\CurrentCountryInterface $currentCountry
   *   The current country instance.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager instance.
   */
  public function __construct(QueryFactory $entityQuery, CurrentCountryInterface $currentCountry, LanguageManagerInterface $languageManager) {
    $this->entityQuery = $entityQuery;
    $this->currentCountry = $currentCountry;
    $this->currentLanguageCode = $languageManager->getCurrentLanguage()
      ->getId();
  }

  /**
   * Get copy text.
   *
   * @return string
   *   The text to render.
   */
  public function getCopyText() {
    if ($this->isCookieEnabled()) {
      return $this->cookieNode->field_d02_copy_text->getString();
    }
    return '';
  }

  /**
   * Get cookie node.
   */
  public function retrieveCookieNode() {
    $domain = $this->currentCountry->getSlug();

    $nids = $this->entityQuery->get('node')
      ->condition('type', self::COOKIE_CONTENT_TYPE)
      ->condition('status', 1)
      ->condition('field_domain_access', "country_{$domain}")
      ->range(0, 1)
      ->execute();

    $nodes = array_values($nids);

    if (!empty($nodes)) {
      $this->cookieNode = Node::load($nodes[0]);
    }

    if ($this->cookieNode && $this->cookieNode->hasTranslation($this->currentLanguageCode)) {
      $this->cookieNode = $this->cookieNode->getTranslation($this->currentLanguageCode);
    }
  }

  /**
   * Get the information link.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getInformationLink() {
    if ($this->isCookieEnabled()) {
      return $this->cookieNode->toUrl();
    }
    return '';
  }

  /**
   * Is cookie enabled for the current domain.
   *
   * @return bool
   *   TRUE/FALSE.
   */
  public function isCookieEnabled() {
    return (bool) $this->cookieNode;
  }

}
