<?php

namespace Drupal\br_frontend\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Html;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link_separate' formatter.
 *
 * @FieldFormatter(
 *   id = "br_frontend_link_url",
 *   label = @Translation("Link URL"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkUrl extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $url = $this->buildUrl($item);
      $link_title = '';

      // Generate external URL without cacheable metadata.
      // Generate internal URL and collect cacheable metadata.
      if ($url->isExternal()) {
        $link_url = $url->toString(FALSE);
      }
      else {
        $generated_url = $url->toString(TRUE);
        $link_url = $generated_url->getGeneratedUrl();
      }
      $link_url = UrlHelper::stripDangerousProtocols($link_url);

      // If the link text field value is available, use it for the text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        $link_title = \Drupal::token()->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
        if (!($link_title instanceof MarkupInterface)) {
          $link_title = Html::escape($link_title);
        }
      }

      $element[$delta] = array(
        '#theme' => 'br_frontend_link_url',
        '#title' => $link_title,
        '#url' => $link_url,
      );
    }
    return $element;
  }

}
