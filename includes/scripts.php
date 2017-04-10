<?php
/**
 * Scripts
 *
 * @package     EDD\FES_Draft\Scripts
 * @since       1.0.3
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_FES_Draft_Scripts' ) ) {

	class EDD_FES_Draft_Scripts {

		public function __construct() {
			// Enqueue frontend scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Enqueue admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		}

		/**
		 * Load frontend scripts
		 *
		 * @since       1.0.0
		 * @return      void
		 */
		public function enqueue_scripts( $hook ) {
			// Use minified libraries if SCRIPT_DEBUG is turned off
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script( 'edd-fes-draft', EDD_FES_DRAFT_URL . '/assets/js/edd-fes-draft' . $suffix . '.js', array( 'jquery' ), EDD_FES_DRAFT_VER, true );
			$options = array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'auto_save' => edd_get_option( 'edd_fes_draft_auto_save', false ),
				'pending_checkbox' => edd_get_option( 'edd_fes_draft_pending_checkbox', false ),
			);

			$options = apply_filters( 'edd_fes_draft_scripts_options', $options );
			wp_localize_script( 'edd-fes-draft', 'edd_fes_draft', $options );
		}

		/**
		 * Load admin scripts
		 *
		 * @since       1.0.0
		 * @return      void
		 */
		public function enqueue_admin_scripts( $hook ) {
			// Use minified libraries if SCRIPT_DEBUG is turned off
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

            wp_enqueue_style( 'edd-fes-draft-admin', EDD_FES_DRAFT_URL . '/assets/css/edd-fes-draft-admin' . $suffix . '.css', array(), EDD_FES_DRAFT_VER );
		}

	}

}