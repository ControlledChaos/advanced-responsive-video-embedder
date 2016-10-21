<?php


/**
 * @group edd_shortcode
 */
class Tests_Shortcode extends WP_UnitTestCase {

	protected $_payment_id = null;

	protected $_post = null;

	protected $_payment_key = null;

	public function setUp() {
		parent::setUp();

		#$this->_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		#wp_set_current_user( $this->_user_id );

		#$post_id = $this->factory->post->create( array( 'post_title' => 'ARVE Shortcode Test', 'post_status' => 'publish' ) );
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_shortcodes_are_registered() {
		global $shortcode_tags;

		$this->assertArrayHasKey( 'arve', $shortcode_tags );
		$this->assertArrayHasKey( 'youtube', $shortcode_tags );
		$this->assertArrayHasKey( 'vimeo', $shortcode_tags );
	}

	public function test_arve_shortcode() {

		$arve_shortcode = arve_shortcode( array(
			'url' => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys'
		) );

		$old_shortcode = arve_shortcode( array(
			'provider' => 'youtube',
			'id'       => 'hRonZ4wP8Ys',
		) );

		$this->assertInternalType( 'string', $shortcode );
		$this->assertContains( 'class="arve-wrapper ', $shortcode );

		$this->assertEquals( $arve_shortcode, $old_shortcode );
	}

}