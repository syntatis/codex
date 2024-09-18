<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation;

use Codex\Foundation\Blocks;
use Codex\Tests\WPTestCase;
use InvalidArgumentException;
use WP_Block_Type_Registry;

class BlocksTest extends WPTestCase
{
	public function testInvalidDirectory(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The blocks path is not a directory.');

		new Blocks('');
	}

	public function testRegister(): void
	{
		$blocks = new Blocks(self::getFixturesPath('/inc/blocks'));
		$blocks->register();

		$blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

		self::assertTrue(isset($blocks['codex/block-a']));
		self::assertTrue(isset($blocks['codex/block-b']));
		self::assertFalse(isset($blocks['codex/block-c']));
	}
}
