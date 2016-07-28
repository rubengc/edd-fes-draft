<?php
/**
 * Plugin Name:     EDD FES Draft
 * Plugin URI:      https://wordpress.org/plugins/edd-fes-draft/
 * Description:     Adds draft submissions to Easy Digital Downloads Frontend Submissions plugin
 * Version:         1.0.0
 * Author:          rubengc
 * Author URI:      http://rubengc.com
 * Text Domain:     edd-fes-draft
 *
 * @package         EDD\FES_Draft
 * @author          rubengc
 * @copyright       Copyright (c) rubengc
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_FES_Draft' ) ) {

    /**
     * Main EDD_FES_Draft class
     *
     * @since       1.0.0
     */
    class EDD_FES_Draft {

        /**
         * @var         EDD_FES_Draft $instance The one true EDD_FES_Draft
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_FES_Draft
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_FES_Draft();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // plugin folder url
            if ( ! defined( 'EDD_FES_DRAFT_URL' ) ) {
                define( 'EDD_FES_DRAFT_URL', plugin_dir_url( __FILE__ ) );
            }

            // plugin folder path
            if ( ! defined( 'EDD_FES_DRAFT_DIR' ) ) {
                define( 'EDD_FES_DRAFT_DIR', plugin_dir_path( __FILE__ ) );
            }

            // plugin root file
            if ( ! defined( 'EDD_FES_DRAFT_FILE' ) ) {
                define('EDD_FES_DRAFT_FILE', __FILE__);
            }
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            require_once EDD_FES_DRAFT_DIR . 'includes/scripts.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Register settings
            add_filter( 'edd_registered_settings', array( $this, 'settings' ), 1 );
            add_filter( 'edd_settings_sections', array( $this, 'edd_settings_panel_add_sections' ), 2, 1 );

            // Frontend submission
            add_filter( 'fes_render_submission_form_frontend_fields', array( $this, 'edd_fes_draft_html_fes_field' ), 10, 4 );
            add_filter( 'fes_save_submission_form_frontend_values', array( $this, 'edd_fes_draft_submission_form_values' ), 10, 3 );
            add_filter( 'fes_after_submission_form_save_frontend', array( $this, 'edd_fes_draft_after_form_save_frontend' ), 10, 4 );

            // Approve download
            add_action( 'fes_approve_download_admin', array( $this, 'edd_fes_draft_approve_download' ) );

            // New meta boxes
            add_action( 'add_meta_boxes', array( $this, 'edd_fes_draft_add_meta_boxes' ) );
        }

        // EDD FES Save draft button
        function edd_fes_draft_html_fes_field( $fields, $form, $user_id, $readonly ) {
            $label = edd_get_option( 'edd_fes_draft_button_text', __( 'Save draft', 'edd-fes-draft' ) );
            $color = edd_get_option( 'checkout_color', 'blue' );
            $color = ( $color == 'inherit' ) ? '' : $color;
            $style = edd_get_option( 'button_style', 'button' );

            ob_start(); ?>
            <fieldset class="fes-submit edd-fes-draft-submit">
                <?php
                if( ! empty( $_REQUEST['post_id'] ) ) {
                    if( edd_get_option( 'edd_fes_draft_preview', false ) ) {
                        $preview_label = edd_get_option( 'edd_fes_draft_preview_button_text', __( 'Preview', 'edd-fes-draft' ) );
                        ?>
                        <a href="<?php echo esc_html(get_permalink($_REQUEST['post_id'])); ?>" target="_blank" class="edd-fes-draft-preview"><?php echo $preview_label; ?></a>
                        <?php
                    }
                }
                ?>
                <button type="button" id="edd-fes-draft-button" class="edd-fes-draft-button edd-submit <?php echo $color; ?> <?php echo $style; ?>"><?php echo $label; ?></button>
            </fieldset>
            <?php if( edd_get_option( 'edd_fes_draft_pending_checkbox', false ) ) { ?>
                <fieldset class="fes-el pending-checkbox fes-checkbox">
                    <div class="checkbox">
                        <input type="checkbox" id="edd_fes_draft_pending_checkbox" class="edd-fes-draft-pending-checkbox" value="true">
                        <label for="edd_fes_draft_pending_checkbox"><?php echo edd_get_option( 'edd_fes_draft_pending_checkbox_label', __('I am sure to submit to review', 'edd-fes-draft') ); ?></label>
                    </div>
                </fieldset>
            <?php } ?>
            <?php $html = ob_get_clean();

            $html_field = new FES_HTML_Field(array( 'html' => $html ), $form, 'custom', $form->save_id);

            $fields[] = $html_field;

            return $fields;
        }

        // If submitted download is published, then create a copy of this download
        public function edd_fes_draft_submission_form_values( $args, $form, $save_id ) {
            // Restores session values
            EDD()->session->set( 'edd_fes_draft_original_post_id', -2 );

            if($save_id != -2) { // Is an already existent download
                $post = get_post($save_id);

                if($post->post_status == 'publish' && edd_get_option( 'edd_fes_draft_maintain_published_downloads', false )) {
                    // If user wants maintain published downloads, then restore the form save id and some session vars
                    $form->change_save_id( -2 );
                    EDD()->session->set( 'fes_is_new', true );
                    EDD()->session->set( 'fes_is_pending', false );
                    EDD()->session->set( 'edd_fes_post_id', -2 );
                    EDD()->session->set( 'edd_fes_draft_original_post_id', $save_id );
                }
            }

            return $args;
        }

        // Custom handle of submission form submit
        public function edd_fes_draft_after_form_save_frontend( $output, $save_id, $values, $user_id ) {
            // If an original post id is stored, then update the meta
            if(EDD()->session->get( 'edd_fes_draft_original_post_id' ) != -2) {
                update_post_meta($save_id, 'edd_fes_draft_original_download', EDD()->session->get( 'edd_fes_draft_original_post_id' ));

                // Restore session vars
                EDD()->session->set( 'edd_fes_draft_original_post_id', -2 );
            }

            $post = get_post( $save_id );

            // Is user clicks Save draft button, then change post status and returned message
            if(isset($values['save_draft'])) {
                EDD()->session->set( 'fes_is_pending', false ); // Prevents emails to user and administrator
                $post->post_status = 'draft';
                $output['post_id'] = $post->ID;
                $output['message'] = __( 'Draft saved successfully!', 'edd-fes-draft' );
            } else {
                $post->post_status = 'pending';

                // If prevent edit pending products then redirects to products list page
                if( edd_get_option( 'edd_fes_draft_prevent_edit_pending', false ) ) {
                    $redirect_to = get_permalink( EDD_FES()->helper->get_option( 'fes-vendor-dashboard-page', false ) );

                    $redirect_to = add_query_arg( array(
                        'task' => 'products'
                    ), $redirect_to );

                    $output['redirect_to'] = $redirect_to;
                }
            }

            wp_update_post( $post );

            return $output;
        }

        // On approve download, if has an original download, then update the original download and remove the current one
        public function edd_fes_draft_approve_download( $download_id ) {
            global $wpdb;

            $original_download_id = get_post_meta( $download_id, 'edd_fes_draft_original_download', true );

            if($original_download_id) {
                $download = get_post( $download_id );
                $original_download = get_post( $original_download_id );

                if($original_download) {
                    // Original download data
                    $data = array(
                        'ID' => $original_download_id,
                        'comment_status' => $download->comment_status,
                        'ping_status'    => $download->ping_status,
                        'post_author'    => $download->post_author,
                        'post_content'   => $download->post_content,
                        'post_excerpt'   => $download->post_excerpt,
                        // Only changes slug if download's title changes
                        'post_name'      => ($original_download->post_title != $download->post_title) ? $download->post_name : $original_download->post_name,
                        'post_parent'    => $download->post_parent,
                        'post_password'  => $download->post_password,
                        'post_status'    => $download->post_status,
                        'post_title'     => $download->post_title,
                        'post_type'      => $download->post_type,
                        'to_ping'        => $download->to_ping,
                        'menu_order'     => $download->menu_order
                    );

                    $data = apply_filters('edd_fes_draft_approve_download_data', $data, $download, $original_download );

                    // Update original download terms based on download terms
                    $taxonomies = get_object_taxonomies( $download->post_type );
                    foreach ($taxonomies as $taxonomy) {
                        $post_terms = wp_get_object_terms( $download_id, $taxonomy, array('fields' => 'slugs') );
                        wp_set_object_terms( $original_download_id, $post_terms, $taxonomy, false );
                    }

                    // Excluded metas to update
                    $excluded_metas = array(
                        'edd_fes_draft_original_download',
                        '_edd_download_earnings',
                        '_edd_download_sales',
                    );

                    $excluded_metas = apply_filters('edd_fes_draft_approve_download_excluded_metas', $excluded_metas, $download, $original_download );

                    $post_metas = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$download_id");

                    // Update original download post metas based on download metas
                    if ( count($post_metas) != 0 ) {
                        $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                        $meta_keys = array();
                        foreach ( $post_metas as $post_meta ) {
                            if( ! in_array( $post_meta->meta_key, $excluded_metas ) ) {
                                $meta_key = $post_meta->meta_key;
                                $meta_value = addslashes($post_meta->meta_value);
                                $sql_query_sel[]= "SELECT $original_download_id, '$meta_key', '$meta_value'";

                                $meta_keys[] = "'" . $meta_key . "'";
                            }
                        }
                        // Delete old metas
                        if( !empty($meta_keys) ) {
                            $meta_keys = implode(', ', $meta_keys);
                            $wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id=$original_download_id AND meta_key IN ($meta_keys)");
                        }

                        // Insert new metas
                        $sql_query.= implode(" UNION ALL ", $sql_query_sel);
                        $wpdb->query($sql_query);
                    }

                    if( apply_filters('edd_fes_draft_approve_download_remove_old', '__return_true' ) ) {
                        wp_delete_post( $download_id, true );
                    }

                    // Update original download data based on download data
                    wp_update_post( $data );
                } else {
                    // Unset original download meta if does not exists
                    update_post_meta( $download_id, 'edd_fes_draft_original_download', '', $original_download_id );
                }
            }
        }

        // Adds a meta box of original download
        public function edd_fes_draft_add_meta_boxes() {
            global $post;

            $original_download_id = get_post_meta( $post->ID, 'edd_fes_draft_original_download', true );

            if($original_download_id) {
                add_meta_box(
                    'edd-fes-draft-original-download', 
                    'Original Download', 
                    array( $this, 'edd_fes_draft_original_download_meta_box_content' ), 
                    'download', 
                    'side', 
                    'default'
                );
            }
        }

        public function edd_fes_draft_original_download_meta_box_content( $post ) {
            $original_download_id = get_post_meta( $post->ID, 'edd_fes_draft_original_download', true );

            if($original_download_id) {
                $original_download = get_post($original_download_id);
                if( ! is_wp_error( $original_download ) ) { ?>
                    <a href="<?php echo add_query_arg( array( 'action' => 'edit', 'post' => $original_download_id ), 'post.php' ); ?>">#<?php echo $original_download->ID; ?> - <?php echo $original_download->post_title; ?></a>
                <?php }
            }
        }

        // Without posibility to override or extend this function of EDD FES, so added to allow an easy override of product list actions
        // Template: fes_templates/frontend-products.php
        public function product_list_actions( $product_id ) {

            if ( 'publish' == get_post_status( $product_id ) ) : ?>
                <a href="<?php echo esc_html( get_permalink( $product_id ) );?>" title="<?php _e( 'View', 'edd_fes' );?>" class="edd-fes-action view-product-fes"><?php _e( 'View', 'edd_fes' );?></a>
            <?php endif; ?>

            <?php if ( 'publish' != get_post_status( $product_id ) && 'future' != get_post_status( $product_id ) ) : ?>
                <a href="<?php echo esc_html( get_permalink( $product_id ) );?>" title="<?php _e( 'Preview', 'edd-fes-draft' );?>" class="edd-fes-action view-product-fes"><?php _e( 'Preview', 'edd-fes-draft' );?></a>
            <?php endif; ?>

            <?php if ( EDD_FES()->helper->get_option( 'fes-allow-vendors-to-edit-products', false ) && 'future' != get_post_status( $product_id ) && ( 'pending' == get_post_status( $product_id ) && edd_get_option( 'edd_fes_draft_prevent_edit_pending', false ) ) ) : ?>
                <a href="<?php echo add_query_arg( array( 'task' => 'edit-product', 'post_id' => $product_id ), get_permalink() ); ?>" title="<?php _e( 'Edit', 'edd_fes' );?>" class="edd-fes-action edit-product-fes"><?php _e( 'Edit', 'edd_fes' );?></a>
            <?php endif; ?>

            <?php if ( EDD_FES()->helper->get_option( 'fes-allow-vendors-to-delete-products', false ) ) : ?>
                <a href="<?php echo add_query_arg( array( 'task' => 'delete-product', 'post_id' => $product_id ), get_permalink() );?>" title="<?php _e( 'Delete', 'edd_fes' );?>" class="edd-fes-action edit-product-fes"><?php _e( 'Delete', 'edd_fes' );?></a>
            <?php endif;
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_FES_DRAFT_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_fes_draft_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-fes-draft' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-fes-draft', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-fes-draft/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-fes-draft/ folder
                load_textdomain( 'edd-fes-draft', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-fes-draft/languages/ folder
                load_textdomain( 'edd-fes-draft', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-fes-draft', false, $lang_dir );
            }
        }

        public function edd_settings_panel_add_sections( $sections ) {
            $sections['fes']['draft'] = __( 'Drafts', 'edd-fes-draft' );
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

            return $settings;
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_FES_Draft
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_FES_Draft The one true EDD_FES_Draft
 */
function edd_fes_draft() {
    return EDD_FES_Draft::instance();
}
add_action( 'plugins_loaded', 'edd_fes_draft' );


/**
 * EDD FES Draft activation hook
 *
 * @since       1.0.0
 * @return      void
 */
function edd_fes_draft_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'edd_fes_draft_activation' );