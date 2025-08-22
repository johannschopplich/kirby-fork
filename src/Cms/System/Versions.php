<?php

namespace Kirby\Cms\System;

use Kirby\Toolkit\A;

class Versions
{
	protected bool $hasNoVulnerabilities = false;
	protected Version|false $version;

	/**
	 * @param array<string, \Kirby\Cms\System\Version> $versions
	 */
	public function __construct(
		protected Package $package,
		protected array $versions = []
	) {
	}

	public static function factory(
		Package $package,
		array $versions
	): static {
		return new static(
			package:  $package,
			versions: A::map(
				$versions,
				fn ($version) => Version::factory($version)
			)
		);
	}

	/**
	 * Extracts the first matching version entry from
	 * the data array unless no data is available
	 */
	public function find(): Version|null {
		if (isset($this->version) === true) {
			return $this->version ?: null;
		}

		$current = $this->package->version('current');
		$latest  = $this->package->version('latest');

		// special check for unreleased versions
		if (
			$latest !== null &&
			version_compare($current, $latest, '>') === true
		) {
			return new Version(status: VersionStatus::Unreleased);
		}

		foreach ($this->versions as $constraint => $version) {
			// filter out every entry that does not match the current version
			if ($this->package->matchVersion($current, $constraint, 'while finding version entry') !== true) {
				continue;
			}

			if ($version->status() === VersionStatus::NoVulnerabilities) {
				$this->hasNoVulnerabilities = true;

				// use the next matching version entry with
				// more specific update information
				continue;
			}

			if ($version->status() === VersionStatus::Latest) {
				$this->hasNoVulnerabilities = true;
			}

			return $this->version = $version;
		}

		$this->package->error(
			'No matching version entry found for {package}@' . $current
		);

		$this->version = false;
		return null;
	}

	/**
	 * @todo Shouldn't this just be available for system, not plugins?
	 */
	public function findMaximumFreeUpdate(): string|null
	{
		$current = $this->package->version('current');

		return A::find(
			$this->versions,
			fn (Version $version) => $version->isFreeUpdate($current)
		);
	}

	public function hasNoVulnerabilities(): bool
	{
		return $this->hasNoVulnerabilities;
	}
}
