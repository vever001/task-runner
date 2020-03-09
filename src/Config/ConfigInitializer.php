<?php

namespace OpenEuropa\TaskRunner\Config;

use Consolidation\Config\Loader\YamlConfigLoader;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Config init.
 */
class ConfigInitializer {

  /**
   * Config.
   *
   * @var \OpenEuropa\TaskRunner\Config\DefaultConfig
   */
  protected $config;

  /**
   * Input.
   *
   * @var \Symfony\Component\Console\Input\InputInterface
   */
  protected $input;

  /**
   * Loader.
   *
   * @var \Consolidation\Config\Loader\YamlConfigLoader
   */
  protected $loader;

  /**
   * Processor.
   *
   * @var \OpenEuropa\TaskRunner\Config\YamlConfigProcessor
   */
  protected $processor;

  /**
   * Site.
   *
   * @var string
   */
  protected $site;

  /**
   * ConfigInitializer constructor.
   *
   * @param string $repo_root
   *   Repo root.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input.
   */
  public function __construct($repo_root, InputInterface $input) {
    $this->input = $input;
    $this->config = new DefaultConfig($repo_root);
    $this->loader = new YamlConfigLoader();
    $this->processor = new YamlConfigProcessor();
  }

  /**
   * Set site.
   *
   * @param mixed $site
   *   Site.
   */
  public function setSite($site) {
    $this->site = $site;
    $this->config->setSite($site);
  }

  /**
   * Determine site.
   *
   * @return mixed|string
   *   Site.
   */
  protected function determineSite() {
    if ($this->input->hasParameterOption('site')) {
      $site = $this->input->getParameterOption('site');
    }
    elseif ($this->input->hasParameterOption('--site')) {
      $site = $this->input->getParameterOption('--site');
    }
    else {
      $site = 'default';
    }

    return $site;
  }

  /**
   * Initialize.
   *
   * @return \OpenEuropa\TaskRunner\Config\DefaultConfig
   *   Config.
   */
  public function initialize() {
    if (!$this->site) {
      $site = $this->determineSite();
      $this->setSite($site);
    }
    $this->loadConfigFiles();
    $this->processConfigFiles();

    return $this->config;
  }

  /**
   * Load config.
   *
   * @return $this
   *   Config.
   */
  public function loadConfigFiles() {
    $this->loadDefaultConfig();
    $this->loadProjectConfig();
    $this->loadSiteConfig();
    $this->loadLocalConfig();

    return $this;
  }

  /**
   * Load default config.
   *
   * @return $this
   *   Config.
   */
  public function loadDefaultConfig() {
    $this->processor->add($this->config->export());
    $this->processor->extend($this->loader->load($this->config->get('runner.root') . '/config/runner.yml'));
    $this->config->replace($this->processor->export());

    return $this;
  }

  /**
   * Load project config.
   *
   * @return $this
   *   Config.
   */
  public function loadProjectConfig() {
    $this->processor->extend($this->loader->load($this->config->get('runner.repo_root') . '/runner.yml.dist')); // TODO FAILS !!!!
    $this->processor->extend($this->loader->load($this->config->get('runner.repo_root') . '/runner.yml'));
    $this->config->replace($this->processor->export());

    return $this;
  }

  /**
   * Load site config.
   *
   * @return $this
   *   Config.
   */
  public function loadSiteConfig() {
    if ($this->site) {
      $this->processor->extend($this->loader->load($this->config->get('drupal.root') . "/sites/{$this->site}/runner.yml.dist"));
      $this->processor->extend($this->loader->load($this->config->get('drupal.root') . "/sites/{$this->site}/runner.yml"));
      $this->config->replace($this->processor->export());
    }

    return $this;
  }

  /**
   * Load local config.
   *
   * @return $this
   *   Config.
   */
  public function loadLocalConfig() {
    $local_config = $this->getLocalConfigFilepath();
    if ($local_config) {
      $this->processor->extend($this->loader->load($local_config));
      $this->config->replace($this->processor->export());
    }

    return $this;
  }

    /**
     * Get the local configuration filepath.
     *
     * @param string $configuration_file
     *   The default filepath.
     *
     * @return string|null
     *   The local configuration file path, or null if it doesn't exist.
     */
    private function getLocalConfigFilepath($configuration_file = 'openeuropa/taskrunner/runner.yml') {
        if ($config = getenv('OPENEUROPA_TASKRUNNER_CONFIG')) {
            return $config;
        }

        if ($config = getenv('XDG_CONFIG_HOME')) {
            return $config . '/' . $configuration_file;
        }

        if ($home = getenv('HOME')) {
            return getenv('HOME') . '/.config/' . $configuration_file;
        }

        return NULL;
    }

  /**
   * Process config.
   *
   * @return $this
   *   Config.
   */
  public function processConfigFiles() {
    $this->config->populateHelperConfig();

    return $this;
  }

}
