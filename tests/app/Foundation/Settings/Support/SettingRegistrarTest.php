<?php

declare(strict_types=1);

namespace Codex\Tests\Foundation\Settings\Support;

use Codex\Foundation\Settings\Setting;
use Codex\Foundation\Settings\Support\SettingRegistrar;
use Codex\Tests\WPTestCase;

class SettingRegistrarTest extends WPTestCase
{
	public function testName(): void
	{
		$setting = new Setting('say');
		$registrar = new SettingRegistrar($setting, 'codex');

		$this->assertSame('say', $registrar->getName());

		$registrar->setPrefix('codex_prefix_');

		$this->assertSame('codex_prefix_say', $registrar->getName());
	}
}
