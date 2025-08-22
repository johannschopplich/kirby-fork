<?php

namespace Kirby\Cms\System;

class Update
{
	public function __construct(
		public UpdateStatus $status,
		public string|null $version = null,
		public string|null $url = null,
	) {
	}

	/**
	 * @todo Shouldn't this just be available for system, not plugins?
	 */
	public static function findMaximumFreeUpdate(
		Package $package,
	): string|null {
		return $package->versions()->findMaximumFreeUpdate();
	}

	/**
	 * Finds the minimum possible security update
	 * to fix all known vulnerabilities
	 *
	 * @return string|null Version number of the update or
	 *                     `null` if no free update is possible
	 */
	public static function findMinimumSecurityUpdate(
		Package $package,
	): string|null {
		$version = $package->versions()->find();

		if ($version === null || $version->latest() === null) {
			return null; // @codeCoverageIgnore
		}

		return $package->incidents()->findMinimumSecurityUpdate(
			minVersion: $package->version('current'),
			maxVersion: $version->latest()
		);
	}

	public static function for(Package $package): static
	{
		// check if we have valid data to compare to
		$current = $package->version('current');
		$latest  = $package->version('latest');
		$target  = $package->versions()->find($current, $latest);

		if ($target === null) {
			return new static(
				status: UpdateStatus::Error,
				url:    $package->urls()->find($current, 'changes'),
			);
		}

		// current version is latest available
		if ($target->status() === VersionStatus::Latest) {
			return new static(
				status: UpdateStatus::UpToDate,
				url:    $package->urls()->find($current, 'changes'),
			);
		}

		// current version is unreleased
		if ($target->status() === VersionStatus::Unreleased) {
			return new static(status: UpdateStatus::Unreleased);
		}

		// check if the installation is vulnerable;
		// minimum possible security fixes are preferred
		// over all other updates and upgrades
		if ($package->hasVulnerabilities() === true) {
			// a free security update was found
			if ($update = static::findMinimumSecurityUpdate($package)) {
				return new static(
					status:  UpdateStatus::SecurityUpdate,
					url:     $package->urls()->find($update, 'changes'),
					version: $update,
				);
			}

			// only a paid security upgrade is possible
			return new static(
				status:  UpdateStatus::SecurityUpgrade,
				version: $latest,
				url:     $package->urls()->find($latest, 'upgrade'),
			);
		}

		// check if the user limited update checking to security updates
		if ($package->securityOnly() === true) {
			return new static (
				status: UpdateStatus::NotVulnerable,
				url:    $package->urls()->find($current, 'changes'),
			);
		}

		// update within the same major version
		if ($latest !== null && $latest !== $current) {
			return new static(
				status:  UpdateStatus::Update,
				url:     $package->urls()->find($latest, 'changes'),
				version: $latest,
			);
		}

		// license includes updates to a newer major version
		if ($version = static::findMaximumFreeUpdate($package)) {
			// extract the part before the first dot
			// to find the major release page URL
			preg_match('/^(\w+)\./', $version, $matches);

			return new static(
				status:  UpdateStatus::Update,
				url:     $package->urls()->find($matches[1] . '.0', 'changes'),
				version: $version,
			);
		}

		// no free update is possible, but we are not on the latest version,
		// so the overall latest version must be an upgrade
		return new static(
			status:  UpdateStatus::Upgrade,
			url:     $package->urls()->find($latest, 'upgrade'),
			version: $latest,
		);
	}

	public function icon(): string
	{
		return $this->status->icon();
	}

	public function label(): string
	{
		return $this->status->label($this->version);
	}

	public function status(): UpdateStatus
	{
		return $this->status;
	}

	public function theme(): string
	{
		return $this->status->theme();
	}

	public function url(): string|null
	{
		return $this->url;
	}

	public function version(): string|null
	{
		return $this->version;
	}
}
