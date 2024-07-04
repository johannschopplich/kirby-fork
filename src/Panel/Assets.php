<?php

namespace Kirby\Panel;

use Kirby\Cms\App;
use Kirby\Cms\Url;
use Kirby\Exception\Exception;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\Asset;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;

/**
 * The Assets class collects all js, css, icons and other
 * files for the Panel. It pushes them into the media folder
 * on demand and also makes sure to create proper asset URLs
 * depending on dev mode
 *
 * @package   Kirby Panel
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 * @since     4.0.0
 */
class Assets
{
	protected bool $isDev;
	protected bool $isPluginDev;
	protected App $kirby;
	protected string $nonce;
	protected Plugins $plugins;
	protected string $url;

	public function __construct()
	{
		$this->kirby   = App::instance();
		$this->nonce   = $this->kirby->nonce();
		$this->plugins = new Plugins();

		// check if Panel is running in dev mode to
		// get the assets from the Vite dev server;
		// dev mode = explicitly enabled in the config AND Vite is running
		$this->isDev =
			$this->kirby->option('panel.dev', false) !== false &&
			is_file($this->kirby->root('panel') . '/.vite-running') === true;

		// check if any plugin is running in dev mode to
		// load the non-production version of Vue
		$this->isPluginDev =
			is_file($this->kirby->root('plugins'). '/.vite-running') === true;

		// get the base URL
		$this->url = $this->url();
	}

	/**
	 * Get all CSS files
	 */
	public function css(): array
	{
		$css = [
			'index'   => $this->url . '/css/style.min.css',
			'plugins' => $this->plugins->url('css'),
			...$this->custom('panel.css')
		];

		// during dev mode we do not need to load
		// the general stylesheet (as styling will be inlined)
		if ($this->isDev === true) {
			$css['index'] = null;
		}

		return array_filter($css);
	}

	/**
	 * Check for a custom asset file from the
	 * config (e.g. panel.css or panel.js)
	 */
	public function custom(string $option): array
	{
		$customs = [];

		if ($assets = $this->kirby->option($option)) {
			$assets  = A::wrap($assets);

			foreach ($assets as $index => $path) {
				if (Url::isAbsolute($path) === true) {
					$customs['custom-' . $index] = $path;
					continue;
				}

				$asset = new Asset($path);

				if ($asset->exists() === true) {
					$customs['custom-' . $index] =  $asset->url() . '?' . $asset->modified();
				}
			}
		}

		return $customs;
	}

	/**
	 * Generates an array with all assets
	 * that need to be loaded for the panel (js, css, icons)
	 */
	public function external(): array
	{
		return [
			'css'         => $this->css(),
			'icons'       => $this->favicons(),
			'import-maps' => $this->importMaps(),
			'js'          => $this->js()
		];
	}

	/**
	 * Returns array of favicon icons
	 * based on config option
	 *
	 * @todo Deprecate `url` option in v5, use `href` option instead
	 * @todo Deprecate `rel` usage as array key in v5, use `rel` option instead
	 *
	 * @throws \Kirby\Exception\InvalidArgumentException
	 */
	public function favicons(): array
	{
		$icons = $this->kirby->option('panel.favicon', [
			[
				'rel'   => 'apple-touch-icon',
				'type'  => 'image/png',
				'href'  => $this->url . '/apple-touch-icon.png'
			],
			[
				'rel'   => 'alternate icon',
				'type'  => 'image/png',
				'href'  => $this->url . '/favicon.png'
			],
			[
				'rel'   => 'shortcut icon',
				'type'  => 'image/svg+xml',
				'href'  => $this->url . '/favicon.svg'
			],
			[
				'rel'   => 'apple-touch-icon',
				'type'  => 'image/png',
				'href'  => $this->url . '/apple-touch-icon-dark.png',
				'media' => '(prefers-color-scheme: dark)'
			],
			[
				'rel'   => 'alternate icon',
				'type'  => 'image/png',
				'href'  => $this->url . '/favicon-dark.png',
				'media' => '(prefers-color-scheme: dark)'
			]
		]);

		if (is_array($icons) === true) {
			// normalize options
			foreach ($icons as $rel => &$icon) {
				// TODO: remove this backward compatibility check in v6
				if (isset($icon['url']) === true) {
					$icon['href'] = $icon['url'];
					unset($icon['url']);
				}

				// TODO: remove this backward compatibility check in v6
				if (is_string($rel) === true && isset($icon['rel']) === false) {
					$icon['rel'] = $rel;
				}

				$icon['href']  = Url::to($icon['href']);
				$icon['nonce'] = $this->nonce;
			}

			return array_values($icons);
		}

		// make sure to convert favicon string to array
		if (is_string($icons) === true) {
			return [
				[
					'rel'   => 'shortcut icon',
					'type'  => F::mime($icons),
					'href'  => Url::to($icons),
					'nonce' => $this->nonce
				]
			];
		}

		throw new InvalidArgumentException('Invalid panel.favicon option');
	}

	/**
	 * Load the SVG icon sprite
	 * This will be injected in the
	 * initial HTML document for the Panel
	 */
	public function icons(): string
	{
		$dir   = $this->kirby->root('panel') . '/';
		$dir  .= match ($this->isDev) {
			true  => 'public',
			false => 'dist'
		};
		$icons = F::read($dir . '/img/icons.svg');
		$icons = preg_replace('/<!--(.|\s)*?-->/', '', $icons);
		return $icons;
	}

	/**
	 * Get all import maps
	 */
	public function importMaps(): array
	{
		$map = [
			'vue' => match (true) {
				// during dev mode, load the dev version of Vue
				$this->isDev       => $this->url . '/node_modules/vue/dist/vue.esm-browser.js',
				// when any plugin is in dev mode, also load the dev version
				// of Vue  but from the dist folder, not node_modules
				$this->isPluginDev => $this->url . '/js/vue.esm-browser.js',
				// otherwise use the production version of Vue
				default            => $this->url . '/js/vue.esm-browser.prod.js'
			}
		];

		return array_filter($map);
	}

	/**
	 * Get all js files
	 */
	public function js(): array
	{
		$js = [
			'vendor' => [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/js/vendor.min.js'
			],
			'plugin-registry' => [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/js/plugins.js'
			],
			'plugins' => [
				'nonce' => $this->nonce,
				'src'   => $this->plugins->url('js'),
			],
			...A::map($this->custom('panel.js'), fn ($src) => [
				'nonce' => $this->nonce,
				'src'   => $src,
				'defer' => true
			]),
			'index' => [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/js/index.min.js',
				'defer' => true
			],
		];


		// during dev mode, add vite client and adapt
		// path to `index.js` - vendor does not need
		// to be loaded in dev mode
		if ($this->isDev === true) {
			// load the non-minified index.js, remove vendor script and
			// development version of Vue
			$js['vendor']['src'] = null;
			$js['index']['src']  = $this->url . '/src/index.js';

			// add vite dev client
			$js['vite'] = [
				'nonce' => $this->nonce,
				'src'   => $this->url . '/@vite/client'
			];
		}

		return array_filter($js, fn ($js) => empty($js['src']) === false);
	}

	/**
	 * Links all dist files in the media folder
	 * and returns the link to the requested asset
	 *
	 * @throws \Kirby\Exception\Exception If Panel assets could not be moved to the public directory
	 */
	public function link(): bool
	{
		$mediaRoot   = $this->kirby->root('media') . '/panel';
		$panelRoot   = $this->kirby->root('panel') . '/dist';
		$versionHash = $this->kirby->versionHash();
		$versionRoot = $mediaRoot . '/' . $versionHash;

		// check if the version already exists
		if (is_dir($versionRoot) === true) {
			return false;
		}

		// delete the panel folder and all previous versions
		Dir::remove($mediaRoot);

		// recreate the panel folder
		Dir::make($mediaRoot, true);

		// copy assets to the dist folder
		if (Dir::copy($panelRoot, $versionRoot) !== true) {
			throw new Exception('Panel assets could not be linked');
		}

		return true;
	}

	/**
	 * Get the base URL for all assets depending on dev mode
	 */
	public function url(): string
	{
		// vite is not running, use production assets
		if ($this->isDev === false) {
			return $this->kirby->url('media') . '/panel/' . $this->kirby->versionHash();
		}

		// explicitly configured base URL
		$dev = $this->kirby->option('panel.dev');

		if (is_string($dev) === true) {
			return $dev;
		}

		// port 3000 of the current Kirby request
		return rtrim($this->kirby->request()->url([
			'port'   => 3000,
			'path'   => null,
			'params' => null,
			'query'  => null
		])->toString(), '/');
	}
}
