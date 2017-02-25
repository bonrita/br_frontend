<?php

/**
 * The endpoint for the F06 overview details module.
 *
 * @file
 */

namespace Drupal\br_frontend\Controller;

use Drupal\br_frontend\OverviewControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class F07OverviewController
 *
 * @package Drupal\br_frontend\Controller
 */
class F07OverviewController extends OverviewControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('br_frontend.f07overview')
    );
  }

}
