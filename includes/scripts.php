<?php
/**
 * Scripts
 *
 * @package     EDD\FES_Draft\Scripts
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Load frontend scripts
 *
 * @since       1.0.0
 * @return      void
 */
add_action( 'wp_enqueue_scripts', 'edd_fes_draft_scripts' );
function edd_fes_draft_scripts( $hook ) {
    // Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

    wp_enqueue_script( 'edd_fes_draft', EDD_FES_DRAFT_URL . '/assets/js/edd-fes-draft' . $suffix . '.js', array( 'jquery' ), '0.1', true );
    $options = array(
		'ajax_url'  => admin_url( 'admin-ajax.php' ),
		'auto_save' => edd_get_option( 'edd_fes_draft_auto_save', false ),
		'pending_checkbox' => edd_get_option( 'edd_fes_draft_pending_checkbox', false ),
	);
			
	$options = apply_filters( 'edd_fes_draft_scripts_options', $options );
	wp_localize_script( 'edd_fes_draft', 'edd_fes_draft', $options );
}