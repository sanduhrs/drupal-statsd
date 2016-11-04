<?php

namespace Drupal\statsd\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class StatsDLogger implements LoggerInterface {
  use RfcLoggerTrait;

  protected $config;

  /**
   * Construct a StatsDLogger interface to allow log event response.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('statsd.settings');
  }

  /**
   * @inheritdoc
   */
  public function log($level, $message, array $context = array()) {

    if ($context['channel'] != 'statsd') {
      $enabled        = $this->config->get('events.watchdog_events');
      $eventThreshold = $this->config->get('events.watchdog_level');

      if (!$enabled || $eventThreshold < $level) {
        return;
      }

      if (strstr($message, 'Login attempt failed')) {
        // The user key in the context appears to be an instance of the
        // AccountProxy class.
        // @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Session!AccountProxy.php/class/AccountProxy/8.2.x
        statsd_user_login_failed($context['user']->getAccountName());
      }

      $levels = RfcLogLevel::getLevels();

      $data = array(
        'watchdog.type.' . $context['channel'],
        'watchdog.severity.' . $levels[$level],
      );

      statsd_call($data);
    }
  }

}