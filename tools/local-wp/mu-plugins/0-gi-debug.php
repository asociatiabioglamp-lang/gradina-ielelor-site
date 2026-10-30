<?php
// Doar local: toate avertismentele PHP vizibile și scrise în log.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', WP_CONTENT_DIR . '/gi-php-errors.log');
