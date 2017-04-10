<?php
/**
 * Admin
 *
 * @package     EDD\FES_Draft\Admin
 * @since       1.0.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_FES_Draft_Admin' ) ) {

    class EDD_FES_Draft_Admin {

        public function __construct() {
            // Decline download process
            add_action('admin_init', array( $this, 'decline_download' ) );
            add_action( 'admin_notices', array( $this, 'declined_notice'  ) );

            // Approve download
            add_action( 'fes_approve_download_admin', array( $this, 'approve_download' ) );

            // Download list actions
            add_filter( 'fes_download_table_actions', array( $this, 'download_table_actions' ), 10, 2 );

            // New meta boxes
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        }

        // New process to decline download as draft instead of trash
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

        // Notice on decline download
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

        // On approve download, if has an original download, then update the original download and remove the current one
        public function approve_download( $download_id ) {
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

        public function download_table_actions( $admin_actions, $post ) {
            if ( $post->post_status == 'pending' && current_user_can( 'publish_posts' ) ) {
                $admin_actions['decline'] = array(
                    'action' => 'declined',
                    'name' => __( 'Decline', 'edd_fes_draft' ),
                    'url' => wp_nonce_url( add_query_arg( 'decline_download', $post->ID ), 'decline_download' )
                );
            }

            return $admin_actions;
        }

        // Adds a meta box of original download
        public function add_meta_boxes() {
            global $post;

            if( $post && $post->post_type == 'download' ) {
                $original_download_id = get_post_meta( $post->ID, 'edd_fes_draft_original_download', true );

                if($original_download_id) {
                    add_meta_box(
                        'edd-fes-draft-changes',
                        'Changes',
                        array( $this, 'changes_meta_box_content' ),
                        'download',
                        'normal',
                        'high'
                    );
                }
            }
        }

        // Content of changes meta box
        public function changes_meta_box_content( $post ) {
            $original_download_id = get_post_meta( $post->ID, 'edd_fes_draft_original_download', true );

            if($original_download_id) {
                $original_download = get_post($original_download_id);

                if( ! is_wp_error( $original_download ) ) {
                    $form_id = EDD_FES()->helper->get_option( 'fes-submission-form', false );

                    // Current download form
                    $current_form = EDD_FES()->helper->get_form_by_id( $form_id, $post->ID );
                    // Original download form
                    $original_form = EDD_FES()->helper->get_form_by_id( $form_id, $original_download->ID ); ?>

                    <label>Original download:</label> <a href="<?php echo add_query_arg( array( 'action' => 'edit', 'post' => $original_download_id ), 'post.php' ); ?>">#<?php echo $original_download->ID; ?> - <?php echo $original_download->post_title; ?></a>

                    <div class="edd-fes-draft-diff">
                        <?php foreach($current_form->fields as $index => $current_field) :
                            $original_field = $original_form->fields[$index];

                            // If original field differs from current one, then show diff
                            if( $original_field->get_field_value( $original_download->ID ) !== $current_field->get_field_value( $post->ID ) ) :
                                ?><p><strong><?php echo $current_field->get_label(); ?></strong></p><?php
                                echo wp_text_diff(
                                    apply_filters('edd_fes_submissions_manager_' . $original_field->characteristics['template'] . '_output',  $original_field->formatted_data() ),
                                    apply_filters('edd_fes_submissions_manager_' . $current_field->characteristics['template'] . '_output',  $current_field->formatted_data() )
                                ); ?>

                                <?php
                            endif;
                        endforeach; ?>
                    </div>
                <?php }
            }
        }

    }

}