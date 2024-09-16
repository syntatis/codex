<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Hooks\Support;

use Codex\Contracts\Hookable;
use Codex\Foundation\Hooks\Action;
use Codex\Foundation\Hooks\Filter;
use Codex\Foundation\Hooks\Hook;
use Codex\Tests\WPTestCase;

use function array_key_first;

/** @requires PHP 8.0 */
class ParserTest extends WPTestCase
{
	/** @var array<mixed> */
	private static array $wpFilter;

	// phpcs:ignore -- Upstream convention.
	public function set_up(): void
	{
		parent::set_up();

		self::$wpFilter = $GLOBALS['wp_filter'];
	}

	// phpcs:ignore -- Upstream convention.
	public function tear_down(): void
	{
		$GLOBALS['wp_filter'] = self::$wpFilter;

		parent::tear_down();
	}

	public function testActionOnMethod(): void
	{
		$hasActions = new class implements Hookable
		{
			public function hook(Hook $hook): void
			{
				$hook->addAction('init', [$this, 'bar'], 124);
				$hook->parse($this);
			}

			public function bar(): void
			{
			}

			#[Action(name: 'init', priority: 123, acceptedArgs: 2)]
			public function foo(): void
			{
			}
		};

		$hook = new Hook();
		$hasActions->hook($hook);

		$this->assertEquals(123, has_action('init', [$hasActions, 'foo']));
		$this->assertEquals(124, has_action('init', [$hasActions, 'bar']));

		$hooks = $GLOBALS['wp_filter']['init'][123];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasActions, 'foo'], $added['function']);
		$this->assertEquals(2, $added['accepted_args']);

		$hooks = $GLOBALS['wp_filter']['init'][124];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasActions, 'bar'], $added['function']);
		$this->assertEquals(1, $added['accepted_args']);
	}

	public function testFilterOnMethod(): void
	{
		$hasFilters = new class implements Hookable
		{
			public function hook(Hook $hook): void
			{
				$hook->addFilter('the_content', [$this, 'bar'], 224);
				$hook->parse($this);
			}

			public function bar(): void
			{
			}

			#[Filter(name: 'the_content', priority: 223, acceptedArgs: 2)]
			public function foo(): void
			{
			}
		};

		$hook = new Hook();
		$hasFilters->hook($hook);

		$this->assertEquals(223, has_filter('the_content', [$hasFilters, 'foo']));
		$this->assertEquals(224, has_filter('the_content', [$hasFilters, 'bar']));

		$hooks = $GLOBALS['wp_filter']['the_content'][223];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasFilters, 'foo'], $added['function']);
		$this->assertEquals(2, $added['accepted_args']);

		$hooks = $GLOBALS['wp_filter']['the_content'][224];
		$added = $hooks[array_key_first($hooks)];

		$this->assertEquals([$hasFilters, 'bar'], $added['function']);
		$this->assertEquals(1, $added['accepted_args']);
	}

	public function testActionOnClass(): void
	{
		$foo = new Foo();
		$hook = new Hook();
		$hook->parse($foo);

		$hooks = $GLOBALS['wp_filter']['init'][234];
		$added = $hooks[array_key_first($hooks)];

		$this->assertIsCallable($added['function']);
		$this->assertEquals(2, $added['accepted_args']);
	}

	public function testFilterOnClass(): void
	{
		$bar = new Bar();
		$hook = new Hook();
		$hook->parse($bar);

		$hooks = $GLOBALS['wp_filter']['the_title'][432];
		$added = $hooks[array_key_first($hooks)];

		$this->assertIsCallable($added['function']);
		$this->assertEquals(1, $added['accepted_args']);
	}

	public function testWithConstructor(): void
	{
		$instance = new WithConstructor();
		$hook = new Hook();
		$hook->parse($instance);

		$this->assertFalse(isset($GLOBALS['wp_filter']['muplugins_loaded'][100]));
	}

	public function testWithDestructor(): void
	{
		$instance = new WithDestructor();
		$hook = new Hook();
		$hook->parse($instance);

		$this->assertFalse(isset($GLOBALS['wp_filter']['setup_theme'][123]));
	}

	public function testWithPrivateMethod(): void
	{
		$instance = new WithPrivateMethod();
		$hook = new Hook();
		$hook->parse($instance);

		$this->assertFalse(isset($GLOBALS['wp_filter']['admin_bar_init'][99]));
	}

	public function testWithDoubleDashedMethod(): void
	{
		$instance = new WithDoubleDashed();
		$hook = new Hook();
		$hook->parse($instance);

		$this->assertFalse(isset($GLOBALS['wp_filter']['wp_loaded'][345]));
	}

	public function testWithSettings(): void
	{
		$instance = new WithSettings();
		$hook = new Hook();
		$hook->parse($instance);

		$this->assertTrue(isset($GLOBALS['wp_filter']['admin_bar_init'][3210]));
		$this->assertTrue(isset($GLOBALS['wp_filter']['the_content'][3210]));

		$hook->removeAction('admin_bar_init', WithSettings::class . '::bar', 3210);

		$this->assertFalse(isset($GLOBALS['wp_filter']['admin_bar_init'][3210]));
		$this->assertTrue(isset($GLOBALS['wp_filter']['the_content'][3210]));
	}
}

// phpcs:disable
#[Action(name: 'init', priority: 234, acceptedArgs: 2)]
class Foo
{
	public function __invoke(): void
	{
	}
}

#[Filter(name: 'the_title', priority: 432)]
class Bar
{
	public function __invoke(): string
	{
		return '';
	}
}

class WithConstructor
{
	#[Action(name: 'muplugins_loaded', priority: 100)]
	public function __construct()
	{

	}
}

class WithDestructor
{
	#[Action(name: 'setup_theme', priority: 123)]
	public function __destruct()
	{

	}
}

class WithDoubleDashed
{
	#[Action(name: 'wp_loaded', priority: 345)]
	public static function __callStatic(mixed $name, mixed $arguments)
	{

	}
}

class WithPrivateMethod
{
	#[Action(name: 'admin_bar_init', priority: 99)]
	private function foo()
	{

	}
}

class WithSettings
{
	#[Action(name: 'admin_bar_init', priority: 3210, options: ['ref' => 'bar'])]
	public function bar()
	{
	}

	#[Filter(name: 'the_content', priority: 3210, options: ['ref' => 'content'])]
	public function content()
	{
		return '';
	}
}
