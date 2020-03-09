<?php

namespace OpenEuropa\TaskRunner\Config;

use Symfony\Component\Finder\Finder;

/**
 * Default configuration for Task Runner.
 */
class DefaultConfig extends TaskRunnerConfig {

  /**
   * DefaultConfig constructor.
   *
   * @param string $repo_root
   *   The repository root of the project.
   */
  public function __construct($repo_root) {
    parent::__construct();

    $this->set('runner.repo_root', $repo_root);
    $this->set('runner.root', dirname(dirname(dirname(__FILE__))));
    $this->set('runner.bin_dir', $repo_root . '/vendor/bin');
  }

  /**
   * Populates configuration settings not available during construction.
   */
  public function populateHelperConfig() {
    $this->set('drupal.drush.alias', $this->get('drupal.drush.default_alias'));

    if (!$this->get('drupal.multisites')) {
      $this->set('drupal.multisites', $this->getSiteDirs());
    }

    $multisites = $this->get('drupal.multisites');
    $first_multisite = reset($multisites);
    $site = $this->get('runner.site', $first_multisite);
    $this->setSite($site);
  }

  /**
   * Set site.
   *
   * @param string $site
   *   Site name.
   */
  public function setSite($site) {
    $this->set('runner.site', $site);
    if (!$this->get('drupal.drush.options.uri') && $site != 'default') {
      $this->set('drupal.drush.options.uri', $site);
    }

    if ($site != 'default') {
      $this->set('drupal.site.sites_subdir', $site);
    }
  }

  /**
   * Gets an array of sites for the Drupal application.
   *
   * I.e., sites under build/sites, not including 'settings' directory that
   * can be used to share settings files.
   *
   * @return array
   *   An array of sites.
   */
  protected function getSiteDirs() {
    $sites_dir = $this->get('drupal.root') . '/sites';
    $sites = [];

    $finder = new Finder();
    $dirs = $finder
      ->in($sites_dir)
      ->directories()
      ->depth('< 1')
      ->exclude(['settings'])
      ->sortByName();
    foreach ($dirs->getIterator() as $dir) {
      $sites[] = $dir->getRelativePathname();
    }

    return $sites;
  }

}
