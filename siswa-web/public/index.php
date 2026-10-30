<?php
session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/helpers/ui.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/App.php';

new App();
