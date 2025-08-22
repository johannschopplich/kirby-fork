<?php

namespace Kirby\Cms\System;

use Kirby\Cms\App;
use Kirby\Toolkit\I18n;

class Messages
{
	public function __construct(
		protected Package $package,
		protected array $messages = []
	) {
		// add a message for each vulnerability
		// the current version is affected by
		$this->messages = [
			...$this->messages,
			...$this->vulnerabilities()
		];
	}

	public static function factory(
		Package $package,
		array $messages = [],
		array $php = []
	): static|null {
		$current = $package->version('current');
		$type    = $package instanceof PluginPackage ? 'plugin' : 'kirby';

		// collect all matching custom messages
		$filters = [
			'kirby' => App::instance()->version(),
			// some PHP version strings contain extra info that makes them
			// invalid so we need to strip it off
			'php'   => preg_replace('/^([^~+-]+).*$/', '$1', phpversion())
		];

		if ($type === 'plugin') {
			$filters['plugin'] = $current;
		}

		$messages = $this->filterArrayByVersion(
			$messages,
			$filters,
			'while filtering messages'
		);

		// add special message for end-of-life versions
		if ($eol = static::eol($package->versions()->find(), $type)) {
			$messages[] = $eol;
		}

		// add special message for end-of-life PHP versions
		if ($php = static::php($php)) {
			$messages[] = $php;
		}

		return new static(
			package:  $package,
			messages: $messages
		);
	}

	protected static function eol(Version|null $version, string $type): array|null
	{
		if ($version?->status() === VersionStatus::EndOfLife) {
			return [
				'text' => match ($type) {
					'plugin' => I18n::template('system.issues.eol.plugin'),
					'kirby'  => I18n::translate('system.issues.eol.kirby')
				},
				'link' => $version->link() ?? 'https://getkirby.com/security/end-of-life',
				'icon' => 'bell'
			];
		}

		return null;
	}

	protected static function php(array $php): array|null
	{
		$phpMajor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$phpEol   = $php[$phpMajor] ?? null;

		if (is_string($phpEol) === true && $eolTime = strtotime($phpEol)) {
			// the timestamp is available and valid, now check if it is in the past
			if ($eolTime < time()) {
				return [
					'text' => I18n::template('system.issues.eol.php', null, ['release' => $phpMajor]),
					'link' => 'https://getkirby.com/security/php-end-of-life',
					'icon' => 'bell'
				];
			}
		}

		return null;
	}

	public function vulnerabilities(): array
	{
		$vulnerabilities = $this->package->incidents()->vulnerabilities();
		$messages        = [];

		foreach ($vulnerabilities as $vulnerability) {
			$messages[] = [
				'text' => $vulnerability->label($this->package),
				'link' => $vulnerability->link(),
				'icon' => $vulnerability->icon()
			];
		}

		return $messages;
	}
}
