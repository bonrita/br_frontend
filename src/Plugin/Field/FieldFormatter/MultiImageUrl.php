<?php

namespace Drupal\br_frontend\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "br_frontend_multi_image_url",
 *   label = @Translation("Multi URL to image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class MultiImageUrl extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style_0' => '',
      'image_style_1' => '',
      'image_style_2' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style_0'] = [
      '#title' => t('Image style 1'),
      '#type' => 'select',
      '#required' => TRUE, // This has to be required because it will break if no setting is selected.
      '#default_value' => $this->getSetting('image_style_0'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles')
        ],
    ];

    $element['image_style_1'] = [
      '#title' => t('Image style 2'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style_1'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles')
        ],
    ];

    $element['image_style_2'] = [
      '#title' => t('Image style 3'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style_2'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles')
        ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);

    //@todo loop through image stle settings and update summary dynamically.
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style_0');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image 1 using image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Image 1 using original image');
    }

    $image_style_setting = $this->getSetting('image_style_1');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image 2 using image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Image 2 using original image');
    }

    $image_style_setting = $this->getSetting('image_style_2');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image 3 using image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Image 3 using original image');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_style_settings = $this->getSettings();

    $image_styles = [];
    if (!empty($image_style_settings)) {
      foreach ($image_style_settings as $image_style_setting) {
        $image_styles[] = $this->imageStyleStorage->load($image_style_setting);
      }
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];

      // @todo Wrap in file_url_transform_relative(). This is currently
      // impossible. As a work-around, we currently add the 'url.site' cache
      // context to ensure different file URLs are generated for different
      // sites in a multisite setup, including HTTP and HTTPS versions of the
      // same site. Fix in https://www.drupal.org/node/2646744.
      $cache_contexts[] = 'url.site';

      if ($image_styles) {
        /** @var \Drupal\image\Entity\ImageStyle $image_style */
        $i = 0;
        foreach ($image_styles as $image_style) {
          if (!$image_style instanceof ImageStyle) {
            continue;
          }
          $base_cache_tags = $image_style->getCacheTags();
          $url = $image_style->buildUrl($file->getFileUri());
          $image_style_name = $image_style->getName();

          $item = $file->_referringItem;
          $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

          $elements[$delta][$i] = array(
            '#theme' => 'br_frontend_image_url',
            '#item' => $item,
            '#image_style' => $image_style_name,
            '#url' => $url,
            '#cache' => array(
              'tags' => $cache_tags,
              'contexts' => $cache_contexts,
            ),
          );
          $i++;
        }
      }

    }

    return $elements;
  }

}
