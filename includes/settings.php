<?php
/**
 * Settings
 *
 * @package     EDD\FES_Draft\Settings
 * @since       1.0.3
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_FES_Draft_Settings' ) ) {

    class EDD_FES_Draft_Settings {

        public function __construct() {
            // Add new section under FES settings
            add_filter( 'edd_settings_sections', array( $this, 'sections' ), 2, 1 );

            // Add settings page content
            add_filter( 'edd_registered_settings', array( $this, 'settings' ), 1 );
        }

        public function sections( $sections ) {
            if( isset( $sections['fes'] ) ) {
                $sections['fes']['draft'] = __( 'Drafts', 'edd-fes-draft' );
            }

            return $sections;
        }

        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {
            if( isset( $settings['fes'] ) ) {
                $settings['fes']['draft'] = array(
                    array(
                        'id'    => 'edd_fes_draft_button_text',
                        'name'  => __( 'Save Draft Button Text', 'edd-fes-draft' ),
                        'desc'  => '',
                        'type'  => 'text',
                        'std'   => __( 'Save Draft', 'edd-fes-draft' )
                    ),
                    array(
                        'id'    => 'edd_fes_draft_auto_save',
                        'name'  => __( 'Enable Auto Save', 'edd-fes-draft' ),
                        'desc'  => '',
                        'type'  => 'checkbox',
                    ),
                    array(
                        'id'    => 'edd_fes_draft_preview',
                        'name'  => __( 'Enable Preview Button', 'edd-fes-draft' ),
                        'desc'  => '',
                        'type'  => 'checkbox',
                    ),
                    array(
                        'id'    => 'edd_fes_draft_preview_button_text',
                        'name'  => __( 'Preview Button Text', 'edd-fes-draft' ),
                        'desc'  => '',
                        'type'  => 'text',
                        'std'   => __( 'Preview', 'edd-fes-draft' )
                    ),
                    array(
                        'id'    => 'edd_fes_draft_pending_checkbox',
                        'name'  => __( 'Submit To Review Checkbox', 'edd-fes-draft' ),
                        'desc'  => __( 'Adds a checkbox to enable submit product as a pending to review', 'edd-fes-draft' ),
                        'type'  => 'checkbox',
                        'std'   => __( 'Preview', 'edd-fes-draft' )
                    ),
                    array(
                        'id'    => 'edd_fes_draft_pending_checkbox_label',
                        'name'  => __( 'Submit To Review Checkbox Label', 'edd-fes-draft' ),
                        'desc'  => '',
                        'type'  => 'text',
                        'std'   => __( 'I am sure to submit to review', 'edd-fes-draft' )
                    ),
                    array(
                        'id'    => 'edd_fes_draft_prevent_edit_pending',
                        'name'  => __( 'Prevent Edit Pending Products', 'edd-fes-draft' ),
                        'desc'  => __( 'If enabled then vendors can not edit pending products (a desired restriction for draft functionallity)', 'edd-fes-draft' ),
                        'type'  => 'checkbox',
                    ),
                    array(
                        'id'    => 'edd_fes_draft_maintain_published_downloads',
                        'name'  => __( 'Maintain Published Downloads Visibility', 'edd-fes-draft' ),
                        'desc'  => __( 'If vendor edits a published download then creates a copy of edited download (this allows you to maintain always visible published downloads)', 'edd-fes-draft' ),
                        'type'  => 'checkbox',
                    ),
                );
            }

            return $settings;
        }

    }

}
