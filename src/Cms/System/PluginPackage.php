<?php

namespace Kirby\Cms\System;

use Kirby\Plugin\Plugin as Plugin;

class PluginPackage extends Package
{
	protected string|null $name;

	public function __construct(
		protected Plugin $plugin,
		protected bool $securityOnly = false,
		array|null $data = null
	) {
		$this->name    = $plugin->name();
		$this->version = $plugin->version() ?? '0.0.0';

		parent::__construct($plugin->kirby(), $securityOnly, $data);
	}

	public function key(): string
	{
		return 'plugins/' . $this->name;
	}

	/**
	 * Returns the human-readable package name for error messages
	 */
	public function name(): string
	{
		return 'plugin "' . $this->name . '"';
	}

	public function toArray(): array
	{
		return [
			...parent::toArray(),
			'pluginName' => $this->name,
		];
	}
}
