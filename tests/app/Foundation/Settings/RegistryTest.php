<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Settings;

use Codex\Foundation\Hooks\Hook;
use Codex\Foundation\Settings\Registry;
use Codex\Foundation\Settings\Setting;
use Codex\Tests\WPTestCase;
use InvalidArgumentException;

use function version_compare;

class RegistryTest extends WPTestCase
{
	private Hook $hook;

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->hook = new Hook();
	}

	/** @dataProvider dataInvalidGroup */
	public function testInvalidGroup(string $group): void
	{
		$this->expectException(InvalidArgumentException::class);

		new Registry($group);
	}

	public static function dataInvalidGroup(): iterable
	{
		yield [''];
		yield [' '];
	}

	public function testIsRegistered(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say'))
				->withDefault('Hello, World!'),
			(new Setting('count', 'number'))
				->withDefault(1),
			(new Setting('list', 'array'))
				->withDefault(['count', 'two', 'three'])
				->apiSchema(['items' => ['type' => 'string']]),
		]);
		$registry->register();

		$this->assertTrue($registry->isRegistered());

		$registry->deregister();

		$this->assertFalse($registry->isRegistered());
	}

	public function testRegisteredSettings(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!')
				->withLabel('Say'),
			(new Setting('count', 'integer'))
				->withDefault(1)
				->withDescription('How many time?'),
			(new Setting('list', 'array'))
				->withDefault(['count', 'two', 'three'])
				->apiSchema(['items' => ['type' => 'string']]),
		]);
		$registeredSettings = get_registered_settings();

		$this->assertArrayNotHasKey('say', $registeredSettings);
		$this->assertArrayNotHasKey('count', $registeredSettings);
		$this->assertArrayNotHasKey('list', $registeredSettings);

		$registry->register();

		$registeredSettings = get_registered_settings();

		$this->assertArrayHasKey('say', $registeredSettings);
		$this->assertSame('string', $registeredSettings['say']['type']);
		$this->assertSame('', $registeredSettings['say']['description']);
		$this->assertTrue($registeredSettings['say']['show_in_rest']);

		if (version_compare($GLOBALS['wp_version'], '6.6', '>=')) {
			$this->assertSame('Say', $registeredSettings['say']['label']);
		}

		$this->assertArrayHasKey('count', $registeredSettings);
		$this->assertSame('integer', $registeredSettings['count']['type']);
		$this->assertSame('How many time?', $registeredSettings['count']['description']);
		$this->assertTrue($registeredSettings['count']['show_in_rest']);

		if (version_compare($GLOBALS['wp_version'], '6.6', '>=')) {
			$this->assertSame('', $registeredSettings['count']['label']);
		}

		$this->assertArrayHasKey('list', $registeredSettings);
		$this->assertSame('array', $registeredSettings['list']['type']);
		$this->assertSame('', $registeredSettings['list']['description']);
		$this->assertEquals([
			'name' => 'list',
			'schema' => ['items' => ['type' => 'string']],
		], $registeredSettings['list']['show_in_rest']);

		if (version_compare($GLOBALS['wp_version'], '6.6', '>=')) {
			$this->assertSame('', $registeredSettings['list']['label']);
		}

		$registry->deregister();

		$registeredSettings = get_registered_settings();

		$this->assertArrayNotHasKey('say', $registeredSettings);
		$this->assertArrayNotHasKey('count', $registeredSettings);
		$this->assertArrayNotHasKey('list', $registeredSettings);
	}

	public function testDefault(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
			(new Setting('count', 'number'))
				->withDefault(1),
			(new Setting('list', 'array'))
				->withDefault(['count', 'two', 'three'])
				->apiSchema(['items' => ['type' => 'string']]),
		]);

		$this->assertFalse(get_option('say'));
		$this->assertFalse(get_option('count'));
		$this->assertFalse(get_option('list'));

		$registry->register();

		$this->assertSame('Hello, World!', get_option('say'));
		$this->assertSame(1, get_option('count'));
		$this->assertSame(['count', 'two', 'three'], get_option('list'));

		$registry->deregister();

		$this->assertFalse(get_option('say'));
		$this->assertFalse(get_option('count'));
		$this->assertFalse(get_option('list'));
	}

	public function testPrefix(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
			(new Setting('count', 'number'))
				->withDefault(1),
			(new Setting('list', 'array'))
				->withDefault(['count', 'two', 'three'])
				->apiSchema(['items' => ['type' => 'string']]),
		]);
		$registry->setPrefix('codex_');

		$this->assertFalse(get_option('codex_say'));
		$this->assertFalse(get_option('codex_count'));
		$this->assertFalse(get_option('codex_list'));

		$registry->register();

		$this->assertSame('Hello, World!', get_option('codex_say'));
		$this->assertSame(1, get_option('codex_count'));
		$this->assertSame(['count', 'two', 'three'], get_option('codex_list'));

		$registry->deregister();

		$this->assertFalse(get_option('codex_say'));
		$this->assertFalse(get_option('codex_count'));
		$this->assertFalse(get_option('codex_list'));
	}

	public function testAddOption(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
		]);
		$registry->setPrefix('codex_');
		$registry->register();

		$this->assertSame('Hello, World!', get_option('codex_say'));
		$this->assertTrue(add_option('codex_say', 'Hi'));
		$this->assertSame('Hi', get_option('codex_say'));
	}

	public function testUpdateOption(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
		]);
		$registry->setPrefix('codex_');
		$registry->register();

		$this->assertTrue(add_option('codex_say', 'Hi'));
		$this->assertSame('Hi', get_option('codex_say'));
		$this->assertTrue(update_option('codex_say', 'Hai'));
		$this->assertSame('Hai', get_option('codex_say'));
	}

	public function testDeleteOption(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
		]);
		$registry->setPrefix('codex_');
		$registry->register();

		$this->assertSame('Hello, World!', get_option('codex_say'));
		$this->assertTrue(update_option('codex_say', 'Hai'));
		$this->assertSame('Hai', get_option('codex_say'));
		$this->assertTrue(delete_option('codex_say'));
		$this->assertSame('Hello, World!', get_option('codex_say'));
	}

	public function testPassingDefault(): void
	{
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
		]);
		$registry->setPrefix('codex_');
		$registry->register();

		$this->assertSame('Hello, World!', get_option('codex_say'));
		$this->assertSame('Hai', get_option('codex_say', 'Hai'));
	}

	public function testDeregister(): void
	{
		$wpdb = $GLOBALS['wpdb'];
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
		]);
		$registry->setPrefix('codex_');
		$registry->register();

		$this->assertSame('Hello, World!', get_option('codex_say'));
		$this->assertTrue(update_option('codex_say', 'World'));
		$this->assertSame('World', get_option('codex_say'));

		// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
		$row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'codex_say'));

		$this->assertEquals(['option_value' => 'World'], (array) $row);

		$registry->deregister();

		// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
		$row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'codex_say'));

		$this->assertEquals(['option_value' => 'World'], (array) $row);
		$this->assertSame('World', get_option('codex_say'));
	}

	public function testDeregisterWithDelete(): void
	{
		$wpdb = $GLOBALS['wpdb'];
		$registry = new Registry('codex');
		$registry->addSettings(...[
			(new Setting('say', 'string'))
				->withDefault('Hello, World!'),
		]);
		$registry->setPrefix('codex_');
		$registry->register();

		$this->assertSame('Hello, World!', get_option('codex_say'));
		$this->assertTrue(update_option('codex_say', 'World'));
		$this->assertSame('World', get_option('codex_say'));

		// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
		$row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'codex_say'));

		$this->assertEquals(['option_value' => 'World'], (array) $row);

		$registry->deregister(true);

		// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
		$row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'codex_say'));

		$this->assertNull($row);
		$this->assertFalse(get_option('codex_say'));
	}
}
