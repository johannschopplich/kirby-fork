<?php

namespace Kirby\Panel\Controller\View;

use Kirby\Cms\App;
use Kirby\Cms\Find;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Exception\LogicException;
use Kirby\Exception\PermissionException;
use Kirby\Http\Uri;
use Kirby\Panel\Controller\ViewController;
use Kirby\Panel\Ui\Button\ViewButtons;
use Kirby\Panel\Ui\View;
use Kirby\Toolkit\A;

/**
 * Controls the preview view
 *
 * @package   Kirby Panel
 * @author    Nico Hoffmann <nico@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 * @since     6.0.0
 */
abstract class ModelPreviewViewController extends ViewController
{
	public function __construct(
		public Page|Site $model,
		public string $versionId
	) {
		parent::__construct();
	}

	public function buttons(): ViewButtons
	{
		return ViewButtons::view(view: $this->id(), model: $this->model)->defaults($this->model::CLASS_ALIAS . '.versions', 'languages')
			->bind(['versionId' => $this->versionId]);
	}

	public static function factory(string $path, string $versionId): static
	{
		// handle redirect if view was reloaded with a redirect URL
		// after navigating to a different page inside the preview browser
		if ($redirect = static::redirect($versionId)) {
			App::instance()->panel()->go($redirect);
		}

		return new static(
			model:     Find::parent($path),
			versionId: $versionId
		);
	}

	public function id(): string
	{
		return $this->model::CLASS_ALIAS . '.preview';
	}

	public function load(): View
	{
		$props = $this->props();

		if ($props['src']['latest'] === null) {
			throw new PermissionException('The preview is not available');
		}

		return new View(...$props);
	}

	public function props(): array
	{
		return [
			'component' => 'k-preview-view',
			'buttons'   => $this->buttons(),
			'src'       => $this->src(),
			'versionId' => $this->versionId,
		];
	}

	protected static function redirect(string $versionId): string|null
	{
		$kirby = App::instance();

		// Get redirect URL path
		if ($redirect = $kirby->request()->get('redirect')) {
			$redirect = new Uri($redirect);

			// Look up new model and redirect to its preview
			if ($result = $kirby->call($redirect->path, 'GET')) {

				if ($result instanceof ModelWithContent === false) {
					throw new LogicException(
						message: 'Cannot redirect the preview view to an URL that does not belong to any model'
					);
				}

				$url = $result->panel()->url() . '/preview/' . $versionId;
				$url = new Uri($url);

				// Preserve the redirect URL's query and params
				// and inject them into the new URL
				unset(
					$redirect->query()->_token,
					$redirect->query()->_version,
					$redirect->query()->_preview
				);

				if ($redirect->query->isNotEmpty() === true) {
					$url->query->_query = $redirect->query->toString();
				}

				if ($redirect->params->isNotEmpty() === true) {
					$url->query->_params = $redirect->params->toString();
				}

				return $url->toString();
			}
		}

		return null;
	}

	public function src(): array
	{
		$src = [
			'latest'  => $this->model->previewUrl('latest'),
			'changes' => $this->model->previewUrl('changes'),
		];

		return A::map(
			$src,
			function (string $url): string {
				$uri = new Uri($url);

				// set the preview flag
				$uri->query()->_preview = 'true';

				// inject params and query from a redirect
				$uri->params->merge($this->request->get('_params'));
				$uri->query->merge($this->request->get('_query'));

				return $uri->toString();
			}
		);
	}
}
