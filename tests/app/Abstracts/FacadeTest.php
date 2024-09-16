<?php

declare(strict_types=1);

namespace Codex\Tests\Abstracts;

use Codex\Abstracts\Facade;
use Codex\Tests\WPTestCase;
use Pimple\Container;
use Pimple\Exception\UnknownIdentifierException;
use Pimple\Psr11\Container as Psr11Container;

class FacadeTest extends WPTestCase
{
	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function tear_down(): void
	{
		Facade::clearResolvedInstances();

		parent::tear_down();
	}

	public function testUndefinedService(): void
	{
		$container = new Container();

		Facade::setFacadeApplication(new Psr11Container($container));

		$this->expectException(UnknownIdentifierException::class);

		Trumpet::get();
	}

	public function testSwappingServiceAndReset(): void
	{
		$container = new Container();
		$container['trumpet'] = new TrumpetObject();

		Facade::setFacadeApplication(new Psr11Container($container));

		self::assertSame('A trumpet, a brass instrument played in jazz bands.', Trumpet::get());

		Trumpet::swap(new OtherTrumpetObject());

		self::assertSame('A trumpet is a brass wind instrument commonly used in classical music, jazz, and other musical genres.', Trumpet::get());

		Trumpet::reset();

		self::assertSame('A trumpet, a brass instrument played in jazz bands.', Trumpet::get());
	}
}

// phpcs:disable
/**
 * @method static string get()
 * @method static string itSelf()
 */
class Trumpet extends Facade
{
	protected static function getFacadeAccessor(): string
    {
        return 'trumpet';
    }
}

class TrumpetObject
{
	public function get(): string
	{
		return 'A trumpet, a brass instrument played in jazz bands.';
	}

	public function itSelf(): self
	{
		return $this;
	}
}

class OtherTrumpetObject
{
	public function get(): string
	{
		return 'A trumpet is a brass wind instrument commonly used in classical music, jazz, and other musical genres.';
	}
}
