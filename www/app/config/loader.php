<?php

use Phalcon\Loader;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Shoutzor\Listener\ErrorListener;

$eventsManager = new EventsManager();
$loader = new Loader;

//Register namespaces
$loader->registerNamespaces([
  /**
   * Shoutzor Namespaces
   */
  'Shoutzor\Controller'   => $config->application->controllersDir,
  'Shoutzor\Model'        => $config->application->modelsDir,
  'Shoutzor'              => $config->application->appDir . 'core/',

  /**
   * Library Namespaces
   */
  'Intervention\Image'    => $config->application->libDir . 'Intervention/Image'
]);

$loader->register();
