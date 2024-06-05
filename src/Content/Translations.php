<?php

namespace Kirby\Content;

use Kirby\Cms\Collection;
use Kirby\Cms\Language;
use Kirby\Cms\Languages;
use Kirby\Cms\ModelWithContent;

/**
 * @package   Kirby Content
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 *
 * @extends \Kirby\Cms\Collection<\Kirby\Content\Translation>
 */
class Translations extends Collection
{
	public static function create(
		ModelWithContent $model,
		Version $version,
		array $translations
	): static {
		foreach ($translations as $translation) {
			Translation::create(
				model: $model,
				version: $version,
				language: Language::ensure($translation['code'] ?? 'default'),
				fields: $translation['content'] ?? [],
				slug: $translation['slug'] ?? null
			);
		}

		return static::load(
			model: $model,
			version: $version
		);
	}

	public function findByKey(string $key): Translation|null
	{
		try {
			return parent::get(Language::ensure($key)->code());
		} catch (NotFoundException) {
			return null;
		}
	}

	public static function load(
		ModelWithContent $model,
		Version $version
	): static {
		$translations = new static();

		foreach (Languages::ensure() as $language) {
			$translation = new Translation(
				model: $model,
				version: $version,
				language: $language
			);

			$translations->data[$language->code()] = $translation;
		}

		return $translations;
	}
}
