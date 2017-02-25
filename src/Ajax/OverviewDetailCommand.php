<?php

namespace Drupal\br_frontend\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;

class OverviewDetailCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * The content for the matched element(s).
   *
   * Either a render array or an HTML string.
   *
   * @var string|array
   */
  protected $content;

  /**
   * Constructs an OverviewDetailCommand object.
   *
   * @param string|array $content
   *   The content that will be inserted in the matched element(s), either a
   *   render array or an HTML string.
   */
  public function __construct($content) {
    $this->content = $content;
  }

  /**
   * @inheritDoc
   */
  public function render() {
    return array(
      'data' => $this->getRenderedContent(),
    );
  }

}
