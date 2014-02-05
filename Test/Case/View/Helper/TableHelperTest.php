<?php

App::uses('Controller', 'Controller');
App::uses('HtmlHelper', 'View/Helper');
App::uses('PaginatorHelper', 'View/Helper');
App::uses('TableHelper', 'Common.View/Helper');
App::uses('CommonTestCase', 'Common.TestSuite');

class TableHelperTestController extends Controller {

	public $name = 'Table';

	public $uses = null;

}

class TestTableHelper extends TableHelper {

	public function __call($method, $params) {
		$method = '_' . $method;
		$cntParams = count($params);

		if (2 == $cntParams) {
			return $this->{$method}($params[0], $params[1]);
		}
		return $this->{$method}($params[0]);
	}

}

class TableHelperTest extends CommonTestCase {

	public function setUp() {
		parent::setUp();

		if (empty($_GET['bootstrap'])) {
			$this->__resetPlugins = array();
			foreach (array('TwitterBootstrap', 'BoostCake') as $plugin) {
				if (CakePlugin::loaded($plugin)) {
					$resetPlugins[] = $plugin;
					CakePlugin::unload($plugin);
				}
			}
		}

		$this->View = $this->getMock('View', array('append'), array(new TableHelperTestController()));
		$this->Table = new TestTableHelper($this->View);
		$this->Table->Paginator = new PaginatorHelper($this->View);
		if (CakePlugin::loaded('TwitterBootstrap')) {
			App::uses('BootstrapPaginatorHelper', 'TwitterBootstrap.View/Helper');
			$this->Table->Paginator = new BootstrapPaginatorHelper($this->View);
		}
		$this->Table->Paginator->request = new CakeRequest(null, false);
		$this->Table->Paginator->request->addParams(array(
			'paging' => array(
				'Article' => array(
					'page' => 1,
					'current' => 2,
					'count' => 8,
					'prevPage' => false,
					'nextPage' => true,
					'pageCount' => 3,
					'order' => null,
					'limit' => 3,
					'options' => array(
						'page' => 1,
						'conditions' => array()
					),
					'paramType' => 'named'
				)
			)
		));
	}

	public function tearDown() {
		parent::tearDown();

		if (!empty($this->__resetPlugins)) {
			foreach ($this->__resetPlugins as $plugin) {
				CakePlugin::load($plugin);
			}
		}

		unset($this->View, $this->Table, $this->__resetPlugins);
	}

	public function options($type) {
		$options = array(
			'simple' => array(
				'columns' => array(
					'name' => array('th' => array('align' => 'center'), 'td' => array('align' => 'center'), 'value' => '[name]'),
					'value' => array('th' => array('style' => 'background-color:#000;'), 'value' => '[value]')
				),
				'th' => array('position' => 'both'),
				'paginate' => false
			),

			'users' => array(
				'prepend' => 'Comes before the table',
				'append' => 'Comes after the table',
				'columns' => array(
					'first name' => array(
						'value' => '[User.first_name]',
						'th' => array('align' => 'left'),
					),
					'last name' => array(
						'value' => '[User.last_name]',
						'th' => array('align' => 'right', 'style' => 'background-color:#000;'),
					),
					'email' => array(
						'value' => '<a href="mailto:[User.email]">[User.email]</a>'
					),
					'actions' => array(
						'value' => "[link]Edit|[php]return Router::url(array('controller' => 'users', 'action' => 'edit', [User.id]));[/php][/link]"
					)
				),
				'th' => array(
					'attrs' => array('align' => 'center')
				),
				'paginate' => false
			),

			'articles' => array(
				'columns' => array(
					'title' => array(
						'value' => '[Article.title]',
						'sort' => 'title',
					),
					'content' => array(
						'value' => '[truncate length=10][Article.content][/truncate]',
						'sort' => true
					),
				),
				'paginate' => array(
					'position' => 'both',
					'prev' => array('title' => 'Previous'),
					'next' => array('title' => 'Next'),
				)
			)
		);

		return Hash::merge($this->Table->settings, $options[$type]);
	}

	public function testExtractAttributes() {
		$result = $this->Table->extractAttributes(' style="color:#000;" class="foo bar" "dummy"');
		$expected = array('style' => 'color:#000;', 'class' => 'foo bar', 'dummy');
		$this->assertEqual($result, $expected);

		$result = $this->Table->extractAttributes(" style='color:#000;' class='foo bar' 'dummy' ");
		$expected = array('style' => 'color:#000;', 'class' => 'foo bar', 'dummy');
		$this->assertEqual($result, $expected);

		$result = $this->Table->extractAttributes(' style=color:#000; class=foo bar dummy ');
		$expected = array('style' => 'color:#000;', 'class' => 'foo', 'bar', 'dummy');
		$this->assertEqual($result, $expected);

		$result = $this->Table->extractAttributes(' style="color:#000;" class=\'foo bar\' dummy ');
		$expected = array('style' => 'color:#000;', 'class' => 'foo bar', 'dummy');
		$this->assertEqual($result, $expected);
	}

	public function testRenderWithoutBootstrap() {
		$data = array(
			array('name' => 'john', 'value' => 'doe'),
			array('name' => 'foo', 'value' => 'bar'),
		);
		$result = $this->Table->render($data, $this->options('simple'));
		$expected = array(
			array('table' => array('cellpadding' => 0, 'cellspacing' => 0, 'border' => 0)),
			array('thead' => true),
			array('tr' => true),
			array('th' => array('align' => 'center')), 'name', '/th',
			array('th' => array('align' => 'left', 'style' => 'background-color:#000;')), 'value', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/thead',
			array('tbody' => true),
			array('tr' => array('class' => 'odd')),
			array('td' => array('align' => 'center')), 'john', '/td',
			array('td' => array('align' => 'left')), 'doe', '/td',
			'preg:/\s*/', '/tr',
			array('tr' => array('class' => 'even')),
			array('td' => array('align' => 'center')), 'foo', '/td',
			array('td' => array('align' => 'left')), 'bar', '/td',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/tbody',
			array('tfoot' => true),
			array('tr' => true),
			array('th' => array('align' => 'center')), 'name', '/th',
			array('th' => array('align' => 'left', 'style' => 'background-color:#000;')), 'value', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/tfoot',
		);
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$expected[0]['table']['class'] = 'table table-striped';
		}
		$this->assertTags($result, $expected);

		$data = array(
			array('User' => array('id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@doe.com')),
			array('User' => array('id' => 2, 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@doe.com')),
			array('User' => array('id' => 3, 'first_name' => 'Foo', 'last_name' => 'Bar', 'email' => 'foo@bar.com')),
			array('User' => array('id' => 4, 'first_name' => 'Dummy', 'last_name' => 'User', 'email' => 'dummy@user.com')),
		);

		$result = $this->Table->render($data, $this->options('users'));
		$expected = array(
			'Comes before the table',
			array('table' => array('cellpadding' => 0, 'cellspacing' => 0, 'border' => 0)),
			array('thead' => true),
			array('tr' => true),
			array('th' => array('align' => 'left')), 'first name', '/th',
			array('th' => array('align' => 'right', 'style' => 'background-color:#000;')), 'last name', '/th',
			array('th' => array('align' => 'center')), 'email', '/th',
			array('th' => array('align' => 'center')), 'actions', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/thead',
			array('tbody' => true),
			array('tr' => array('class' => 'odd')),
			array('td' => array('align' => 'left')), 'John', '/td',
			array('td' => array('align' => 'left')), 'Doe', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => 'mailto:john@doe.com')), 'john@doe.com', '/a', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => '/users/edit/1')), 'Edit', '/a', '/td',
			'preg:/\s*/', '/tr',
			array('tr' => array('class' => 'even')),
			array('td' => array('align' => 'left')), 'Jane', '/td',
			array('td' => array('align' => 'left')), 'Doe', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => 'mailto:jane@doe.com')), 'jane@doe.com', '/a', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => '/users/edit/2')), 'Edit', '/a', '/td',
			'preg:/\s*/', '/tr',
			array('tr' => array('class' => 'odd')),
			array('td' => array('align' => 'left')), 'Foo', '/td',
			array('td' => array('align' => 'left')), 'Bar', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => 'mailto:foo@bar.com')), 'foo@bar.com', '/a', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => '/users/edit/3')), 'Edit', '/a', '/td',
			'preg:/\s*/', '/tr',
			array('tr' => array('class' => 'even')),
			array('td' => array('align' => 'left')), 'Dummy', '/td',
			array('td' => array('align' => 'left')), 'User', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => 'mailto:dummy@user.com')), 'dummy@user.com', '/a', '/td',
			array('td' => array('align' => 'left')), array('a' => array('href' => '/users/edit/4')), 'Edit', '/a', '/td',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/tbody',
			'preg:/\s*/', '/table',
			'Comes after the table'
		);
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$expected[1]['table']['class'] = 'table table-striped';
		}
		$this->assertTags($result, $expected);

		$data = array(
			array('Article' => array('title' => 'foo', 'content' => 'bar')),
			array('Article' => array('title' => 'dummy', 'content' => 'content'))
		);

		$result = $this->Table->render($data, $this->options('articles'));
		$expected = array(
			array('table' => array('cellpadding' => 0, 'cellspacing' => 0, 'border' => 0)),
			array('tr' => array('class' => 'pagination')),
			array('td' => array('align' => 'center', 'colspan' => 2)),
			array('span' => array('class' => 'disabled')), 'Previous', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'current')), 1, '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'next')), array('a' => array('href' => '/index/page:2', 'rel' => 'next')), 'Next', '/a', '/span',
			'preg:/\s*/', '/td',
			'preg:/\s*/', '/tr',
			array('thead' => true),
			array('tr' => true),
			array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:title/direction:asc')), 'title', '/a', '/th',
			array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:content/direction:asc')), 'content', '/a', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/thead',
			array('tbody' => true),
			array('tr' => array('class' => 'odd')),
			array('td' => array('align' => 'left')), 'foo', '/td',
			array('td' => array('align' => 'left')), 'bar', '/td',
			'preg:/\s*/', '/tr',
			array('tr' => array('class' => 'even')),
			array('td' => array('align' => 'left')), 'dummy', '/td',
			array('td' => array('align' => 'left')), 'content', '/td',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/tbody',
			array('tr' => array('class' => 'pagination')),
			array('td' => array('align' => 'center', 'colspan' => 2)),
			array('span' => array('class' => 'disabled')), 'Previous', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'current')), 1, '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'next')), array('a' => array('href' => '/index/page:2', 'rel' => 'next')), 'Next', '/a', '/span',
			'preg:/\s*/', '/td',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/table',
		);
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$expected = array(
				array('table' => array('cellpadding' => 0, 'cellspacing' => 0, 'border' => 0, 'class' => 'table table-striped')),
				array('tr' => array('class' => 'pagination')),
				array('td' => array('align' => 'center', 'colspan' => 2)),
				array('ul' => true),
				array('li' => array('class' => 'previous disabled')), array('a' => array('href' => '/index/page:1')), 'Previous', '/a', '/li',
				'preg:/\s*/', array('li' => array('class' => 'current disabled')), array('a' => array('href' => '#')), 1, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2', 'rel'=> 'next')), 'Next', '/a', '/li',
				'preg:/\s*/', '/ul',
				'preg:/\s*/', '/td',
				'preg:/\s*/', '/tr',
				array('thead' => true),
				array('tr' => true),
				array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:title/direction:asc')), 'title', '/a', '/th',
				array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:content/direction:asc')), 'content', '/a', '/th',
				'preg:/\s*/', '/tr',
				'preg:/\s*/', '/thead',
				array('tbody' => true),
				array('tr' => array('class' => 'odd')),
				array('td' => array('align' => 'left')), 'foo', '/td',
				array('td' => array('align' => 'left')), 'bar', '/td',
				'preg:/\s*/', '/tr',
				array('tr' => array('class' => 'even')),
				array('td' => array('align' => 'left')), 'dummy', '/td',
				array('td' => array('align' => 'left')), 'content', '/td',
				'preg:/\s*/', '/tr',
				'preg:/\s*/', '/tbody',
				array('tr' => array('class' => 'pagination')),
				array('td' => array('align' => 'center', 'colspan' => 2)),
				array('ul' => true),
				array('li' => array('class' => 'previous disabled')), array('a' => array('href' => '/index/page:1')), 'Previous', '/a', '/li',
				'preg:/\s*/', array('li' => array('class' => 'current disabled')), array('a' => array('href' => '#')), 1, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2', 'rel'=> 'next')), 'Next', '/a', '/li',
				'preg:/\s*/', '/ul',
				'preg:/\s*/', '/td',
				'preg:/\s*/', '/tr',
				'preg:/\s*/', '/table',
			);
		}
		$this->assertTags($result, $expected);

		$result = $this->Table->render($data, Hash::merge($this->options('articles'), array('paginate' => array('divOptions' => array()))));
		$expected = array(
			array('div' => true),
			array('span' => array('class' => 'disabled')), 'Previous', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'current')), 1, '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'next')), array('a' => array('href' => '/index/page:2', 'rel' => 'next')), 'Next', '/a', '/span',
			'preg:/\s*/', '/div',
			array('table' => array('cellpadding' => 0, 'cellspacing' => 0, 'border' => 0)),
		);
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$expected = array(
				array('div' => true),
				array('ul' => true),
				array('li' => array('class' => 'previous disabled')), array('a' => array('href' => '/index/page:1')), 'Previous', '/a', '/li',
				'preg:/\s*/', array('li' => array('class' => 'current disabled')), array('a' => array('href' => '#')), 1, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2', 'rel'=> 'next')), 'Next', '/a', '/li',
				'preg:/\s*/', '/ul',
				'preg:/\s*/', '/div',
				array('table' => array('cellpadding' => 0, 'cellspacing' => 0, 'border' => 0, 'class' => 'table table-striped')),
			);
		}
		$expected = array_merge($expected, array(
			array('thead' => true),
			array('tr' => true),
			array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:title/direction:asc')), 'title', '/a', '/th',
			array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:content/direction:asc')), 'content', '/a', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/thead',
			array('tbody' => true),
			array('tr' => array('class' => 'odd')),
			array('td' => array('align' => 'left')), 'foo', '/td',
			array('td' => array('align' => 'left')), 'bar', '/td',
			'preg:/\s*/', '/tr',
			array('tr' => array('class' => 'even')),
			array('td' => array('align' => 'left')), 'dummy', '/td',
			array('td' => array('align' => 'left')), 'content', '/td',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/tbody',
			'/table',
		));
		if (!CakePlugin::loaded('TwitterBootstrap')) {
			$expected = array_merge($expected, array(
				array('div' => true),
				array('span' => array('class' => 'disabled')), 'Previous', '/span',
				'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'current')), 1, '/span',
				'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/span',
				'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/span',
				'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'next')), array('a' => array('href' => '/index/page:2', 'rel' => 'next')), 'Next', '/a', '/span',
				'preg:/\s*/', '/div',
			));
		} else {
			$expected = array_merge($expected, array(
				array('div' => true),
				array('ul' => true),
				array('li' => array('class' => 'previous disabled')), array('a' => array('href' => '/index/page:1')), 'Previous', '/a', '/li',
				'preg:/\s*/', array('li' => array('class' => 'current disabled')), array('a' => array('href' => '#')), 1, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2', 'rel'=> 'next')), 'Next', '/a', '/li',
				'preg:/\s*/', '/ul',
				'preg:/\s*/', '/div',
			));
		}
		$this->assertTags($result, $expected);

		$this->expectException('Exception');
		$result = $this->Table->render($data, $this->Table->settings);

		foreach ($resetPlugins as $plugin) {
			CakePlugin::load($plugin);
		}
	}

	public function testReplaceData() {
		$data = array(
			'User' => array('id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@doe.com'),
			'Comment' => array(
				array('id' => 1, 'text' => 'foo bar'),
				array('id' => 2, 'text' => 'FOO BAR'),
			)
		);

		$result = $this->Table->replaceData('[User.first_name]', $data);
		$expected = 'John';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceData('[Comment.{n}.text]', $data);
		$expected = 'foo bar FOO BAR';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceData('[Comment.{n, }.text]', $data);
		$expected = 'foo bar, FOO BAR';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceData('[Comment.{n, }.invalid]', $data);
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceData('[]', $data);
		$expected = '[]';
		$this->assertEqual($result, $expected);
	}

	public function testReplaceEval() {
		$result = $this->Table->replaceEval('[php]return "dummy";[/php]');
		$expected = 'dummy';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceEval('[php]return strlen("dummy");[/php]');
		$expected = '5';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceEval('[php]function foo($bar) { return $bar; } return foo("bar");[/php]');
		$expected = 'bar';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceEval('[php][/php]');
		$expected = '';
		$this->assertEqual($result, $expected);

	}

	public function testReplaceImage() {
		$result = $this->Table->replaceImage('[image]/path/to/img.png[/image]');
		$expected = $this->Table->Html->image('/path/to/img.png');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceImage('[image alt="dummy image"]/path/to/img.png[/image]');
		$expected = $this->Table->Html->image('/path/to/img.png', array('alt' => 'dummy image'));
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceImage('[image][/image]');
		$expected = '';
		$this->assertEqual($result, $expected);
	}

	public function testReplaceLink() {
		$result = $this->Table->replaceLink('[link]Title|http://example.com[/link]');
		$expected = $this->Table->Html->link('Title', 'http://example.com');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceLink('[link]http://example.com[/link]');
		$expected = $this->Table->Html->link('http://example.com');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceLink('[link]|http://example.com[/link]');
		$expected = $this->Table->Html->link('http://example.com');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceLink('[link escape]Title >>[/link]');
		$expected = $this->Table->Html->link('Title >>', null, array('escape' => true));
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceLink('[link escape=true]Title >>[/link]');
		$expected = $this->Table->Html->link('Title >>', null, array('escape' => true));
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceLink('[link escape=false]Title >>[/link]');
		$expected = $this->Table->Html->link('Title >>', null);
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceLink('[link][/link]');
		$expected = '';
		$this->assertEqual($result, $expected);
	}

	public function testReplaceTime() {
		$result = $this->Table->replaceTime('[time]2013-12-15 08:33:09[/time]');
		$expected = 'Sun, Dec 15th 2013, 08:33';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTime('[time method="niceShort"]2013-12-15 08:33:09[/time]');
		$expected = 'Dec 15th 2013, 08:33';
		$this->assertEqual($result, $expected);
	}

	public function testReplaceTranslate() {
		$result = $this->Table->replaceTranslate('[__]Translate this[/__]');
		$expected = __('Translate this');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTranslate('[__][/__]');
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTranslate('[__d|common]Translate this[/__d]');
		$expected = __d('common', 'Translate this');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTranslate('[__d|common][/__d]');
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTranslate('[link][__d|affiliates]View[/__d]|/admin/affiliates/affiliates/view/1[/link]|[link][__d|affiliates]Suspend[/__d]|/admin/affiliates/affiliates/suspend/1[/link]');
		$expected = '[link]' . __d('affiliates', "View") . '|/admin/affiliates/affiliates/view/1[/link]|[link]' . __d('affiliates', "Suspend") . '|/admin/affiliates/affiliates/suspend/1[/link]';
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTranslate('[__c|3]$5.00[/__c]');
		$expected = __c('$5.00', LC_MONETARY);
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTranslate('[__c|3][/__c]');
		$expected = '';
		$this->assertEqual($result, $expected);
	}

	public function testReplaceTruncate() {
		$text =
<<<TXT
Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
TXT
		;

		$result = $this->Table->replaceTruncate("[truncate]{$text}[/truncate]");
		$expected = String::truncate($text);
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTruncate("[truncate length=10]{$text}[/truncate]");
		$expected = String::truncate($text, 10);
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTruncate("[truncate length=20 ellipsis=.....]{$text}[/truncate]");
		$expected = String::truncate($text, 20, array('ellipsis' => '.....'));
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceTruncate("[truncate length=30 foo=bar]{$text}[/truncate]");
		$expected = String::truncate($text, 30);
		$this->assertEqual($result, $expected);
	}

	public function testReplaceUrl() {
		$result = $this->Table->replaceUrl('[url]/users/edit/1[/url]');
		$expected = $this->Table->Html->url('/users/edit/1');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceUrl('[url full]/users/edit/1[/url]');
		$expected = $this->Table->Html->url('/users/edit/1', true);
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceUrl('[url full=true]/users/edit/1[/url]');
		$expected = $this->Table->Html->url('/users/edit/1', true);
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceUrl('[url full=false]/users/edit/1[/url]');
		$expected = $this->Table->Html->url('/users/edit/1');
		$this->assertEqual($result, $expected);

		$result = $this->Table->replaceUrl('[url][/url]');
		$expected = '';
		$this->assertEqual($result, $expected);
	}

	public function testRenderCell() {
		$data = array(
			'User' => array('id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@doe.com', 'created' => date('Y-m-d H:i:s')),
			'Comment' => array(
				array('id' => 1, 'text' => 'foo bar'),
				array('id' => 2, 'text' => 'FOO BAR'),
			)
		);

		$result = $this->Table->renderCell('[time][User.created][/time]', $data);
		$expected = CakeTime::nice($data['User']['created']);
		$this->assertEqual($result, $expected);

		$result = $this->Table->renderCell('[truncate length=9 ellipsis=..][Comment.{n, }.text][/truncate]', $data);
		$expected = 'foo bar..';
		$this->assertEqual($result, $expected);

		$result = $this->Table->renderCell('[truncate length=9 ellipsis=..][Comment.{n}.invalid][/truncate]', $data);
		$expected = '';
		$this->assertEqual($result, $expected);

		$result = $this->Table->renderCell('[url]/users/email/[User.email][/url]', $data);
		$expected = $this->Table->url('/users/email/john@doe.com');
		$this->assertEqual($result, $expected);


		$result = $this->Table->renderCell('[image][url full]/users/email/[User.email][/url][/image]', $data);
		$expected = $this->Table->Html->image($this->Table->url('/users/email/john@doe.com', true));
		$this->assertEqual($result, $expected);

		$result = $this->Table->renderCell('[User.[php]return "first_name";[/php]]', $data);
		$expected = 'John';
		$this->assertEqual($result, $expected);

		$txt =
<<<TEXT
[php] if ("[User.id]" == "1") {
	echo '
<div class="btn-group pull-right">
  <button type="button" class="btn btn-xs btn-default"><a href="[php]return Router::url(array_merge(array('plugin' => null, 'controller' => 'properties'), array("action" => "view", "[User.id]")));[/php]">[__d|dashboard]Details[/__d]</a></button>
</div>
	';
}[/php]
TEXT
		;
		$expected =
<<<TEXT

<div class="btn-group pull-right">
  <button type="button" class="btn btn-xs btn-default"><a href="/properties/view/1">Details</a></button>
</div>
\t
TEXT
		;
		$result = $this->Table->renderCell($txt, $data);
		$this->assertEqual($result, $expected);

		$txt =
<<<TEXT
[php] if ("[User.id]" == "1") {
	echo '
<div class="btn-group pull-right">
  <button type="button" class="btn btn-xs btn-default"><a href="[php]return Router::url(array_merge(array('plugin' => null, 'controller' => 'properties'), array("action" => "view", "[User.id]")));[/php]">[__d|dashboard]Details[/__d]</a></button>
  <button type="button" class="btn btn-xs btn-default"><a href="[php]return Router::url(array_merge(array('plugin' => null, 'controller' => 'properties'), array("action" => "view", "[User.id]")));[/php]">[__d|dashboard]Details[/__d]</a></button>
</div>
	';
}[/php]
TEXT
		;
		$expected =
<<<TEXT

<div class="btn-group pull-right">
  <button type="button" class="btn btn-xs btn-default"><a href="/properties/view/1">Details</a></button>
  <button type="button" class="btn btn-xs btn-default"><a href="/properties/view/1">Details</a></button>
</div>
\t
TEXT
		;
		$result = $this->Table->renderCell($txt, $data);
		$this->assertEqual($result, $expected);
	}

	public function testRenderHeaders() {
		$result = $this->Table->renderHeaders($this->options('simple'));
		$expected = array(
			array('thead' => true),
			array('tr' => true),
			array('th' => array('align' => 'center')), 'name', '/th',
			array('th' => array('align' => 'left', 'style' => 'background-color:#000;')), 'value', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/thead'
		);
		$this->assertTags($result, $expected);

		$result = $this->Table->renderHeaders($this->options('users'));
		$expected = array(
			array('thead' => true),
			array('tr' => true),
			array('th' => array('align' => 'left')), 'first name', '/th',
			array('th' => array('align' => 'right', 'style' => 'background-color:#000;')), 'last name', '/th',
			array('th' => array('align' => 'center')), 'email', '/th',
			array('th' => array('align' => 'center')), 'actions', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/thead'
		);
		$this->assertTags($result, $expected);

		$result = $this->Table->renderHeaders($this->options('articles'));
		$expected = array(
			array('thead' => true),
			array('tr' => true),
			array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:title/direction:asc')), 'title', '/a', '/th',
			array('th' => array('align' => 'left')), array('a' => array('href' => '/index/page:1/sort:content/direction:asc')), 'content', '/a', '/th',
			'preg:/\s*/', '/tr',
			'preg:/\s*/', '/thead'
		);
		$this->assertTags($result, $expected);
	}

	public function testRenderPagination() {
		$result = $this->Table->renderPagination($this->options('articles'));
		$expected = array(
			array('tr' => array('class' => 'pagination')),
			array('td' => array('align' => 'center', 'colspan' => 2)),
			array('span' => array('class' => 'disabled')), 'Previous', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'current')), 1, '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'next')), array('a' => array('href' => '/index/page:2', 'rel' => 'next')), 'Next', '/a', '/span',
			'preg:/\s*/', '/td',
			'preg:/\s*/', '/tr'
		);
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$expected = array(
				array('tr' => array('class' => 'pagination')),
				array('td' => array('align' => 'center', 'colspan' => 2)),
				array('ul' => true),
				array('li' => array('class' => 'previous disabled')), array('a' => array('href' => '/index/page:1')), 'Previous', '/a', '/li',
				'preg:/\s*/', array('li' => array('class' => 'current disabled')), array('a' => array('href' => '#')), 1, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2', 'rel'=> 'next')), 'Next', '/a', '/li',
				'preg:/\s*/', '/ul',
				'preg:/\s*/', '/td',
				'preg:/\s*/', '/tr'
			);
		}
		$this->assertTags($result, $expected);

		$options = Hash::merge($this->options('articles'), array('paginate' => array('divOptions' => array())));
		$result = $this->Table->renderPagination($options);
		$expected = array(
			array('div' => true),
			array('span' => array('class' => 'disabled')), 'Previous', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'current')), 1, '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/span',
			'preg:/\s*/', 'preg:/&nbsp;/', array('span' => array('class' => 'next')), array('a' => array('href' => '/index/page:2', 'rel' => 'next')), 'Next', '/a', '/span',
			'preg:/\s*/', '/div'
		);
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$expected = array(
				array('div' => true),
				array('ul' => true),
				array('li' => array('class' => 'previous disabled')), array('a' => array('href' => '/index/page:1')), 'Previous', '/a', '/li',
				'preg:/\s*/', array('li' => array('class' => 'current disabled')), array('a' => array('href' => '#')), 1, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2')), 2, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:3')), 3, '/a', '/li',
				'preg:/\s*/', array('li' => true), array('a' => array('href' => '/index/page:2', 'rel'=> 'next')), 'Next', '/a', '/li',
				'preg:/\s*/', '/ul',
				'preg:/\s*/', '/div'
			);
		}
		$this->assertTags($result, $expected);
	}

}
