<?php
class PL_Admin_UI {
	private $options_page;

	/**
	 * Constructor.
	 */
	function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Setup hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Register Menu.
	 */
	function register_menu() {
		$this->options_page = add_options_page( 'JS Loader', 'JS Loader', 'manage_options', 'pl-admin', array( $this, 'render_admin_page' ) );
	}

	function admin_scripts( $hook ) {
		if ( $hook === $this->options_page ) {
			wp_enqueue_style( 'pl_admin_style', plugin_dir_url( __FILE__ ) . 'ui/dist/css/app.css' );
			wp_enqueue_script(
				'pl_admin_script',
				plugin_dir_url( __FILE__ ) . 'ui/dist/js/app.js',
				[],
				filemtime( plugin_dir_path( __FILE__ ) . 'ui/dist/js/app.js' ),
				true
			);
			wp_localize_script( 'pl_admin_script', 'pl_data', get_option( PL_Base::HANDLES_OPTION ) );
		}
	}

	public function render_admin_page() {
		?>
		<h2>Promising Loader</h2>
		<div id="app"></div>
		<?php
	}
}
