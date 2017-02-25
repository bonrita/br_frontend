<?php

namespace Drupal\br_frontend\Twig\Extension;

/**
 * Provides various filters for Twig templates.
 */
class BrFrontendExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('imperial', [$this, 'convertMetricToImperial']),
      new \Twig_SimpleFilter('numeric', [$this, 'convertToNumeric']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'br_frontend';
  }

  /**
   * Twig filter callback: Return specific field item(s) value.
   *
   * @param $metric
   *   The metric value to convert.
   * @param string $type
   *   The type of conversion to perform.
   *
   * @return float
   *   The converted imperial value.
   */
  public function convertMetricToImperial($metric, $type = 'length') {

    $imperial = '';
    $metric = $this->convertToNumeric($metric);

    // Source: http://www.metric-conversions.org
    switch ($type) {
      // Millimeters to inch.
      case 'length':
        $imperial = $metric * 0.039370;
        break;

      // Square meters to square feet.
      case 'area':
        $imperial = $metric *  3.2808^2;
        break;

      // Liters to UK gallons.
      case 'volume':
        $imperial = $metric * 0.21997;
        break;

      // Cubic meters to Cubic feet.
      case 'space':
        $imperial = $metric *  3.2808^3;
        break;

      // Kilograms to pounds.
      case 'mass':
        $imperial = $metric * 2.2046;
        break;

      // Celcius to Fahrenheit.
      case 'temperature':
        $imperial = ($metric * 1.8000) + 32.0;
        break;
    }

    return $imperial;
  }

  /**
   * Twig filter callback: Return the numeric value of a render array.
   *
   * @param $build
   *   A render array containing a value.
   *
   * @return float
   *   The float value.
   */
  public function convertToNumeric($build) {
    $value = 0;

    if (is_array($build)) {
      if (isset($build[0]['#markup'])) {
        $value = $build[0]['#markup'];
      }
    }
    elseif (is_numeric($build)) {
      $value = $build;
    }

    return $value;
  }
}
