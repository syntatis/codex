<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Hooks;

use ArgumentCountError;
use Codex\Foundation\Hooks\Exceptions\RefExistsException;
use Codex\Foundation\Hooks\Hook;
use Codex\Tests\WPTestCase;
use InvalidArgumentException;

class HookTest extends WPTestCase
{
	private Hook $hook;

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function set_up(): void
	{
		parent::set_up();

		$this->hook = new Hook();
	}

	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_down(): void
	{
		$this->hook->removeAll();

		parent::tear_down();
	}

	public function testAddAction(): void
	{
		$func = static fn () => true;
		$this->hook->addAction('wp', $func);

		$this->assertSame(10, has_action('wp', $func));
	}

	public function testAddActionPriority(): void
	{
		$func = static fn () => true;
		$this->hook->addAction('init', $func, 100);

		$this->assertSame(100, has_action('init', $func));
	}

	public function testAddActionAcceptedArgs(): void
	{
		$this->hook->addAction(
			'auth_cookie_malformed',
			static function ($cookie, $scheme): void {
			},
			100,
			2,
		);

		do_action('auth_cookie_malformed', '123', 'auth');

		$this->hook->addAction(
			'auth_cookie_malformed',
			static function ($cookie, $scheme): void {
			},
			100,
		);

		$this->expectException(ArgumentCountError::class);

		do_action('auth_cookie_malformed', '123', 'auth');
	}

	public function testAddFilter(): void
	{
		$func = static function ($value) {
			return $value;
		};

		$this->hook->addFilter('all_plugins', $func);

		$this->assertSame(10, has_filter('all_plugins', $func));
	}

	public function testAddFilterPriority(): void
	{
		$func = static function ($value) {
			return $value;
		};
		$this->hook->addFilter('all_plugins', $func, 100);

		$this->assertSame(100, has_filter('all_plugins', $func));
	}

	public function testAddFilterAcceptedArgs(): void
	{
		$this->hook->addFilter('allow_empty_comment', static function ($allowEmptyComment, $commentData) {
			return $allowEmptyComment;
		}, 100, 2);

		apply_filters('allow_empty_comment', false, []);

		$this->hook->addFilter('allow_empty_comment', static function ($allowEmptyComment, $commentData) {
			return $allowEmptyComment;
		}, 100);

		$this->expectException(ArgumentCountError::class);

		apply_filters('allow_empty_comment', false, []);
	}

	public function testRemoveAction(): void
	{
		$func1 = static function ($value): void {
		};
		$func2 = static function ($value): void {
		};
		$this->hook->addAction('wp', $func1, 30);
		$this->hook->addAction('wp', $func2, 30);

		$this->assertSame(30, has_action('wp', $func1));
		$this->assertSame(30, has_action('wp', $func2));

		$this->hook->removeAction('wp', $func2, 30);

		$this->assertSame(30, has_action('wp', $func1));
		$this->assertFalse(has_action('wp', $func2));
	}

	public function testRemoveActionNamedFunction(): void
	{
		$this->hook->addAction('get_sidebar', '__return_false', 39, 1);

		$this->assertSame(39, has_action('get_sidebar', '__return_false'));

		$this->hook->removeAction('get_sidebar', '__return_false', 39);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));
	}

	public function testRemoveActionInvalidNamedFunction(): void
	{
		$this->hook->addAction('get_sidebar', '__return_true', 190);

		$this->assertSame(190, has_action('get_sidebar', '__return_true'));

		$this->hook->removeAction('get_sidebar', '__invalid_function__', 190);

		$this->assertSame(190, has_action('get_sidebar', '__return_true'));
	}

	public function testRemoveActionClassMethod(): void
	{
		$callback = new CallbackTest();

		$this->hook->addAction('admin_bar_init', [$callback, 'init'], 25);

		$this->assertSame(25, has_action('admin_bar_init', [$callback, 'init']));

		$this->hook->removeAction('admin_bar_init', 'Codex\Tests\Foundation\Hooks\CallbackTest::init', 25);

		$this->assertFalse(has_action('admin_bar_init', [$callback, 'init']));
	}

	public function testRemoveActionInvalidClassMethod(): void
	{
		$callback = new CallbackTest();

		$this->hook->addAction('admin_bar_init', [$callback, 'init'], 26);

		$this->assertSame(26, has_action('admin_bar_init', [$callback, 'init']));

		// The `run` method does not exist.
		$this->hook->removeAction('admin_bar_init', 'Codex\Tests\Foundation\Hooks\CallbackTest::run', 26);

		$this->assertSame(26, has_action('admin_bar_init', [$callback, 'init']));
	}

	/** @group with-ref */
	public function testSetInvalidRef(): void
	{
		$func = static fn ($value) => null;

		$this->expectException(InvalidArgumentException::class);

		$this->hook->addAction('wp_footer', $func, 70, 1, ['id' => '@bar']);
	}

	/** @group with-ref */
	public function testRemoveActionAnonymousFunction(): void
	{
		$func = static fn ($value) => null;
		$this->hook->addAction('register_sidebar', $func, 50, 1, ['id' => 'bar']);

		$this->assertSame(50, has_action('register_sidebar', $func));

		$this->hook->removeAction('register_sidebar', '@bar', 50);

		$this->assertFalse(has_action('register_sidebar', $func));
	}

	/** @group with-ref */
	public function testRemoveActionAnonymousFunctionWithInvalidRef(): void
	{
		// With invalid ref.
		$func = static fn ($value) => null;
		$this->hook->addAction('register_sidebar', $func, 51, 1, ['id' => 'bar']);

		$this->assertSame(51, has_action('register_sidebar', $func));

		$this->hook->removeAction('register_sidebar', '@no-bar', 50);

		$this->assertSame(51, has_action('register_sidebar', $func));
	}

	/** @group with-ref */
	public function testRemoveActionNamedFunctionWithRef(): void
	{
		// Remove with named function.
		$this->hook->addAction('get_sidebar', '__return_false', 39, 1, ['id' => 'named-func-1']);

		$this->assertSame(39, has_action('get_sidebar', '__return_false'));

		$this->hook->removeAction('get_sidebar', '__return_false', 39);

		$this->assertFalse(has_action('get_sidebar', '__return_false'));

		// Remove with ref.
		$this->hook->addAction('get_sidebar', '__return_null', 40, 1, ['id' => 'named-func-2']);

		$this->assertSame(40, has_action('get_sidebar', '__return_null'));

		$this->hook->removeAction('get_sidebar', '@named-func-2', 40);

		$this->assertFalse(has_action('get_sidebar', '__return_null'));
	}

	/** @group with-ref */
	public function testRemoveActionNamedFunctionWithInvalidRef(): void
	{
		$this->hook->addAction('get_sidebar', '__return_true', 41, 1, ['id' => 'name-func-3']);

		$this->assertSame(41, has_action('get_sidebar', '__return_true'));

		$this->hook->removeAction('get_sidebar', '@no-name-func-3', 41);

		$this->assertSame(41, has_action('get_sidebar', '__return_true'));
	}

	/** @group with-ref */
	public function testRemoveActionClassMethodWithRef(): void
	{
		$callback = new CallbackTest();

		$this->hook->addAction('wp_head', [$callback, 'init'], 33, 1, ['id' => 'class-1']);

		$this->assertSame(33, has_action('wp_head', [$callback, 'init']));

		$this->hook->removeAction('wp_head', 'Codex\Tests\Foundation\Hooks\CallbackTest::init', 33);

		$this->assertFalse(has_action('wp_head', [$callback, 'init']));

		// Remove with ref.
		$callback = new CallbackTest();

		$this->hook->addAction('wp_head', [$callback, 'init'], 34, 1, ['id' => 'class-2']);

		$this->assertSame(34, has_action('wp_head', [$callback, 'init']));

		$this->hook->removeAction('wp_head', '@class-2', 34);

		$this->assertFalse(has_action('wp_head', [$callback, 'init']));
	}

	/** @group with-ref */
	public function testRemoveActionClassMethodWithInvalidRef(): void
	{
		$callback = new CallbackTest();

		$this->hook->addAction('wp_head', [$callback, 'init'], 35, 1, ['id' => 'class-3']);

		$this->assertSame(35, has_action('wp_head', [$callback, 'init']));

		$this->hook->removeAction('wp_head', '@no-class-3', 35);

		$this->assertSame(35, has_action('wp_head', [$callback, 'init']));
	}

	/** @group with-ref */
	public function testRemoveFilterAnonymousFunction(): void
	{
		$func = static fn ($value) => null;

		$this->hook->addFilter('icon_dir', $func, 10, 1, ['id' => 'body']);

		$this->assertSame(10, has_filter('icon_dir', $func));

		$this->hook->removeFilter('icon_dir', '@body', 10);

		$this->assertFalse(has_filter('icon_dir', $func));
	}

	/** @group with-ref */
	public function testRemoveFilterNamedFunctionWithRef(): void
	{
		$this->hook->addFilter('get_the_excerpt', '__return_empty_string', 28, 1, ['id' => 'filter-named-func-1']);

		$this->assertSame(28, has_action('get_the_excerpt', '__return_empty_string'));

		$this->hook->removeFilter('get_the_excerpt', '__return_empty_string', 28);

		$this->assertFalse(has_action('get_the_excerpt', '__return_empty_string'));

		// Remove with ref.
		$this->hook->addFilter('get_the_excerpt', '__return_null', 200, 1, ['id' => 'filter-named-func-2']);

		$this->assertSame(200, has_action('get_the_excerpt', '__return_null'));

		$this->hook->removeFilter('get_the_excerpt', '@filter-named-func-2', 200);

		$this->assertFalse(has_action('get_the_excerpt', '__return_null'));
	}

	/** @group with-ref */
	public function testRemoveFilterNamedFunctionWithInvalidRef(): void
	{
		$this->hook->addFilter('get_the_archive_title', '__return_true', 280, 1, ['id' => 'filter-named-func-3']);

		$this->assertSame(280, has_action('get_the_archive_title', '__return_true'));

		$this->hook->removeFilter('get_the_archive_title', '@no-filter-named-func-3', 280);

		$this->assertSame(280, has_action('get_the_archive_title', '__return_true'));
	}

	/** @group with-ref */
	public function testRemoveFilterClassMethodWithRef(): void
	{
		$callback = new CallbackTest();

		$this->hook->addFilter('the_content', [$callback, 'init'], 51, 1, ['id' => 'filter-class-1']);

		$this->assertSame(51, has_filter('the_content', [$callback, 'init']));

		$this->hook->removeFilter('the_content', 'Codex\Tests\Foundation\Hooks\CallbackTest::init', 51);

		$this->assertFalse(has_filter('the_content', [$callback, 'init']));

		// Remove with ref.
		$callback = new CallbackTest();

		$this->hook->addFilter('the_title', [$callback, 'init'], 52, 1, ['id' => 'filter-class-2']);

		$this->assertSame(52, has_filter('the_title', [$callback, 'init']));

		$this->hook->removeFilter('the_title', '@filter-class-2', 52);

		$this->assertFalse(has_filter('the_title', [$callback, 'init']));
	}

	/** @group with-ref */
	public function testRemoveFilterClassMethodWithInvalidRef(): void
	{
		$callback = new CallbackTest();

		$this->hook->addFilter('body_class', [$callback, 'init'], 62, 1, ['id' => 'filter-class-3']);

		$this->assertSame(62, has_filter('body_class', [$callback, 'init']));

		$this->hook->removeFilter('body_class', '@no-filter-class-3', 62);

		$this->assertSame(62, has_filter('body_class', [$callback, 'init']));
	}

	/** @group with-ref */
	public function testAddRefExists(): void
	{
		$this->hook->addFilter('the_content', static fn () => true, 320, 1, ['id' => 'ref-true']);

		$this->expectException(RefExistsException::class);

		$this->hook->addFilter('the_content_rss', static fn () => true, 320, 1, ['id' => 'ref-true']);
	}

	public function testRemoveAll(): void
	{
		$func = static function ($value): void {
		};
		$funcNative = static function ($value): void {
		};

		add_action('wp', $funcNative);
		add_action('init', $funcNative);
		add_filter('the_content', $funcNative);
		add_filter('all_plugins', $funcNative);

		$this->hook->addAction('wp', $func);
		$this->hook->addAction('init', $func);
		$this->hook->addFilter('the_content', $func);
		$this->hook->addFilter('all_plugins', $func);

		// Actions.
		$this->assertSame(10, has_action('wp', $func));
		$this->assertSame(10, has_action('init', $func));
		$this->assertSame(10, has_action('wp', $funcNative));
		$this->assertSame(10, has_action('init', $funcNative));

		// Filters.
		$this->assertSame(10, has_filter('the_content', $func));
		$this->assertSame(10, has_filter('all_plugins', $func));
		$this->assertSame(10, has_filter('the_content', $funcNative));
		$this->assertSame(10, has_filter('all_plugins', $funcNative));

		// These methods should de-register all actions and filters.
		$this->hook->removeAll();

		// List of actions and filters, added with `add_action` and `add_filter`.
		$this->assertSame(10, has_action('wp', $funcNative));
		$this->assertSame(10, has_action('init', $funcNative));
		$this->assertSame(10, has_filter('the_content', $funcNative));
		$this->assertSame(10, has_filter('all_plugins', $funcNative));

		// List of actions and filters, added with `addAction` and `addFilter` from `Hook`.
		$this->assertFalse(has_action('wp', $func));
		$this->assertFalse(has_action('init', $func));
		$this->assertFalse(has_filter('the_content', $func));
		$this->assertFalse(has_filter('all_plugins', $func));
	}
}

// phpcs:disable
class CallbackTest {
	public function init(): void
	{
	}

	public function change(): string
	{
		return '';
	}
}
