<?php

/**
 * Optimizer class.
 */
class PL_Base {
	private $learn_mode = true;

	const HANDLES_OPTION = 'pl_handles';

	private $script_definitions = [
		'normal' => [],
		'async'  => [],
		'defer'  => [],
		'onload' => [],
	];
	private $style_definitions = [];

	private $discovered_scripts = [];
	private $discovered_styles = [];

	var $enqueued_scripts = [
		'async'  => [],
		'onload' => [],
		'defer'  => [],
		'normal' => [],
	];

	var $all_styles = [
		'defer'  => [],
		'normal' => [],
	];
	
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Setup hooks.
	 */
	public function init() {
		if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
			return;
		}

		if ( is_admin() || is_user_logged_in() ) {
			return;
		}

		add_action( 'wp_print_scripts', [ $this, 'load_definitions' ] );

		add_filter( 'script_loader_tag', [ $this, 'script_loader_tag' ], 1000, 2 );
		add_filter( 'style_loader_tag', [ $this, 'style_loader_tag' ], 10, 2 );

		add_action( 'wp_footer', [ $this, 'add_script_loader' ], 999 );
		
		add_action( 'wp_head', [ $this, 'do_preload_fonts' ], 5 );
		add_action( 'wp_head', [ $this, 'do_preload_scripts' ], 5 );
		add_action( 'wp_head', [ $this, 'do_preconnect' ], 5 );

		// Update discovered scripts/styles.
		if ( $this->learn_mode ) {
			add_action( 'shutdown', [ $this, 'maybe_save_option' ] );
		}
	}

	public function load_definitions() {
		$defs = get_option( PL_Base::HANDLES_OPTION, [] );

		// Load these normally.
		$this->script_definitions['normal'] = apply_filters( 'pl_script_load_normal', $defs['scripts']['normal'] ?? [] );

		// Defer these handles.
		$this->script_definitions['defer'] = apply_filters( 'pl_script_load_defer', $defs['scripts']['defer'] ?? [] );

		// Async these handles.
		$this->script_definitions['async'] = apply_filters( 'pl_script_load_async', $defs['scripts']['async'] ?? [] );

		// Onload these.
		$this->script_definitions['onload'] = apply_filters( 'pl_script_load_windowload', $defs['scripts']['onload'] ?? [] );
	}

	/**
	 * Add delayed script loader.
	 */
	public function add_script_loader() {
		$pl_script_data = [
			'delayAfterWindowLoad' => 0,
			'failsafe'             => 6000,
			'sources'              => $this->enqueued_scripts,
		];
		$url = plugins_url( 'promising-loader' ) . '/script-loader.js';
		$version = filemtime( plugin_dir_path( __FILE__ ) . 'script-loader.js' );

		?>
		<script src="<?php echo esc_url( $url . '?' . $version ); ?>" defer></script>
		<script>pl_script_data = <?php echo wp_json_encode( $pl_script_data ); ?>;</script>
		<?php
	}

	/**
	 * Load scripts.
	 */
	public function script_loader_tag( $tag, $handle ) {
		global $wp_scripts;

		$this->discovered_scripts[] = $handle;

		// Handle Async
		if ( in_array( $handle, $this->script_definitions['async'], true ) ) {
			$this->enqueued_scripts['async'][] = apply_filters( 
				'pl_script_def_async',
				[
					'handle'  => $handle,
					'type'    => 'async',
					'src'     => $wp_scripts->registered[ $handle ]->src,
					'deps'    => $wp_scripts->registered[ $handle ]->deps,
				]
			);

			return str_replace( ' src=', ' async src=', $tag );
		}

		if ( false === strstr( $wp_scripts->registered[ $handle ]->src, '?' ) ) {
			$obj = $wp_scripts->registered[ $handle ];
			if ( null === $obj->ver ) {
				$ver = '';
			} else {
				$ver = '?' . $obj->ver;
			}
		}

		// Handle defer and normal.
		if ( in_array( $handle, $this->script_definitions['defer'], true ) ) {
			$this->enqueued_scripts['defer'][] = apply_filters(
				'pl_script_def_defer',
				[
					'handle'  => $handle,
					'type'    => 'defer',
					'src'     => $wp_scripts->registered[ $handle ]->src . $ver,
					'deps'    => $wp_scripts->registered[ $handle ]->deps,
				]
			);

			return str_replace( ' src=', ' defer src=', $tag );
		}  elseif ( in_array( $handle, $this->script_definitions['normal'], true ) ) {
			$this->enqueued_scripts['normal'][] = apply_filters(
				'pl_script_def_normal',
				[
					'handle'  => $handle,
					'type'    => 'normal',
					'src'     => $wp_scripts->registered[ $handle ]->src . $ver,
					'deps'    => $wp_scripts->registered[ $handle ]->deps,
				]
			);
	
			return $tag;
		}

		// Everything that is not specifically marked async/defer/normal will therefore be onload.
		$def = [
			'handle'  => $handle,
			'type'    => 'onload',
			'src'     => $wp_scripts->registered[ $handle ]->src . $ver,
			'deps'    => $wp_scripts->registered[ $handle ]->deps,
			'delay'   => 0,
			'budget'  => 0,
		];

		if ( in_array( $handle, array_keys( $this->script_definitions['onload'] ), true ) ) {
			$def['delay'] = empty( $this->script_definitions['onload'][ $handle ]['delay'] ) ? 0 : absint( $this->script_definitions['onload'][ $handle ]['delay'] );
			$def['budget'] = empty( $this->script_definitions['onload'][ $handle ]['budget'] ) ? 0 : absint( $this->script_definitions['onload'][ $handle ]['budget'] );
			$additional_dependencies = empty( $this->script_definitions['onload'][ $handle ]['additional_deps'] ) ? [] : $this->script_definitions['onload'][ $handle ]['additional_deps'];
			$def['deps'] = array_merge( $def['deps'], $additional_dependencies );
			
		}
		$this->enqueued_scripts['onload'][] = apply_filters( 'pl_script_def_onload', $def );

		return '';
	}

	/**
	 * Preload CSS.
	 */
	public function style_loader_tag( $tag, $handle ) {
		global $wp_styles;
		$this->discovered_styles[] = $handle;

		$skip_preload = apply_filters( 'pl_skip_preload_css', [] );

		if ( ! in_array( $handle, $skip_preload, true ) ) {
			$this->all_styles['preload'][] = $handle;
			$preload_tag_noscript = sprintf(
				'<noscript>%s</noscript>',
				str_replace(
					" id='",
					" id='fallback-",
					$tag
				)
			);
			$preload_tag = sprintf(
				'%s',
				str_replace(
					" rel='stylesheet'", // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
					" rel='preload' as='style' onload='this.onload=null;this.rel=\"stylesheet\"'",
					$tag
				)
			);
			return $preload_tag;
		}

		$this->all_styles['normal'][] = $handle;
		return $tag;
	}

	/**
	 * Setup fonts to preload.
	 */
	public function do_preload_fonts() {
		$fonts = apply_filters( 'pl_preload_fonts', [] );

		echo "<!-- Preload Fonts -->\n";
		foreach ( $fonts as $font ) {
			?>
			<link rel="preload" as="font" href="<?php echo esc_url( $font ); ?>" crossorigin="anonymous">
			<?php
		}
		echo "<!-- Preload Fonts END -->\n";
	}

	/**
	 * Setup SCRIPT to preload. Still not usable.
	 */
	public function do_preload_scripts() {
		global $wp_scripts;

		$scripts = apply_filters( 'pl_preload_scripts', [] );

		echo "<!-- Preload Scripts -->\n";

		foreach ( $scripts as $script ) {
			$url = $wp_scripts->registered[ $script ]->src;
			?>
			<link rel="preload" href="<?php echo esc_url( $url ); ?>" as="script">
			<?php
		}
		echo "<!-- Preload Scripts END -->\n";
	}

	/**
	 * Add preconnect origins.
	 */
	public function do_preconnect() {
		$origins = apply_filters( 'pl_preconnect_origins', [] );

		echo "<!-- Preconnect Origins -->\n";

		foreach ( $origins as $origin ) {
			?>
			<link rel="preconnect" href="<?php echo esc_url( $origin ); ?>">
			<?php
		}
		echo "<!-- Preconnect Origins END -->\n";
	}

	public function maybe_save_option() {
		global $wp_scripts;

		if ( $this->learn_mode ) {
			$previous_handles = array_merge( 
				array_keys( $this->script_definitions['onload'] ),
				$this->script_definitions['normal'],
				$this->script_definitions['async'],
				$this->script_definitions['defer']
			);

			$new_scripts = array_diff( $this->discovered_scripts, $previous_handles );

			foreach ( $new_scripts as $handle ) {
				$this->script_definitions['onload'][$handle] = [];
			}

			$defs = [
				'scripts' => $this->script_definitions,
				'styles'  => [],
			];
			
			if ( ! empty( $new_scripts ) ) {
				update_option( PL_Base::HANDLES_OPTION, $defs );
			}
		}
	}
}
