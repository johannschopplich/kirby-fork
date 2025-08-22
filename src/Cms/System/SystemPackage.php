<?php

namespace Kirby\Cms\System;

use Kirby\Cms\App;

class SystemPackage extends Package
{
	public function __construct(
		protected App $kirby,
		protected bool $securityOnly = false,
		array|null $data = null
	) {
		$this->version = $kirby->version() ?? '0.0.0';
		parent::__construct($kirby, $securityOnly, $data);
	}

	public function key(): string
	{
		return 'security';
	}

	/**
	 * Returns the human-readable package name for error messages
	 */
	public function name(): string
	{
		return 'Kirby';
	}
}
