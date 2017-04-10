<?php
/**
 * Functions
 *
 * @package     EDD\FES_Draft\Functions
 * @since       1.0.3
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_FES_Draft_Functions' ) ) {

    class EDD_FES_Draft_Functions {

        public function __construct() {
            // Frontend submission
            add_filter( 'fes_render_submission_form_frontend_fields', array( $this, 'render_frontend_fields' ), 10, 4 );
            add_filter( 'fes_save_submission_form_frontend_values', array( $this, 'save_frontend_values' ), 10, 3 );
            add_filter( 'fes_after_submission_form_save_frontend', array( $this, 'after_save_frontend' ), 10, 4 );

            // Ajax auto save
            add_action( 'wp_ajax_edd_fes_draft_auto_save', array( $this, 'ajax_auto_save' ) );
        }

        // EDD FES Save draft button
        function render_frontend_fields( $fields, $form, $user_id, $readonly ) {
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
        public function save_frontend_values( $args, $form, $save_id ) {
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
        public function after_save_frontend( $output, $save_id, $values, $user_id ) {
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
                $post->post_status = ( ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-submissions', false ) ) ? 'pending' : 'publish';

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

        /**
         * Ajax request for auto save
         *
         * @since       1.0.0
         * @return      void
         */
        public function ajax_auto_save( ) {
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

    }

}