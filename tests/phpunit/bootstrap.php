<?php
/**
 * PHPUnit bootstrap file for WordPress plugin
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once getenv('WP_PHPUNIT__DIR') . '/includes/functions.php';
require getenv('WP_PHPUNIT__DIR') . '/includes/bootstrap.php';
