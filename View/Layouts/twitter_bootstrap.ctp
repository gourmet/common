<?php
/**
 * Twitter Bootstrap's default layout.
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
?>
<!DOCTYPE html>

<?php
$this->startIfEmpty('html');
printf('<html lang="%s" class="no-js">', Configure::read('Config.language'));
$this->end();
echo $this->fetch('html');
?>

	<head>

		<?php echo $this->Html->charset(); ?>

		<title>
			<?php
			if (!empty($subtitle)) {
				echo $subtitle . ' - ';
			}
			echo $title;
			?>
		</title>

		<?php
		/**
		 * META Tags
		 */
		echo $this->Html->meta(array('name' => 'author', 'content' => $title));
		echo $this->Html->meta('favicon.ico', '/favicon.ico', array('type' => 'icon'));
		echo (isset($this->AppleTouch) && is_a($this->AppleTouch, 'AppleTouchHelper')) ? $this->AppleTouch->viewport() : null;
		echo $this->fetch('meta');

		/**
		 * Stylesheets
		 */
		$__html5Shim =
<<<HTML
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
<script src="//oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->
HTML
		;
		if (!isset($this->AssetCompress) || !is_a($this->AssetCompress, 'AssetCompressHelper')) {
			$this->append('css', $this->Html->css('bootstrap/bootstrap.min'));
			$this->append('css', $this->Html->css('Common.common', null, array('plugin' => true)));
			$this->append('css', $this->Html->css('Common.tb_sticky_footer', null, array('plugin' => true)));
		} else {
			$this->append('css', $this->AssetManager->css('common', array('raw' => Common::read('AssetCompress.raw', Configure::read('debug')))));
		}
		$this->append('css', $__html5Shim);
		echo $this->fetch('css');
		?>

	</head>

	<body class="<?php echo implode(' ', array($this->request->controller, $this->request->action)); ?>">

		<div id="wrap">

			<?php
			$this->startIfEmpty('navbar');
			?>
			<div class="navbar navbar-default navbar-static-top" role="navigation">

				<div class="container">

					<div class="navbar-header">

						<?php
						$this->startIfEmpty('twitterBootstrapBtnNavbar');
						echo $this->element('Common.TwitterBootstrap/btn-navbar');
						$this->end();
						echo $this->fetch('twitterBootstrapBtnNavbar');
						?>

						<?php
						$this->startIfEmpty('title');
						echo $this->Html->link(Configure::read('App.title'), '/', array('role' => 'banner', 'class' => 'navbar-brand', 'tabindex' => -1));
						$this->end();
						echo $this->fetch('title');
						?>

					</div>

					<?php
					$this->startIfEmpty('twitterBootstrapNavCollapse');
					echo $this->element('Common.TwitterBootstrap/nav-collapse');
					$this->end();
					echo $this->fetch('twitterBootstrapNavCollapse');
					?>

				</div>

			</div>
			<?php
			$this->end();
			echo $this->fetch('navbar');

			echo $this->fetch('jumbotron');
			?>

			<div class="container" role="main">

				<?php
				/**
				 * Block: sidebar
				 */
				$this->startIfEmpty('sidebar');
				echo $this->element('Common.blocks/sidebar');
				$this->end();
				echo $this->fetch('sidebar');

				/**
				 * Block: crumbs
				 */
				$this->startIfEmpty('crumbs');
				if (!empty($breadCrumbs)) {
					$__crumb = array(array_shift(array_keys($breadCrumbs)) => array_shift($breadCrumbs));
					foreach ($breadCrumbs as $__k => $__v) {
						$__v = (array) $__v;
						if (!array_key_exists('link', $__v) && !array_key_exists('options', $__v)) {
							$__v = array('link' => $__v);
						}
						$__v = array_merge(array('options' => array(), 'link' => array()), $__v);

						$this->Html->addCrumb($__k, $__v['link'], $__v['options']);
					}
					echo $this->Html->div('breadcrumb', $this->Html->getCrumbs(' > ', array(
						'text' => reset(array_keys($__crumb)),
						'url' => reset($__crumb),
					)));
				}
				unset($__k, $__v);
				$this->end();
				echo $this->fetch('crumbs');

				/**
				 * Block: flash.
				 */
				$this->startIfEmpty('flash');
				foreach ((array) Common::read('Common.messages', array_keys((array) $this->Session->read('Message'))) as $__k) {
					$__k = $this->Session->flash($__k);
					if (!empty($__k)) {
						echo $__k;
						break;
					}
				}
				unset($__k);
				$this->end();
				echo $this->fetch('flash');

				/**
				 * Block: content.
				 */
				echo $this->fetch('content');
				?>

			</div>

			<div id="push"></div>

		</div>

		<footer id="footer">

			<div class="container" role="contentinfo">
				<?php
				/**
				 * Block: footer.
				 */
				$this->startIfEmpty('footer');
				printf('<p>&copy;%s %s</p>', date('Y'), Configure::read('App.title'));
				$this->end();
				echo $this->fetch('footer');
				?>
			</div>

		</footer>

		<?php
		/**
		 * Block: modals.
		 */
		echo $this->fetch('modals');

		/**
		 * Javascript.
		 */
		if (!isset($this->AssetCompress) || !is_a($this->AssetCompress, 'AssetCompressHelper')) {
			echo $this->Html->script(array('jquery/jquery', 'bootstrap/bootstrap'));
		} else {
			echo $this->AssetCompress->script('common', array('raw' => Common::read('AssetCompress.raw', Configure::read('debug'))));
		}
		echo $this->fetch('script');

		/**
		 * Lazy load JS blocks.
		 */
		echo $this->fetch('lazyload');
		?>

	</body>

</html>
