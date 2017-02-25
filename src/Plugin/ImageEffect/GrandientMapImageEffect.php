<?php

namespace Drupal\br_frontend\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;
use Imagick;

/**
 * Applies a Gradient Map on an image resource.
 *
 * @ImageEffect(
 *   id = "br_frontend_image_gradient_map",
 *   label = @Translation("Gradient Map"),
 *   description = @Translation("Applies a gradient map to an image.")
 * )
 */
class GrandientMapImageEffect extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->apply('gradient_map', $this->configuration)) {
      $this->logger->error('Gradient map failed using the %toolkit toolkit on %path (%mimetype)', array(
        '%toolkit' => $image->getToolkitId(),
        '%path' => $image->getSource(),
        '%mimetype' => $image->getMimeType()
      ));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'light' => '#004f7e',
      'dark' =>  '#0078bf'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['light'] = array(
      '#type' => 'color',
      '#title'   => $this->t('Light color'),
      '#description' => t('Replacement color for white.'),
      '#default_value' => $this->configuration['light'],
      '#maxlength' => 7,
      '#size' => 7,
    );

    $form['dark'] = array(
      '#type' => 'color',
      '#title'   => $this->t('Dark color'),
      '#description' => t('Replacement color for black.'),
      '#default_value' => $this->configuration['dark'],
      '#maxlength' => 7,
      '#size' => 7,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['light'] = $form_state->getValue('light');
    $this->configuration['dark'] = $form_state->getValue('dark');
  }

}
