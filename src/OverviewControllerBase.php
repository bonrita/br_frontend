<?php

namespace Drupal\br_frontend;


use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\br_frontend\Ajax\OverviewDetailCommand;

/**
 * Class OverviewDetailController
 *
 * @package Drupal\br_frontend\Controller
 */
abstract class OverviewControllerBase extends ControllerBase {

  /**
   * The Overview service.
   *
   * @var \Drupal\br_frontend\OverviewService $overviewService
   */
  protected $overviewService;

  /**
   * Constructs a OverviewDetailController.
   *
   * @param \Drupal\br_frontend\OverviewServiceInterface $f06_overview
   *   The F06 overview detail service.
   */
  public function __construct(OverviewServiceInterface $overview) {
    $this->overviewService = $overview;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(Request $request) {

    $output = $this->overviewService->overview();

    $response = new AjaxResponse();
    $response->addCommand(new OverviewDetailCommand($output));
    return $response;
  }

}
