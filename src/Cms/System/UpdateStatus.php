<?php

namespace Kirby\Cms\System;

use Kirby\Toolkit\I18n;

enum UpdateStatus: string
{
	case UpToDate = 'up-to-date';
	case NotVulnerable = 'not-vulnerable';

	case SecurityUpdate = 'security-update';
	case SecurityUpgrade = 'security-upgrade';
	case Update = 'update';
	case Upgrade = 'upgrade';

	case Unreleased = 'unreleased';

	case Error = 'error';

	/**
	 * Returns the Panel icon for the status
	 */
	public function icon(): string
	{
		return match ($this) {
			static::UpToDate,
			static::NotVulnerable => 'check',

			static::SecurityUpdate,
			static::SecurityUpgrade => 'alert',

			static::Update,
			static::Upgrade => 'info',

			static::Unreleased => 'hidden',

			default => 'question'
		};
	}

	/**
	 * Returns the human-readable and translated label
	 * for the update status
	 */
	public function label(string|null $version = null): string
	{
		return I18n::template(
			'system.updateStatus.' . $this->value,
			'?',
			['version' => $version ?? '?']
		);
	}

	/**
	 * Returns the Panel theme for the status
	 */
	public function theme(): string
	{
		return match ($this) {
			static::UpToDate,
			static::NotVulnerable => 'positive',

			static::SecurityUpdate,
			static::SecurityUpgrade => 'negative',

			static::Update,
			static::Upgrade => 'info',

			static::Unreleased => 'purple',

			default => 'notice'
		};
	}
}
