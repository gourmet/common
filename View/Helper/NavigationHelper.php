<?php
/**
 * NavigationHelper
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

App::uses('AppHelper', 'View/Helper');
App::uses('Navigation', 'Common.Lib');

/**
 * Navigation helper
 *
 * @package       Common.Helper
 */
class NavigationHelper extends AppHelper {

/**
 * {@inheritdoc}
 */
	public $helpers = array('Html');

/**
 * Current list's tag.
 *
 * @var string
 */
	protected $_tag = null;

/**
 * Lists tags and their respective items tags.
 *
 * @var array
 */
	protected $_tags = array(
		'dl' => 'di',
		'ol' => 'li',
		'ul' => 'li',
		'' => 'li',
		false => '',
	);

/**
 * Render navigation.
 *
 * @param string $path Optional. The navigation's item(s) `Hash` path.
 * @param array $navAttrs Optional. Navigation's HTML attributes.
 * @param array $listAttrs Optional. List's HTML attributes.
 * @return string
 */
	public function render($paths, $navAttrs = array(), $listAttrs = array()) {
		if (false !== $navAttrs) {
			$navAttrs = array_merge(array('tag' => 'nav'), $navAttrs);
			$navTag = $navAttrs['tag'];
			unset($navAttrs['tag']);
		}

		$listAttrs = array_merge(array('tag' => 'ul'), $listAttrs);
		$this->_tag = $listAttrs['tag'];
		unset($listAttrs['tag']);

		if (is_string($paths)) {
			$paths = array(array('path' => $paths));
		}

		$out = '';
		foreach ($paths as $k => $path) {
			if (is_string($path)) {
				$paths[$k] = compact('path');
			} else if (is_string($k)) {
				$attrs = (array) $path;
				$path = $k;
				if (!array_key_exists('navAttrs', $attrs) && !array_key_exists('listAttrs', $attrs)) {
					$attrs = array('navAttrs' => $attrs, 'listAttrs' => array());
				}
				$out .= $this->render($path, $attrs['navAttrs'], $attrs['listAttrs']);
				continue;
			}
			$paths[$k] = Hash::merge(array('attrs' => $listAttrs), $paths[$k]);
			$out .= $this->_render($paths[$k]);
		}

		if (empty($out)) {
			return;
		}

		if (false === $navAttrs) {
			return $out;
		}

		return $this->Html->useTag('tag', $navTag, $navAttrs, $out, $navTag);
	}

/**
 * [_render description]
 * @param [type] $path [description]
 * @param [type] $attrs [description]
 * @return [type]
 */
	protected function _render($nav) {
		extract($nav);
		$items = Navigation::order(Navigation::items($path));

		if (empty($items)) {
			return false;
		}

		$defaults = array($this->_tag => array(), $this->_tags[$this->_tag] => array(), 'a' => array());
		$list = array();

		foreach ($items as $key => $options) {
			$title = Inflector::humanize($key);
			extract($options);
			$htmlAttributes = Hash::merge($defaults, $htmlAttributes);

			Reveal::addRule('Page.current', array('self', '__isPageCurrent'), Router::normalize($url));
			if (Reveal::is('Page.current')) {
				$class = 'active';
				if (!empty($htmlAttributes[$this->_tags[$this->_tag]]['class'])) {
					$class = $htmlAttributes[$this->_tags[$this->_tag]]['class'] . ' ' . $class;
				}
				$htmlAttributes = Hash::merge($htmlAttributes, array($this->_tags[$this->_tag] => compact('class')));
			}

			if (method_exists($this->Html, 'icon') && !empty($icon) && count(explode(' ', $icon)) == 1) {
				$icon = $this->Html->icon($icon);
			}

			$title = $icon . $title;

			// Link-ify item's title when it does not match the current page.
			if (!empty($url)) {
				// Allow for link tag customization using `HtmlHelper::loadConfig()`.
				$configPath = dirname(__FILE__) . DS . 'Navigation' . DS;
				$reset = false;

				if (!empty($htmlAttributes['a']['tag'])) {
					$this->Html->loadConfig($htmlAttributes['a']['tag'], $configPath);
					$reset = true;
					unset($htmlAttributes['a']['tag']);
				}

				if (isset($url['prefix']) && false === $url['prefix'] && !empty($this->_View->request->prefix)) {
					$url[$this->_View->request->prefix] = false;
				}

				if (is_array($url) && !isset($url['plugin'])) {
					$url['plugin'] = null;
				}

				$title = $this->Html->link($title, $url, $htmlAttributes['a'], $confirmMessage);
				$reset && $this->Html->loadConfig('reset', $configPath);
			}

			if (!empty($children)) {
				// if (!)$title = $this->Html->tag('span', $title, array('class' => 'title'));
				$title .= $this->_render(array('path' => "$path.$key.children", 'attrs' => $htmlAttributes[$this->_tag]));
			}
			$list[] = $this->Html->tag($this->_tags[$this->_tag], $title, $htmlAttributes[$this->_tags[$this->_tag]]);
		}

		return $this->Html->tag($this->_tag, implode('', $list), $attrs);
	}


}
