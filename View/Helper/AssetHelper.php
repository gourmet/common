<?php
/**
 * AssetHelper
 *
 * PHP 5
 *
 * Copyright 2013, Jad Bitar (http://jadb.io)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2013, Jad Bitar (http://jadb.io)
 * @link          http://github.com/gourmet/common
 * @since         0.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CommonAppHelper', 'Common.View/Helper');

/**
 * Asset helper
 *
 * Helper that acts as a wrapper to the `AssetCompressHelper` and `HtmlHelper`. When
 * the `AssetCompress` plugin and the `AssetCompressHelper` is loaded for the current view,
 * use the `asset_compress.ini` defined builds when possible or fallback to the `HtmlHelper`
 * default asset inclusion.
 *
 * It also allows other helpers to not include the same asset or JS block more than once.
 *
 * @package       Common.Helper
 */
class AssetHelper extends CommonAppHelper {

/**
 * Associative array of paths and args to be passed to the wrapped method (`rel` and `options`).
 *
 * @var array
 */
	public $css = array();

/**
 * Associative array of paths and options to be passed to the wrapped method.
 *
 * @var array
 */
	public $js = array();

/**
 * Associative array of unique keys to identify every block (default to the block's md5 hash) and
 * options to be passed to the `HtmlHelper::scriptBlock()`.
 *
 * @var array
 */
	public $scriptBlock = array();

/**
 * {@inheritdoc}
 */
	public function afterRender($viewFile) {
		$this->render();
	}

/**
 * [css description]
 * @param [type] $path [description]
 * @param [type] $rel [description]
 * @param array $options [description]
 * @return [type]
 */
	public function css($path, $rel = null, $options = array()) {
		$options = array_merge(array('block' => 'css'), $options);
		$this->_append('css', $path, $options, $rel);
	}

/**
 * [render description]
 * @param string $type [description]
 * @return [type]
 */
	public function render($type = 'all') {
		if ('all' != $type) {
			return $this->_render($type);
		}

		$out = '';
		foreach (array('js', 'scriptBlock', 'css') as $type) {
			$out .= $this->_render($type);
		}

		return $out;
	}

/**
 * [script description]
 * @param [type] $path [description]
 * @param array $options [description]
 * @return [type]
 */
	public function script($path, $options = array()) {
		$options = array_merge(array('block' => 'script'), $options);
		$this->_append('js', $path, $options);
	}

/**
 * [scriptBlock description]
 * @param [type] $script [description]
 * @param array $options [description]
 * @return [type]
 */
	public function scriptBlock($script, $options = array()) {
		$options = array_merge(array('block' => 'script', 'key' => null), $options);

		$key = $options['key'];
		unset($options['key']);
		if (!$key) {
			$key = md5($script);
		}

		$this->scriptBlock[$key] = compact('script', 'options');
	}

/**
 * Adds an extension if the file doesn't already end with it.
 *
 * @param string $file Filename
 * @param string $ext Extension with .
 * @return string
 */
	protected function _addExt($file, $ext) {
		$ext = '.' . $ext;
		if (substr($file, strlen($ext) * -1) !== $ext) {
			$file .= $ext;
		}
		return $file;
	}

/**
 * [append description]
 * @param [type] $type [description]
 * @param [type] $path [description]
 * @param [type] $options [description]
 * @param [type] $rel [description]
 * @return [type]
 */
	protected function _append($type, $path, $options, $rel = null) {
		foreach ((array) $path as $i) {
			$this->{$type}[$i] = compact('options', 'rel');
		}
	}

/**
 * [_render description]
 * @param [type] $type [description]
 * @return [type]
 */
	protected function _render($type) {
		// @todo Replace the `AssetCompress` check by a `Reveal` rule.
		if (in_array($type, array('js', 'css')) && CakePlugin::loaded('AssetCompress') && isset($this->_View->AssetCompress)) {
			foreach ($this->{$type} as $path => $args) {
				$targets = $this->_View->AssetCompress->config()->targets($type);
				$exists = false;
				foreach ($targets as $target) {
					$files = $this->_View->AssetCompress->config()->files($target);
					if (in_array($this->_addExt($path, $type), $files) || in_array($path, $files)) {
						$exists = true;
						break;
					}
				}
				$args = array_merge(Hash::normalize(array('path', 'rel', 'options')), compact('path') + $args);

				if (!$exists) {
					if ('js' == $type) {
						$this->_View->Html->script($path, (array) $args['options']);
					} else {
						$this->_View->Html->css($path, $args['rel'], (array) $args['options']);
					}
					continue;
				}

				$haystack = $this->_View->fetch($args['options']['block']);
				$target = str_replace(array('.css', '.js'), array('', ''), $target);
				$needle = DS . 'assets' . DS . $target;
				if (strpos($haystack, $needle) === false) {
					$method = $type;
					if ('js' == $method) {
						$method = 'script';
					}
					$this->_View->AssetCompress->{$method}($target, (array) $args['options']);
				}
			}
			return;
		}

		foreach ($this->{$type} as $path => $args) {
			extract($args);

			switch ($type) {

				case 'css':
					if (!isset($haystack)) {
						$haystack = $this->_View->fetch('css');
					}

					$needle = $this->_View->Html->css($path, $rel, $options);
					if (strpos($haystack, $needle) === false) {
						$this->_View->prepend('css', $needle);
					}
				break;

				case 'js':
					$this->_View->Html->script($path, $options);
				break;

				case 'scriptBlock':
					$this->_View->Html->scriptBlock($script, $options);
				break;

				default:
			}

		}
	}

}
