<?php
/**
 * Default alert element.
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

foreach (array('level' => false, 'dismiss' => true, 'class' => array(), 'attributes' => array()) as $key => $value) {
	if (!isset($$key)) {
		$$key = $value;
	}
}

$class = (array) $class;

if (!empty($level)) {
	$class[] = 'alert-' . $level;
}

if (!empty($dismiss)) {
	$class[] = 'alert-dismissable';
}

if (isset($code) && !empty($code)) {
	$message = $this->Html->tag('strong', $code . ':') . $message;
}

$class[] = 'alert';

if (true === $dismiss) {
	$dismiss = '<a class="close" data-dismiss="alert" href="#">x</a>';
}

echo $this->Html->div(
	implode(' ', array_unique($class)),
	$dismiss . $message,
	$attributes
);
