<?php

namespace Kirby\Cms\System;

use Kirby\Toolkit\Str;

class Urls
{
	public function __construct(
		protected Package $package,
		protected array $urls = []
	) {
	}

	/**
	 * Finds the first matching URL for the given version and purpose
	 */
	public function find(string $version, string $purpose): string|null
	{
		foreach ($this->urls as $constraint => $entry) {
			// filter out every entry that does not match the version
			if ($this->package->matchVersion($version, $constraint, 'while checking URL') !== true) {
				continue;
			}

			if ($url = $entry[$purpose] ?? null) {
				// insert the URL template placeholders
				return Str::template($url, [
					'current' => $this->package->version('current'),
					'version' => $version
				]);
			}
		}

		$this->package->error(
			message: 'No matching URL found for {package}@' . $version
		);

		return null;
	}
}
