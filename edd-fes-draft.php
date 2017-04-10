<?php
/**
 * Plugin Name:     EDD FES Draft
 * Plugin URI:      https://wordpress.org/plugins/edd-fes-draft/
 * Description:     Adds draft submissions to Easy Digital Downloads Frontend Submissions plugin
 * Version:         1.0.3
 * Author:          Tsunoa
 * Author URI:      https:/tsunoa.com
 * Text Domain:     edd-fes-draft
 *
 * @package         EDD\FES_Draft
 * @author          tsunoa
 * @copyright       Copyright (c) tsunoa
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
         * @var         EDD_FES_Draft_Admin EDD FES Draft admin
         * @since       1.0.3
         */
        protected $admin;

        /**
         * @var         EDD_FES_Draft_Admin EDD FES Draft functions
         * @since       1.0.3
         */
        protected $functions;

        /**
         * @var         EDD_FES_Draft_Admin EDD FES Draft scripts
         * @since       1.0.3
         */
        protected $scripts;

        /**
         * @var         EDD_FES_Draft_Admin EDD FES Draft settings
         * @since       1.0.3
         */
        protected $settings;


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
            // plugin version
            define( 'EDD_FES_DRAFT_VER', '1.0.3' );

            // plugin folder url
            define( 'EDD_FES_DRAFT_URL', plugin_dir_url( __FILE__ ) );

            // plugin folder path
            define( 'EDD_FES_DRAFT_DIR', plugin_dir_path( __FILE__ ) );

            // plugin root file
            define('EDD_FES_DRAFT_FILE', __FILE__);
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            require_once EDD_FES_DRAFT_DIR . 'includes/admin.php';
            require_once EDD_FES_DRAFT_DIR . 'includes/functions.php';
            require_once EDD_FES_DRAFT_DIR . 'includes/scripts.php';
            require_once EDD_FES_DRAFT_DIR . 'includes/settings.php';

            $this->admin = new EDD_FES_Draft_Admin();
            $this->functions = new EDD_FES_Draft_Functions();
            $this->scripts = new EDD_FES_Draft_Scripts();
            $this->settings = new EDD_FES_Draft_Settings();
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

        // Without posibility to override or extend this function of EDD FES, so added to allow an easy override of product list actions
        // Template: fes_templates/frontend-products.php
        // Usage: edd_fes_draft()->product_list_actions( $product_id );
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