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
add_action( 'wp_ajax_edd_fes_draft_auto_save', 'edd_fes_draft_auto_save_ajax' );
function edd_fes_draft_auto_save_ajax( ) {
	$form_id   = isset( $_REQUEST['form_id'] )   ? absint( $_REQUEST['form_id'] )   : EDD_FES()->helper->get_option( 'fes-submission-form', false );
	$user_id   = isset( $_REQUEST['user_id'] )   ? absint( $_REQUEST['user_id'] )   : get_current_user_id();
	$vendor_id = isset( $_REQUEST['vendor_id'] ) ? absint( $_REQUEST['vendor_id'] ) : -2;
	$values    = $_POST;
	$post_id   = !empty( $values ) && isset( $values['post_id'] ) && $values['post_id'] > 0 ? absint( $values['post_id'] ) : EDD()->session->get( 'fes_post_id' );

	// Make the FES Form
	$form      = new FES_Submission_Form( $form_id, 'id', $post_id );

	// Save the FES Form
	$form->save_form_frontend( $values , $user_id );
}