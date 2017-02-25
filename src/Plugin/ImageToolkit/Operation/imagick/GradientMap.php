<?php

namespace Drupal\br_frontend\Plugin\ImageToolkit\Operation\imagick;

use Drupal\imagick\Plugin\ImageToolkit\Operation\imagick\ImagickImageToolkitOperationBase;
use Imagick;

/**
 * Defines imagick gradient map operation.
 *
 * @ImageToolkitOperation(
 *   id = "br_frontend_gradient_map",
 *   toolkit = "imagick",
 *   operation = "gradient_map",
 *   label = @Translation("Gradient Map"),
 *   description = @Translation("Applies a Gradient Map on an image")
 * )
 */
class GradientMap extends ImagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return array(
      'light' => array(
        'description' => 'Replacement color for white.',
      ),
      'dark' => array(
        'description' => 'Replacement color for black.',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = array()) {

    /* @var $resource \Imagick */
    $resource = $this->getToolkit()->getResource();

    // Make duotone CLUT.
    $clut = new Imagick();
    $light = $arguments['light'];
    $dark = $arguments['dark'];
    $clut->newPseudoImage(255,1,"gradient:$light-$dark");

    return $resource->clutImage($clut);
  }

}
