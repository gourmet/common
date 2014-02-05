<?php
/**
 * TableHelper
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

/**
 * Table helper
 *
 * Render HTML tables for passed `$data` with a given set of `$options`.
 *
 * - `$data` MUST be a numeric array. For example, result of `Model::find()` or paginated results.
 * - `$options` is optional
 *
 * @package       Common.Helper
 */
class TableHelper extends AppHelper {

/**
 * {@inheritdoc}
 */
	public $helpers = array('Html', 'Paginator');

/**
 * {@inheritdoc}
 */
	public $settings = array(
		'columns' => array(),
		'prepend' => '',
		'append' => '',
		'empty' => array(
			'message' => 'There are no items to display',
			'class' => 'alert-error',
			'element' => 'Common.alerts/default',
			'dismiss' => false
		),
		'table' => array('attrs' => array('cellpadding' => 0, 'cellspacing' => 0, 'border' => 0)),
		'tr' => array('attrs' => array(), 'even' => 'even', 'odd' => 'odd'),
		'td' => array('attrs' => array('align' => 'left')),
		'th' => array('attrs' => array('align' => 'left'), 'position' => 'top'),
		'paginate' => array(
			'attrs' => array(),
			'prev' => array(
				'title' => '« Previous',
				'options' => array(),
				'disabledTitle' => null,
				'disabledOptions' => array('class' => 'disabled'),
			),
			'next' => array(
				'title' => 'Next »',
				'options' => array(),
				'disabledTitle' => null,
				'disabledOptions' => array('class' => 'disabled'),
			),
			'numbers' => array(
				'before' => '&nbsp; ',
				'after' => ' &nbsp;',
				'modulus' => 10,
				'separator' => ' &nbsp; ',
				'tag' => 'span',
				'first' => 'First ',
				'last' => ' Last',
				'ellipsis' => '...'
			),
			'position' => 'bottom',
			'trOptions' => array('class' => 'pagination'),
			'tdOptions' => array('align' => 'center'),
			'divOptions' => false
		)
	);

/**
 * Default column settings.
 *
 * @var array
 */
	private $__colDefaults = array(
		'sort' => false,
		'value' => ''
	);

/**
 * {@inheritdoc}
 */
	public function __construct(View $View, $settings = array()) {
		// Translate defaults.
		$this->settings = Hash::merge($this->settings, array(
			'empty' => array('message' => __d('common', "There are no items to display")),
			'paginate' => array(
				'prev' => array('title' => __d('common', "« Previous")),
				'next' => array('title' => __d('common', "Next »")),
				'numbers' =>array(
					'first' => __d('common', "First "),
					'last' => __d('common', " Last")
				)
			)
		));

		// Set default TwitterBootstrap table classes for odd/even striping.
		if (CakePlugin::loaded('TwitterBootstrap') || CakePlugin::loaded('BoostCake')) {
			$this->settings = Hash::merge($this->settings, array(
				'table' => array(
					'attrs' => array(
						'class' => 'table table-striped'
					)
				),
				'paginate' => array(
					'prev' => array(
						'options' => array('class' => 'previous'),
						'disabledOptions' => array('class' => 'previous disabled')
					),
					'numbers' => array(
						'tag' => 'li',
						'before' => '',
						'after' => '',
						'separator' => "\n\t\t\t\t"
					),
					'trOptions' => array(
						'class' => 'paginate',
					)
				)
			));
		}

		parent::__construct($View, $settings);
	}

/**
 * Render table.
 *
 * @param array $data Dataset MUST be a numeric array. For example, result of `Model::find()`
 *  or paginated results.
 * @param array $options Optional. Table rendering options to overwrite default `TableHelper::$settings`.
 * @return string Table's HTML.
 */
	public function render($data, $options = array()) {
		$options = Hash::merge($this->settings, $options);
		$options['columns'] = (array) $options['columns'];

		// No columns defined.
		if (empty($options['columns'])) {
			throw new RuntimeException(__d('common', "Missing the data-grid's columns definition"));
		}

		// No records passed.
		if (empty($data)) {
			$element = $options['empty']['element'];
			unset($options['empty']['element']);
			return $options['prepend'] . "\n"
				. $this->_View->element($element, $options['empty'])
				. $options['append'];
		}

		$rowCount = 0;

		// Check if headers and/or pagination should appear on top and/or bottom.
		$bottom = array('bottom', 'both');
		$top = array('top', 'both');
		foreach (array('top', 'bottom') as $pos) {
			foreach (array('th', 'paginate') as $el) {
				$print[$pos][$el] = (false !== $options[$el] && in_array($options[$el]['position'], $$pos));
			}
		}

		if (!empty($options['prepend'])) {
			$out[] = $options['prepend'];
		}

		if (false !== $options['paginate']['divOptions'] && $print['top']['paginate']) {
			$out[] = $this->_renderPagination($options);
		}

		// Start table.
		$out[] = sprintf('<table%s>', $this->_parseAttributes($options['table']['attrs']));

		// Display pagination on top.
		if (false === $options['paginate']['divOptions'] && $print['top']['paginate']) {
			$out[] = $this->_renderPagination($options);
		}

		// Display headers on top.
		if ($print['top']['th']) {
			$out[] = $this->_renderHeaders($options);
		}

		$out[] = "\t<tbody>";

		// Loop through resultset.
		foreach ($data as $i => $row) {
			$td = array();
			// Loop through columns.
			foreach ($options['columns'] as $name => $tdOptions) {
				$tdOptions = array_merge(
					$this->__colDefaults,
					array('td' => $options['td']['attrs']),
					$tdOptions
				);

				$td[] = $this->Html->useTag(
					'tablecell',
					$this->_parseAttributes($tdOptions['td']),
					$this->_renderCell($tdOptions['value'], $row)
				);
			}

			// Create result's row.
			$trOptions = array('attrs' => array_merge(array('class' => ''), $options['tr']['attrs']));
			$trOptions['attrs']['class'] = trim(implode(' ', array($trOptions['attrs']['class'], $options['tr'][$rowCount % 2 ? 'even' : 'odd'])));

			$tr[] = $this->Html->useTag(
				'tablerow',
				$this->_parseAttributes($trOptions['attrs']),
				"\n\t\t\t" . implode("\n\t\t\t", $td) . "\n\t\t"
			);

			$rowCount++;
		}

		$out[] = "\t\t" . implode("\n\t\t", $tr);
		$out[] = "\t</tbody>";

		// Display headers on bottom.
		if ($print['bottom']['th']) {
			$out[] = $this->_renderHeaders($options, 'tfoot');
		}

		// Display pagination on bottom.
		if (false === $options['paginate']['divOptions'] && $print['bottom']['paginate']) {
			$out[] = $this->_renderPagination($options);
		}

		$out[] = '</table>';

		if (false !== $options['paginate']['divOptions'] && $print['bottom']['paginate']) {
			$out[] = $this->_renderPagination($options);
		}

		if (!empty($options['append'])) {
			$out[] = $options['append'];
		}

		// Table with prepended and/or appended stuff.
		return implode("\n", $out);
	}

/**
 * Extract attributes from string.
 *
 * @param string $str String to parse.
 * @return array Attributes.
 */
	protected function _extractAttributes($str) {
		$str = preg_replace("/[\x{00a0}\x{200b}]+/u", ' ', $str);
		$attributes = array();
		$patterns = array(
			'name-value-quotes-dble' => '(\w+)\s*=\s*"([^"]*)"',
			'name-value-quotes-sgle' => "(\w+)\s*=\s*'([^']*)'",
			'name-value-quotes-none' => '(\w+)\s*=\s*([^\s\'"]+)',
			'name-quotes-dble' => '"([^"]*)"',
			'name-quotes-sgle' => "'([^']*)'",
			'name-quotes-none' => '(\w+)'
		);
		$pattern = '/' . implode('(?:\s|$)|', $patterns) . '(?:\s|$)/';

		if (!preg_match_all($pattern, $str, $matches, PREG_SET_ORDER)) {
			return $attributes;
		}

		foreach ($matches as $match) {
			if (!empty($match[1])) {
				$attributes[strtolower($match[1])] = stripcslashes($match[2]);
			} else if (!empty($match[3])) {
				$attributes[$match[3]] = stripcslashes($match[4]);
			} else if (!empty($match[5])) {
				$attributes[$match[5]] = stripcslashes($match[6]);
			} else if (!empty($match[7]) && strlen($match[7])) {
				$attributes[] = stripcslashes($match[7]);
			} else if (!empty($match[8])) {
				$attributes[] = stripcslashes($match[8]);
			} else if (!empty($match[9])) {
				$attributes[] = stripcslashes($match[9]);
			}
		}

		return $attributes;
	}

/**
 * Render table's cell.
 *
 * @param string $str Cell's content.
 * @param array $data Cell's row data.
 * @return Table cell's HTML.
 */
	protected function _renderCell($str, $data) {
		// The order is VERY important.
		$str = $this->_replaceData($str, $data);
		$str = $this->_replaceEval($str);
		$str = $this->_replaceData($str, $data);
		$str = $this->_replaceCurrency($str);
		$str = $this->_replaceTime($str);
		$str = $this->_replaceTranslate($str);
		$str = $this->_replaceUrl($str);
		$str = $this->_replaceImage($str);
		$str = $this->_replaceLink($str);
		$str = $this->_replaceTruncate($str);
		return $str;
	}

/**
 * Render table's headers row.
 *
 * @param array $options Table's options.
 * @param string $tag Optional. Tag to group header's content. Options: 'thead' or 'tfoot'.
 * @return string Table's header or footer HTML.
 */
	protected function _renderHeaders($options, $tag = 'thead') {
		$th = array();
		foreach ($options['columns'] as $name => $thOptions) {
			$thOptions = Hash::merge(
				$this->__colDefaults,
				array('th' => $options['th']['attrs']),
				$thOptions
			);

			if (false !== $options['paginate'] && false !== $thOptions['sort']) {
				if (is_bool($thOptions['sort'])) {
					$thOptions['sort'] = $name;
				}
				if (is_string($thOptions['sort'])) {
					$thOptions['sort'] = array('key' => $thOptions['sort']);
				}
				$thOptions['sort'] = array_merge(array('options' => array()), $thOptions['sort']);
				$name = $this->Paginator->sort($thOptions['sort']['key'], $name, $thOptions['sort']['options']);
			}

			$th[] = $this->Html->useTag(
				'tableheader',
				$this->_parseAttributes($thOptions['th']),
				$name
			);
		}

		return sprintf(
			"\t<%s>\n\t\t%s\n\t</%s>",
			$tag,
			$this->Html->useTag('tablerow', null, "\n\t\t\t" . implode("\n\t\t\t", $th) . "\n\t\t"),
			$tag
		);
	}

/**
 * Render table's pagination row.
 *
 * @param array $options Table's options.
 * @return string Table's pagination HTML.
 */
	public function _renderPagination($options) {
		if ($this->Paginator->request->params['paging'][$this->Paginator->defaultModel()]['pageCount'] < 2) {
			return '';
		}

		if (CakePlugin::loaded('TwitterBootstrap')) {
		}

		$out = array();

		$paginate = $options['paginate'];
		$prev = $paginate['prev'];
		$next = $paginate['next'];

		$this->Paginator->options($paginate['attrs']);

		$after = false !== $paginate['divOptions'] ? "\n\t" : "\n\t\t\t";
		$glue = false !== $paginate['divOptions'] ? "\n\t" : "\n\t\t\t";
		$before = '';
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$glue .= "\t";
			$before .= "\n";
			$paginate['numbers']['separator'] = $after . "\t";
			// Hack for the `BootstrapPaginatorHelper::prev()` and `BootstrapPaginatorHelper::next()` to apply the correct `class`.
			$prev['options']['disabled'] = $prev['disabledOptions']['class'];
			$next['options']['disabled'] = $next['disabledOptions']['class'];
		}

		$out[] = $this->Paginator->prev($prev['title'], $prev['options'], $prev['disabledTitle'], $prev['disabledOptions']);
		$out[] = $this->Paginator->numbers($paginate['numbers']);
		$out[] = $this->Paginator->next($next['title'], $next['options'], $next['disabledTitle'], $next['disabledOptions']);

		$out = $glue . implode($glue, $out);
		if (CakePlugin::loaded('TwitterBootstrap') || CakePlugin::loaded('BoostCake')) {
			if (!isset($paginate['ulOptions'])) {
				$paginate['ulOptions'] = array('class' => 'pagination');
			}
			$out = $this->Html->tag('ul', $out . $after, $paginate['ulOptions']);
		}

		if (false !== $paginate['divOptions']) {
			return $this->Html->useTag('block', $this->_parseAttributes($paginate['divOptions']), $before . "\t" . $out . "\n");
		}

		return "\t" . $this->Html->useTag(
			'tablerow',
			$this->_parseAttributes($paginate['trOptions']),
			"\n\t\t" . $this->Html->useTag(
				'tablecell',
				$this->_parseAttributes(array_merge($paginate['tdOptions'], array('colspan' => count($options['columns'])))),
				$before . "\t\t\t" . $out . "\n\t\t"
			) . "\n\t"
		);
	}

/**
 * Replace by formatted currency string.
 *
 * Examples:
 *  - [currency]50[/currency]
 *  - [currency zero="$0.00"]0[/currency]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceCurrency($str) {
		if (!preg_match_all('/\[currency(.*?)\](.*)\[\/currency\]/i', $str, $matches)) {
			// Fallback regex for when no options are passed.
			if (!preg_match_all('/\[currency(.*?)\](.[^\[]*)\[\/currency\]/i', $str, $matches)) {
				return $str;
			}
		}

		foreach ($matches[0] as $i => $find) {
			$opts = $this->_extractAttributes(trim($matches[1][$i]));

			$currency = CakeNumber::defaultCurrency();
			if (isset($opts['currency'])) {
				$currency = $opts['currency'];
				unset($opts['currency']);
			}

			$replace = (empty($matches[2][$i]) || !is_numeric($matches[2][$i])) ? '' : CakeNumber::currency($matches[2][$i], $currency, $opts);
			$str = str_replace($find, $replace, $str);
		}

		return $str;
	}

/**
 * Replace by formatted time string.
 *
 * Examples:
 *  - [time]...[/time]
 *  - [time format="m-d-Y"]...[/time]
 *  - [time method="niceShort"]..[/time]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceTime($str) {
		if (!preg_match_all('/\[time(.*?)\](.*)\[\/time\]/i', $str, $matches)) {
			// Fallback regex for when no options are passed.
			if (!preg_match_all('/\[time(.*?)\](.[^\[]*)\[\/time\]/i', $str, $matches)) {
				return $str;
			}
		}

		foreach ($matches[0] as $i => $find) {
			$opts = $this->_extractAttributes(trim($matches[1][$i]));

			foreach (Hash::normalize(array('method' => 'nice', 'format', 'timezone')) as $k => $v) {
				${$k} = $v;
				if (isset($opts[$k])) {
					${$k} = $opts[$k];
					unset($opts[$k]);
				}
			}

			App::uses('CakeTime', 'Utility');

			$replace = (empty($matches[2][$i])) ? '' : CakeTime::$method($matches[2][$i], $timezone, $format);
			$str = str_replace($find, $replace, $str);
		}

		return $str;
	}

/**
 * Replace array paths by result of data's `Hash::extract()`.
 *
 * Examples:
 *  - [Article.title]
 *  - [Comments.{n}.author]
 *
 * @param string $str String to check and modify.
 * @param array $data Result row data.
 * @return string Modified string.
 */
	protected function _replaceData($str, $data) {
		// Supported tags.
		$tags = array(
			'[__]' => '[/__]', '[__d]' => '[/__d]', '[__c]' => '[/__c]', '[php]' => '[/php]', '[currency]' => '[/currency]',
			'[url]' => '[/url]', '[image]' => '[/image]', '[link]' => '[/link]', '[truncate]' => '[/truncate]', '[time]' => '[/time]'
		);

		// Find all array path (i.e. [Post.title], [Comment.{n}.text], [Roles.{n, }.name]) matches.
		if (!preg_match_all('/\[([A-Za-z0-9_{},\s\.]+)\]/iUs', $str, $matches)) {
			return $str;
		}

		foreach ($matches[0] as $i => $match) {
			// Skip when exact match of other tag.
			if (in_array($match, $tags) || isset($tags[$match])) {
				continue;
			}

			// Skip when matches tag with attributes (i.e. [truncate length=10]).
			$continue = false;
			foreach (array_keys($tags) as $needle) {
				if ($continue = strpos($match, substr($needle, 0, -1)) === 0) {
					break;
				}
			}
			if ($continue) {
				continue;
			}

			// Check if {f} or {l} in array path.
			// @todo Write tests.
			preg_match('/\{([fl])\}/iUs', $matches[1][$i], $el);

			if (!empty($el)) {
				$matches[1][$i] = str_replace('{' . $el[1] . '}', '{n}', $matches[1][$i]);

				if ('l' == $el[1]) {
					$replace = array_pop(Hash::extract($data, $matches[1][$i]));
				} else if ('f' == $el[1]) {
					$replace = array_shift(Hash::extract($data, $matches[1][$i]));
				}

				$str = str_replace($match, $replace, $str);
				continue;
			}

			// Check if {n} or {s} in array path.
			preg_match('/\{[ns](.+)\}/iUs', $matches[1][$i], $glue);

			// Set string to use in `implode()` extract results.
			if (empty($glue[1])) {
				$glue[1] = ' ';
			} else {
				$matches[1][$i] = str_replace($glue[0], substr($glue[0], 0, 2) . '}', $matches[1][$i]);
			}

			// Replace.
			$str = str_replace($match, implode($glue[1], Hash::extract($data, trim($matches[1][$i]))), $str);
		}

		return $str;
	}

/**
 * Replace PHP code by result of `TableHelper::__eval()`.
 *
 * Examples:
 *  - [php]return 'some_value';[/php]
 *  - [php]return [Invoice.total] * 2;[/php]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceEval($str) {
		// @see http://www.devnetwork.net/viewtopic.php?f=38&t=102670
		$regex ='{
\[php\]
  (                                        # capture parent [php] contents into $1
    (?:                                    # non-cap group for nesting * quantifier
      (?: (?!\[php[^\]]*\]|\[/php\]). )++  # possessively match all non-[php] tag chars
    |                                      # or
      \[php[^\]]*\](?1)\[/php\]            # recursively match nested [php]...[/php]
    )*                                     # loop however deep as necessary
  )                                        # end group 1 capture
\[/php\]                                   # match the parent [/php] closing tag
		}six';

		if (!preg_match_all($regex, $str, $matches)) {
			return $str;
		}

		foreach ($matches[0] as $i => $find) {
			$replace = '';
			if (!empty($matches[1][$i])) {
				if (preg_match_all($regex, $matches[1][$i], $matches2)) {
					$matches[1][$i] = $this->_replaceEval($matches[1][$i]);
				}
				ob_start();
				print eval('?><?php ' . $matches[1][$i]);
				$replace = ob_get_contents();
				ob_end_clean();
			}
			$str = str_replace($find, $replace, $str);
		}

		return $str;
	}

/**
 * Replace image tags by result of `HtmlHelper::image()`.
 *
 * Examples:
 *  - [image]/path/to/img.png[/image]
 *  - [image]/path/to/[User.gravatar].png[/image]
 *  - [image][url full]/path/to/[User.gravatar].png[/url][/image]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceImage($str) {
		// [image border=0 alt='some text']/path/to/image[/image]
		if (!preg_match_all('/\[image(.*?)\](.*)\[\/image\]/i', $str, $matches)) {
			return $str;
		}

		foreach ($matches[0] as $i => $find) {
			$opts = isset($matches[1][$i]) ? $this->_extractAttributes(trim($matches[1][$i])) : array();
			$replace = empty($matches[2][$i]) ? '' : $this->Html->image($matches[2][$i], $opts);
			$str = str_replace($find, $replace, $str);
		}

		return $str;
	}

/**
 * Replace link tags by result of `HtmlHelper::link()`.
 *
 * Examples:
 *  - [link]Title|http://example.com[/link]
 *  - [link]Website|[User.website][/link]
 *  - [link escape]See more >>|/articles/view/[Article.id][/link]
 *  - [link][php]return Router::url(array('controller' => 'Users', 'action' => 'edit', [User.id]))[/php][/link]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceLink($str) {
		if (!preg_match_all('/\[link(.*?)\](.[^\|]*)\|?(.*?)\[\/link\]/i', $str, $matches)) {

			// Find empty tag content (i.e. [link][/link]).
			if (!preg_match_all('/\[link(.*?)\]\[\/link\]/', $str, $matches)) {
				return $str;
			}

			return str_replace($matches[0][0], '', $str);
		}

		foreach ($matches[0] as $i => $find) {
			$opts = $this->_extractAttributes(trim($matches[1][$i]));
			$opts['escape'] = (isset($opts['escape']) && 'true' == $opts['escape']);
			if (in_array('escape', $opts)) {
					$opts = array_filter($opts, function() { return func_get_arg(0) != 'escape'; });
					$opts['escape'] = true;
			}

			// Set empty URL to null for `Html::link()` to behave as expected.
			if (empty($matches[3][$i])) {
				$matches[3][$i] = null;
			}

			// Delete '|' from URLs when no title defined.
			if (strpos($matches[2][$i], '|') === 0) {
				$matches[2][$i] = substr($matches[2][$i], 1);
			}

			$replace = empty($matches[2][$i]) ? '' : $this->Html->link(trim($matches[2][$i]), $matches[3][$i], $opts);
			$str = str_replace($find, $replace, $str);
		}

		return $str;
	}

/**
 * Replace translatable strings by their translated values.
 *
 * Examples:
 *  - [__]Translate this[/__]
 *  - [__d|domain]Translate this[/__d]
 *  - [__c|category]Translate this[/__c]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceTranslate($str) {
		// [__]Text to translate[/__]
		if (preg_match_all('/\[__\](.*?)\[\/__\]/i', $str, $matches)) {
			foreach ($matches[0] as $i => $find) {
				$str = str_replace($find, I18n::translate($matches[1][$i]), $str);
			}
		}

		// [__d|domain]Text to translate[/__d]
		if (preg_match_all('/\[__d\|(.+?)\](.*?)\[\/__d\]/i', $str, $matches)) {
			foreach ($matches[0] as $i => $find) {
				$str = str_replace($find, I18n::translate($matches[2][$i], null, $matches[1][$i]), $str);
			}
		}

		// [__c|category]Text to translate[/__c]
		if (preg_match_all('/\[__c\|(.+?)\](.*?)\[\/__c\]/i', $str, $matches)) {
			foreach ($matches[0] as $i => $find) {
				$str = str_replace($find, I18n::translate($matches[2][$i], null, null, $matches[1][$i]), $str);
			}
		}

		return $str;
	}

/**
 * Replace truncate tags by result of `String::truncate()`.
 *
 * Examples:
 *  - [truncate]A very long string[/truncate]
 *  - [truncate length=8][User.email][/truncate]
 *  - [truncate length=[php]return strlen([User.email]) - 4;[/php]][User.email][/truncate]
 *  - [truncate ellipsis="...."]Another string[/truncate]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceTruncate($str) {
		if (!preg_match_all('/\[truncate(.*?)\](.*)\[\/truncate\]/i', $str, $matches)) {
			// Fallback regex for when no options are passed.
			if (!preg_match_all('/\[truncate(.*?)\](.[^\[]*)\[\/truncate\]/i', $str, $matches)) {
				return $str;
			}
		}

		foreach ($matches[0] as $i => $find) {
			$opts = $this->_extractAttributes(trim($matches[1][$i]));

			$length = 100;
			if (isset($opts['length'])) {
				$length = $opts['length'];
				unset($opts['length']);
			}

			$replace = empty($matches[2][$i]) ? '' : String::truncate($matches[2][$i], $length, $opts);
			$str = str_replace($find, $replace, $str);
		}

		return $str;
	}

/**
 * Replace URL tags by result of `Helper::url()`.
 *
 * Examples:
 *  - [url]http://example.com[/url]
 *  - [url full]/blog[/url]
 *
 * @param string $str String to check and modify.
 * @return string Modified string.
 */
	protected function _replaceUrl($str) {
		if (!preg_match_all('/\[url(.*?)\](.*)\[\/url\]/', $str, $matches)) {
			return $str;
		}

		foreach ($matches[0] as $i => $find) {
			$opts = isset($matches[1][$i]) ? $this->_extractAttributes(trim($matches[1][$i])) : array();
			$full = (in_array('full', $opts) || (isset($opts['full']) && 'false' != $opts['full']));
			$replace = empty($matches[2][$i]) ? '' : $this->url(trim($matches[2][$i]), $full);
			$str = str_replace($find, $replace, $str);
		}

		return $str;
	}

}
