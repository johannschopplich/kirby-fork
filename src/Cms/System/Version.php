<?php

namespace Kirby\Cms\System;

use Kirby\Cms\App;

class Version
{
	public function __construct(
		public VersionStatus $status,
		public string|null $description = null,
		public string|null $initial = null,
		public string|null $latest = null,
		public string|null $link = null,
	) {
	}

	public function description(): string|null
	{
		return $this->description;
	}

	public static function factory(array $version): static
	{
		return new static(
			description: $version['description'] ?? null,
			initial:     $version['initialRelease'] ?? null,
			latest:      $version['latest'] ?? null,
			link:        $version['status-link'] ?? null,
			status:      VersionStatus::tryFrom($version['status'] ?? null),
		);
	}

	public function initial(): int|null
	{
		return $this->initial ? strtotime($this->initial) : null;
	}

	public function isFreeUpdate(string $current): bool
	{
		$release = $this->initial();

		// skip unreleased versions
		if ($release === null) {
			return false;
		}

		// skip versions that are not newer than the current version
		if (version_compare($this->latest() ?? '', $current, '<=') === true) {
			return false;
		}

		// update is free if the initial release was
		// before the license renewal date
		return $release < App::instance()->system()->license()->renewal();
	}


	public function latest(): string|null
	{
		return $this->latest;
	}

	public function link(): string|null
	{
		return $this->link;
	}

	public function status(): VersionStatus|null
	{
		return $this->status;
	}
}
