<?php

namespace Kirby\Content;

use Kirby\Cms\Language;

class TestContentStorageHandler extends ContentStorageHandler
{
	public function create(VersionId $versionId, Language $language, array $fields): void
	{
	}

	public function delete(VersionId $versionId, Language $language): void
	{
	}

	public function exists(VersionId $versionId, Language $language): bool
	{
	}

	public function modified(VersionId $versionId, Language $language): int|null
	{
	}

	public function move(
		VersionId $fromVersionId,
		Language $fromLanguage,
		VersionId $toVersionId,
		Language $toLanguage
	): void {
	}

	public function read(VersionId $versionId, Language $language): array
	{
	}

	public function touch(VersionId $versionId, Language $language): void
	{
	}

	public function update(VersionId $versionId, Language $language, array $fields): void
	{
	}
}
