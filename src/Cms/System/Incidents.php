<?php

namespace Kirby\Cms\System;

use Kirby\Toolkit\A;

class Incidents
{
	protected array $messages;

	/**
	 * @param array<\Kirby\Cms\System\Incident> $incidents
	 */
	public function __construct(
		protected Package $package,
		protected array $incidents
	) {
		$this->incidents = IncidentSeverity::sort($this->incidents);
	}

	public static function factory(
		Package $package,
		array $incidents = []
	): static {
		// shortcut for versions without vulnerabilities
		if ($package->versions()->hasNoVulnerabilities() === true) {
			$incidents = [];
		}

		return new static(
			package: $package,
			incidents: A::map(
				$incidents,
				fn (array $incident) => Incident::factory($incident)
			)
		);
	}

	/**
	 * Filters a two-level array with one or multiple version constraints
	 * for each value by one or multiple version filters;
	 * values that don't contain the filter keys are removed
	 *
	 * @param array $array Array that contains associative arrays
	 * @param array $filters Associative array `field => version`
	 * @param string $reason Suffix for error messages
	 */
	public function filter(
		array $array,
		array $filters,
		string $reason
	): array {
		$package = $this->package;

		return array_filter($array, function ($item) use ($filters, $package, $reason): bool {
			foreach ($filters as $key => $version) {
				if (isset($item[$key]) !== true) {
					$package->error('Missing constraint ' . $key . ' for {package} {reason}');
					return false;
				}

				if ($package->matchVersion($version, $item[$key], $reason) !== true) {
					return false;
				}
			}

			return true;
		});
	}

	/**
	 * Finds the minimum possible security update
	 * to fix all known vulnerabilities
	 *
	 * @return string|null Version number of the update or
	 *                     `null` if no free update is possible
	 */
	public function findMinimumSecurityUpdate(
		string $minVersion,
		string $maxVersion
	): string|null {
		// increase the target version number until there are no vulnerabilities
		$version    = $minVersion;
		$iterations = 0;
		$incidents  = $this->package->data['incidents'] ?? [];
		$affected   = $this->vulnerabilities();

		while ($affected !== []) {
			// protect against infinite loops if the
			// input data is contradicting itself
			$iterations++;

			if ($iterations > 10) {
				return null;
			}

			// if we arrived at the `$maxVersion` but still haven't found
			// a version without vulnerabilities, we cannot suggest a version
			if ($version === $maxVersion) {
				return null;
			}

			// find the minimum version that fixes all affected vulnerabilities
			foreach ($affected as $incident) {
				$incidentVersion = $incident->findMinimumFix($minVersion, $maxVersion);

				// verify that we found at least one possible version;
				// otherwise try the `$maxVersion` as a last chance before
				// concluding at the top that we cannot solve the task
				$incidentVersion ??= $maxVersion;

				// we need a version that fixes all vulnerabilities, so use the
				// "largest of the smallest" fixed versions
				if (version_compare($incidentVersion, $version, '>') === true) {
					$version = $incidentVersion;
				}
			}

			// run another loop to verify that the suggested version
			// doesn't have any known vulnerabilities on its own
			$affected = $this->filter(
				$incidents,
				['affected' => $version],
				'while filtering incidents'
			);
		}

		return $version;
	}

	/**
	 * Returns all incidents that affect the current version
	 * @return array<\Kirby\Update\Incident>
	 */
	public function vulnerabilities(): array
	{
		$current = $this->package->version('current');

		// unstable releases are released before their respective
		// stable release and would not be matched by the constraints,
		// but they will likely also contain the same vulnerabilities;
		// so we strip off any non-numeric version modifiers from the end
		preg_match('/^([0-9.]+)/', $current, $matches);

		$vulnerabilities = static::filter(
			$this->incidents,
			['affected' => $matches[1]],
			'while filtering incidents'
		);

		return $vulnerabilities;
	}
}
