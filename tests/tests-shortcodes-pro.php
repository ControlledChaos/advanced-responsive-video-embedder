<?php

class Tests_Shortcode_Pro extends WP_UnitTestCase {

	public static function setUpBeforeClass() {
		activate_plugin( 'arve-pro/arve-pro.php' );
	}

	public static function tearDownAfterClass() {
		deactivate_plugins( 'arve-pro/arve-pro.php' );
	}

	public function test_thumbnails() {

		$filename = dirname( __FILE__ ) . '/test-attachment.jpg';
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		$this->assertTrue( empty( $upload['error'] ) );

		$attachment_id = parent::_make_attachment( $upload );

		$attr = array(
			'url'       => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys',
			'thumbnail' => (string) $attachment_id,
			'mode'      => 'lazyload',
		);

		$this->assertRegExp( '#<img itemprop="thumbnailUrl" content=".*test-attachment\.jpg#', arve_shortcode_arve( $attr ) );

		$attr['thumbnail'] = 'https://example.com/image.jpg';
		$this->assertContains( '<meta itemprop="thumbnailUrl" content="https://example.com/image.jpg"', arve_shortcode_arve( $attr ) );
	}

	public function test_lazyload() {

		$attr = array(
			'url'       => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys',
			'thumbnail' => 'https://example.com/image.jpg',
			'mode'      => 'lazyload',
		);

		$this->assertNotContains( 'ARVE Error', $output );
		$this->assertContains( 'data-arve-mode="lazyload"', arve_shortcode_arve( $attr ) );
		$this->assertContains( 'data-arve-grow', arve_shortcode_arve( $attr ) );
	}

	public function test_modes() {

		$output = arve_shortcode_arve( array( 'url' => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys' ) );

		$this->assertNotContains( 'ARVE Error', $output );
		$this->assertContains( 'data-arve-mode="normal"', $output );

		$modes = array( 'lazyload', 'lazyload-lightbox' );

		foreach ( $modes as $key => $mode ) {

			$output = arve_shortcode_arve( array( 'url' => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys', 'mode' => $mode ) );
			$this->assertContains( 'Error', $output );
		}
	}
}