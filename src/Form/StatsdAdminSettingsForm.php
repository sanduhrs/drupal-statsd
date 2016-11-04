<?php

namespace Drupal\statsd\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Defines a form that configures statsd settings.
 */
class StatsdAdminSettingsForm extends ConfigFormBase {

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'statsd_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'statsd.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('statsd.settings');

  $form['enabled'] = array(
    '#type'          => 'checkbox',
    '#title'         => $this->t('Enable StatsD'),
    '#description'   => $this->t('Enable StatsD logging. You may want to disable this in non-production environments.'),
    '#default_value' => $config->get('enabled'),
  );
  $form['host'] = array(
    '#type'          => 'textfield',
    '#title'         => $this->t('Host'),
    '#size'          => 25,
    '#description'   => $this->t('The hostname, or IP address of the StatsD daemon. To minimize latency issue, use an IP whenever possible.'),
    '#default_value' => $config->get('host'),
  );
  $form['port'] = array(
    '#type'          => 'textfield',
    '#title'         => $this->t('Port'),
    '#size'          => 5,
    '#description'   => $this->t('The port of the StatsD daemon'),
    '#default_value' => $config->get('port'),
  );
  $form['events'] = array(
    '#type'        => 'fieldset',
    '#title'       => $this->t('Events'),
    '#collapsible' => TRUE,
  );
  $form['events']['user_events'] = array(
    '#type'          => 'checkbox',
    '#title'         => $this->t('Send User Events'),
    '#description'   => $this->t('Captures various user events in the following categories: active sessions, successful logins, failed logins, page views'),
    '#default_value' => $config->get('events.user_events'),
  );
  $form['events']['performance_events'] = array(
    '#type'          => 'checkbox',
    '#title'         => $this->t('Send Performance Events'),
    '#description'   => $this->t('Captures various performance events including peak memory usage and page execution time.'),
    '#default_value' => $config->get('events.performance_events'),
  );
  $form['events']['watchdog_events'] = array(
    '#type'          => 'checkbox',
    '#title'         => $this->t('Send Watchdog Events'),
    '#description'   => $this->t('Captures the severity and type of errors passed through watchdog.'),
    '#default_value' => $config->get('events.watchdog_events'),
  );
  $form['events']['watchdog_level'] = array(
    '#type'          => 'select',
    '#title'         => $this->t('Log Level'),
    '#description'   => $this->t('If watchdog events are enabled, only send data to StatsD at or above the selected threshold'),
    '#options'       => RfcLogLevel::getLevels(),
    '#default_value' => $config->get('events.watchdog_level'),
  );
  $form['sample_rate'] = array(
    '#type'          => 'textfield',
    '#title'         => $this->t('Sample Rate'),
    '#size'          => 2,
    '#description'   => $this->t('StatsD can send a subset of events to Graphite. Choose a lower sample rate if you want to reduce the number of events being sent. Sample rates are between 0 and 1 (e.g. 0.1 implies 10% of events will be logged)'),
    '#default_value' => $config->get('sample_rate'),
  );
  $form['prefix'] = array(
    '#type'          => 'textfield',
    '#title'         => $this->t('Prefix'),
    '#size'          => 15,
    '#description'   => $this->t('Use a prefix if you need to separate similar events (such as on different web servers). This prefix is added for calls (if enabled), as well as any calls via the built-in StatsD client. Do not include the period at the end of the prefix (e.g. use "myprefix" instead of "myprefix."'),
    '#default_value' => $config->get('prefix'),
  );
  $form['suffix'] = array(
    '#type'          => 'textfield',
    '#title'         => $this->t('Suffix'),
    '#size'          => 15,
    '#description'   => $this->t('Use a suffix if you need to separate similar events (such as on different web servers). This suffix is added for calls (if enabled), as well as any calls via the built-in StatsD client. Do not include the period at the beginning of the suffix (e.g. use "mysuffix" instead of "mysuffix."'),
    '#default_value' => $config->get('suffix'),
  );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('statsd.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('host', $form_state->getValue('host'))
      ->set('port', $form_state->getValue('port'))
      ->set('events.user_events', $form_state->getValue('user_events'))
      ->set('events.performance_events', $form_state->getValue('performance_events'))
      ->set('events.watchdog_level', $form_state->getValue('watchdog_level'))
      ->set('sample_rate', $form_state->getValue('sample_rate'))
      ->set('prefix', $form_state->getValue('prefix'))
      ->set('suffix', $form_state->getValue('suffix'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
 public function validateForm(array &$form, FormStateInterface $form_state) {
  $form_state->setValue('host', trim($form_state->getValue('host')));
  $form_state->setValue('port', trim($form_state->getValue('port')));
  $form_state->setValue('sample_rate', trim($form_state->getValue('sample_rate')));
  $form_state->setValue('prefix', trim(rtrim($form_state->getValue('prefix'), '.')));
  $form_state->setValue('suffix', trim(ltrim($form_state->getValue('suffix'), '.')));

  $sample_rate = $form_state->getValue('sample_rate');

  if (!is_numeric($sample_rate) || $sample_rate <= 0 || $sample_rate > 1) {
    $form_state->setErrorByName('sample_rate', t('The sample rate must be a value between 0 and 1') );
  }
 }

}
