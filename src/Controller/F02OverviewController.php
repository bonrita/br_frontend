<?php

/**
 * The endpoint for the F02 press release overview details module.
 *
 * @file
 */

namespace Drupal\br_frontend\Controller;

use Drupal\br_frontend\OverviewControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class OverviewDetailController
 *
 * @package Drupal\br_frontend\Controller
 */
class F02OverviewController extends OverviewControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('br_frontend.f02overview')
    );
  }

}
