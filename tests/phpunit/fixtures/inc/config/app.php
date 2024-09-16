<?php

declare(strict_types=1);

return [
	'name' => 'wp-test',
	'text_domain' => 'wp-test',
	'assets_path' => '/dist',
	'assets_url' => 'https://example.org/dist',
	'assets_handle_prefix' => 'wp-test-',
	'blocks_path' => dirname(__DIR__) . '/blocks',
	'option_prefix' => 'wp_test_',
	'empty' => '',
	'blank' => [],
];
