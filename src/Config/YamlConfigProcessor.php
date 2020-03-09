<?php

namespace OpenEuropa\TaskRunner\Config;

use Consolidation\Config\Loader\ConfigProcessor;
use OpenEuropa\TaskRunner\Utils\ArrayManipulator;

/**
 * Custom processor for YAML based configuration.
 */
class YamlConfigProcessor extends ConfigProcessor {

  /**
   * Expand dot notated keys.
   *
   * @param array $config
   *   The configuration to be processed.
   *
   * @return array
   *   The processed configuration
   */
  protected function preprocess(array $config) {
    $config = ArrayManipulator::expandFromDotNotatedKeys(ArrayManipulator::flattenToDotNotatedKeys($config));
    return $config;
  }

}
