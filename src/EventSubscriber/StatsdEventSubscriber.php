<?php

namespace Drupal\statsd\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;

/**
 * Subscribe to KernelEvents::REQUEST and ::TERMINATE events.
 */
class StatsdEventSubscriber implements EventSubscriberInterface {

  protected $config;

  protected $dbConnection;

  /**
   * Constructs a StatsdEventSubscriber object.
   */
  public function __construct(ConfigFactoryInterface $config, Connection $dbConnection) {
    $this->config = $config->get('statsd.settings');
    $this->dbConnection = $dbConnection;
  }

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    // Setting low priority to go early in the event stack.
    $events[KernelEvents::REQUEST][] = array('statsdBootHandler', -100);

    // Setting high priority to go late in the event stack.
    $events[KernelEvents::TERMINATE][] = array('statsdTerminateHandler', 100);
    return $events;
  }

  /**
   * Set a static variable to support request processing time tracking.
   */
  public function statsdBootHandler() {
    if ($this->config->get('events.performance_events')) {
      drupal_static('statsd_timer', microtime(TRUE));
    }
  }

  /**
   * Set configured metrics on shut down.
   *
   */
  public function statsdTerminateHandler() {
    if ($this->config->get('events.user_events')) {
      $active_sessions = $this->dbConnection->query("SELECT count(*) as num FROM {sessions} WHERE timestamp > UNIX_TIMESTAMP() - 3600")->fetchField();
      statsd_call('user_events.active_sessions', 'gauge', $active_sessions);
      statsd_call('user_events.page_view');
    }

    if ($this->config->get('events.performance_events')) {
      $memory = round(memory_get_peak_usage() / 1024 / 1024, 2);
      statsd_call('performance_events.peak_memory', 'gauge', $memory);

      $start = &drupal_static('statsd_timer');
      // hook_boot() may not be called in certain contexts.
      if ($start > 0) {
        $end  = microtime(TRUE);
        $time = round(($end - $start) * 1000, 0);
        statsd_call('performance_events.execution_time', 'timing', $time);
      }
    }

  }

}

