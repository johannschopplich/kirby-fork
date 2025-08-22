<?php

namespace Kirby\Cms\System;

use Kirby\Cms\App;
use Kirby\Exception\Exception;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;
use Kirby\Toolkit\V;
use Throwable;

abstract class Package
{
	/**
	 * Host to request the update data from
	 */
	public static string $host = 'https://getkirby.com';

	protected array $data;
	protected array $errors = [];
	protected Incidents $incidents;
	protected Messages $messages;
	protected Update $update;
	protected Urls $urls;
	protected string $version;
	protected Versions $versions;

	public function __construct(
		protected App $kirby,
		protected bool $securityOnly = false,
		array|null $data = null
	) {
		$this->data = $data ?? Data::load($this);
	}

	public function error(
		string $message,
		string $exception = Exception::class,
		Throwable|null $previous = null
	): Throwable {
		return $this->errors[] = new $exception(
			fallback: $message,
			previous: $previous
		);
	}

	public function errorVersionConstraint(
		Throwable $previous,
		string $reason
	): Throwable {
		return $this->error(
			message: 'Error comparing version constraint for {name} ' . $reason . ': ' . $previous->getMessage(),
			previous: $previous
		);
	}

	/**
	 * Returns the list of errors that
	 * were collected during processing
	 */
	public function errors(): array
	{
		return $this->errors;
	}

	/**
	 * Returns the list of exception message strings that
	 * were collected during processing and fill in placeholders
	 */
	public function errorMessages(): array
	{
		return A::map(
			$this->errors,
			fn (Throwable $e) => Str::template($e->getMessage(), [
				'name' => $this->name()
			]),
		);
	}

	public function hasUpdate(): bool
	{
		return $this->update() !== null;
	}

	public function hasVulnerabilities(): bool
	{
		return $this->incidents()->vulnerabilities() !== [];
	}

	public function icon(): string
	{
		return $this->update()->icon();
	}

	public function incidents(): Incidents
	{
		return $this->incidents ??= Incidents::factory(
			$this,
			$this->data['incidents'] ?? []
		);
	}

	abstract public function key(): string;

	public function kirby(): App
	{
		return $this->kirby;
	}

	public function label(): string
	{
		return $this->update()->label();
	}

	/**
	 * Compares a version against a Composer version constraint
	 * and returns whether the constraint is satisfied
	 *
	 * @param string $reason Suffix for error messages
	 */
	public function matchVersion(
		string $version,
		string $constraint,
		string $reason
	): bool {
		try {
			return V::version($version, $constraint);
		} catch (Exception $e) {
			$this->error(
				previous: $e,
				message: 'Error comparing version constraint for {package} ' . $reason . ': ' . $e->getMessage(),
			);
			return false;
		}
	}

	public function messages(): Messages
	{
		return $this->messages ??= Messages::factory(
			package: $this,
			messages: $this->data['messages'] ?? [],
			php: $this->data['php'] ?? []
		);
	}

	/**
	 * Returns the human-readable package name for error messages
	 */
	abstract public function name(): string;

	public function status(): UpdateStatus
	{
		return $this->update()->status();
	}

	public function securityOnly(): bool
	{
		return $this->securityOnly;
	}

	public function theme(): string
	{
		return $this->update()->theme();
	}

	 /**
	 * Returns the most important human-readable
	 * status information as array
	 */
	public function toArray(): array
	{
		return [
			'currentVersion' => $this->version('current') ?? '?',
			'icon'           => $this->icon(),
			'label'          => $this->label(),
			'latestVersion'  => $this->version('latest') ?? '?',
			'theme'          => $this->theme(),
			'url'            => $this->url(),
		];
	}

	public function update(): Update
	{
		return $this->update ??= Update::for($this);
	}

	public function url(): string|null
	{
		return $this->update()->url();
	}

	public function urls(): Urls
	{
		return $this->urls ??= new Urls(
			$this,
			$this->data['urls'] ?? []
		);
	}

	/**
	 * Returns the currently installed or latest version
	 * @param string $type 'current' or 'latest'
	 */
	public function version(string $type): string|null
	{
		return match ($type) {
			'current' => $this->version,
			'latest'  => $this->data['latest'] ?? null
		};
	}

	public function versions(): Versions
	{
		return $this->versions ??= Versions::factory(
			$this,
			$this->data['versions'] ?? []
		);
	}
}
