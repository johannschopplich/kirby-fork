<?php

namespace Kirby\Panel\Ui\Button;

use Kirby\Cms\ModelWithContent;
use Kirby\Content\VersionId;
use Kirby\Http\Uri;
use Kirby\Toolkit\I18n;

/**
 * Versions view button for models
 *
 * @package   Kirby Panel
 * @author    Nico Hoffmann <nico@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 * @since     5.0.0
 * @unstable
 */
class VersionsButton extends ViewButton
{
	public function __construct(
		ModelWithContent $model,
		public VersionId|string $versionId = 'latest'
	) {
		parent::__construct(
			model: $model,
			class: 'k-versions-view-button',
			icon: $this->icon(),
			text: I18n::translate('version.' . $this->versionId()),
		);
	}

	public function icon(): string
	{
		return $this->versionId === 'compare' ? 'layout-columns' : 'git-branch';
	}

	public function isCurrent(string $versionId): bool
	{
		return $this->versionId() === $versionId;
	}

	public function options(): array
	{
		return [
			[
				'label'   => $this->i18n('version.latest'),
				'icon'    => 'git-branch',
				'link'    => $this->url('latest'),
				'current' => $this->isCurrent('latest')
			],
			[
				'label'   => $this->i18n('version.changes'),
				'icon'    => 'git-branch',
				'link'    => $this->url('changes'),
				'current' => $this->isCurrent('changes')
			],
			'-',
			[
				'label'   => $this->i18n('version.compare'),
				'icon'    => 'layout-columns',
				'link'    => $this->url('compare'),
				'current' => $this->isCurrent('compare')
			],

		];
	}

	public function props(): array
	{
		return [
			...parent::props(),
			'options' => $this->options()
		];
	}

	protected function url(string $versionId): string
	{
		$url = $this->model->panel()->url(true) . '/preview/' . $versionId;
		$url = new Uri($url);

		return $url->toString();
	}

	public function versionId(): string
	{
		return match ($this->versionId) {
			'compare' => 'compare',
			default   => VersionId::from($this->versionId)->value()
		};
	}
}
