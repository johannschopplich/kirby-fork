<?php

namespace Kirby\Cms\System;

use Kirby\Toolkit\A;

enum IncidentSeverity: string
{
	case Critical = 'critical';
	case High = 'high';
	case Medium = 'medium';
	case Low = 'low';
	case Default = 'default';

	public function icon(): string
	{
		return match ($this) {
			default => 'bug'
		};
	}

	/**
	 * Sorts incidents by their severity
	 * @param array<\Kirby\Cms\System\Incident> $incidents
	 * @return array<\Kirby\Cms\System\Incident>
	 */
	public static function sort(array $incidents): array
	{
		$severities = A::map(
			$incidents,
			fn (Incident $incident) => match ($incident->severity()) {
				self::Critical => 4,
				self::High     => 3,
				self::Medium   => 2,
				self::Low      => 1,
				default        => 0,
			}
		);

		array_multisort($severities, SORT_DESC, $incidents);
		return $incidents;
	}
}
