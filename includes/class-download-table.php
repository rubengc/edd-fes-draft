<?php
/**
 * Scripts
 *
 * @package     EDD\FES_Draft\Download_Table
 * @since       1.0.1
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class EDD_FES_Draft_Download_Table {
    public function __construct() {
        add_action('admin_init', array( $this, 'decline_download' ));
        add_action( 'admin_notices', array( $this, 'declined_notice'  ) );
    }

    public function decline_download() {
        if ( !empty( $_GET['decline_download'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'decline_download' ) && current_user_can( 'edit_post', $_GET['decline_download'] ) ) {
            $post_id       = absint( $_GET['decline_download'] );

            $download_data = array(
                'ID'         => $post_id,
                'post_status' => 'draft'
            );

            if ( $post_id < 1 ){
                return;
            }

            wp_update_post( $download_data );

            $post = get_post( $post_id );

            if ( ! is_object( $post ) || is_wp_error( $post ) ){
                return;
            }

            $user = new WP_User( $post->post_author );

            if ( !is_object( $user ) || is_wp_error( $user ) ){
                return;
            }

            $to 		= apply_filters( 'fes_submission_declined_email_to', $user->user_email, $user);
            $from_name  = edd_get_option( 'from_name', get_bloginfo( 'name' ) );
            $from_email = edd_get_option( 'from_email', get_bloginfo( 'admin_email' ) );

            $subject = apply_filters( 'fes_submission_declined_message_subj', __( 'Submission Declined', 'edd_fes' ) );

            $message = EDD_FES()->helper->get_option( 'fes-vendor-submission-declined-email', '' );
            $type 	 = "post";
            $id 	 = $post->ID;
            $args['permissions'] = 'fes-vendor-submission-declined-email-toggle';
            EDD_FES()->emails->send_email( $to , $from_name, $from_email, $subject, $message, $type, $id, $args );

            do_action( 'fes_decline_download_admin', $post_id );

            wp_redirect( remove_query_arg( 'decline_download', add_query_arg( 'declined_downloads', $post_id, admin_url( 'edit.php?post_type=download' ) ) ) );
            exit;
        }
    }

    public function declined_notice() {
        global $post_type, $pagenow;
        if ( $pagenow == 'edit.php' && $post_type == 'download' && !empty( $_REQUEST['declined_downloads'] ) ) {
            $declined_downloads = $_REQUEST['declined_downloads'];
            if ( is_array( $declined_downloads ) ) {
                $declined_downloads = array_map( 'absint', $declined_downloads );
                $titles             = array();

                if ( empty( $declined_downloads ) ){
                    return;
                }

                foreach ( $declined_downloads as $download_id ){
                    $titles[] = get_the_title( $download_id );
                }
                echo '<div class="updated"><p>' . sprintf( _x( '%s declined', 'Titles of downloads declined', 'edd_fes' ), '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) . '</p></div>';
            } else {
                echo '<div class="updated"><p>' . sprintf( _x( '%s declined', 'Title of download declined', 'edd_fes' ), '&quot;' . get_the_title( $declined_downloads ) . '&quot;' ) . '</p></div>';
            }
        }
    }
}