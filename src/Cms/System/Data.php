<?php

namespace Kirby\Cms\System;

use Kirby\Exception\Exception;
use Kirby\Http\Remote;
use Throwable;

class Data
{
	/**
	 * Marker that stores whether a previous
	 * remote request timed out
	 */
	protected static bool $timedOut = false;

	/**
	 * Loads the getkirby.com update data
	 * from cache or via HTTP
	 */
	public static function load(
		Package $package
	): array|null {
		$kirby   = $package->kirby();
		$version = $package->version('current');

		// try to get the data from cache
		$cache = $kirby->cache('updates');
		$key   = $package->key();
		$data  = $cache->get($key);

		// valid cached data for the current version
		if (
			is_array($data) === true &&
			$data['_version'] === $version
		) {
			return $data;
		}

		// exception message (on previous request error)
		if (is_string($data) === true) {
			// restore the exception to make it visible when debugging
			$package->error($data);
		}

		// before we request the data, ensure we have a writable cache;
		// this reduces strain on the CDN from repeated requests
		if ($cache->enabled() === false) {
			$package->error(
				'Cannot check for updates without a working "updates" cache'
			);
			return null;
		}

		$data = static::fetch($package, $key);

		if ($data === null) {
			return null;
		}

		if (is_array($data) !== true) {
			// also cache the current version to
			// invalidate the cache after updates
			// (ensures that the update status is
			// fresh directly after the update to
			// avoid confusion with outdated info)
			$data['_version'] = $version;
		}

		// cache the retrieved data for three days
		$cache->set($key, $data, 3 * 24 * 60);

		return $data;
	}

	public static function fetch(
		Package $package,
		string $key,
	): array|string|null {
		// first catch every exception;
		// we collect it below for debugging
		try {
			if (static::$timedOut === true) {
				throw new Exception(message: 'Previous remote request timed out'); // @codeCoverageIgnore
			}

			$response = Remote::get(
				Package::$host . '/' . $key . '.json',
				['timeout' => 2]
			);

			// allow status code HTTP 200 or 0 (e.g. for the file:// protocol)
			if (in_array($response->code(), [0, 200], true) !== true) {
				throw new Exception(message: 'HTTP error ' . $response->code()); // @codeCoverageIgnore
			}

			$data = $response->json();

			if (is_array($data) !== true) {
				throw new Exception(message: 'Invalid JSON data');
			}

			return $data;

		} catch (Throwable $e) {
			$exception = $package->error(
				message: 'Could not load update data for {package}: ' . $e->getMessage(),
				previous: $e
			);

			// if the request timed out, prevent additional
			// requests for other packages (e.g. plugins)
			// to avoid long Panel hangs
			if ($e->getCode() === 28) {
				static::$timedOut = true; // @codeCoverageIgnore
			} elseif (static::$timedOut === false) {
				// different error than timeout;
				// prevent additional requests in the
				// next three days (e.g. if a plugin
				// does not have a page on getkirby.com)
				// by caching the exception message
				// instead of the data array
				return $exception->getMessage();
			}

			return null;
		}
	}
}
