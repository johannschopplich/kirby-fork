<?php

namespace Kirby\Cms\System;

use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

class Incident
{
	public function __construct(
		public string $affected,
		public string $description,
		public string $fixed,
		public string $link,
		public IncidentSeverity $severity,
	) {
	}

	public function affected(): string
	{
		return $this->affected;
	}

	public function description(): string
	{
		return $this->description;
	}

	public static function factory(array $incident): static
	{
		return new static(
			affected:    $incident['affected'],
			description: $incident['description'],
			fixed:       $incident['fixed'],
			link:        $incident['link'],
			severity:    IncidentSeverity::from($incident['severity'])
		);
	}

	/**
	 * @todo Could we flip this to return early?
	 */
	public function findMinimumFix(
		string $minVersion,
		string $maxVersion
	): string|null {
		$version = null;

		foreach (Str::split($this->fixed(), ',') as $fixed) {
			// skip versions of other major releases
			if (
				version_compare($fixed, $minVersion, '<') === true ||
				version_compare($fixed, $maxVersion, '>') === true
			) {
				continue;
			}

			// find the minimum version that fixes this specific vulnerability
			if (
				$version === null ||
				version_compare($fixed, $version, '<') === true
			) {
				$version = $fixed;
			}
		}

		return $version;
	}

	public function fixed(): string
	{
		return $this->fixed;
	}

	public function icon(): string
	{
		return $this->severity->icon();
	}

	public function label(Package $package): string
	{
		$type = $package instanceof PluginPackage ? 'plugin' : 'kirby';

		$data = [
			'severity'    => $this->severity(),
			'description' => $this->description(),
		];

		if ($type === 'plugin') {
			$data['plugin'] = $package->name();
		}

		return I18n::template(
			'system.issues.vulnerability.' . $type,
			null,
			$data
		);
	}

	public function link(): string
	{
		return $this->link;
	}

	public function severity(): IncidentSeverity
	{
		return $this->severity;
	}
}
