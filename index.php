<?php
/**
*Plugin Name: Quick Draft CPT
*Description: Add quick draft widgets in dashboard for custom post types. An easy setting page to choose the reuiqred custom post types.
*Version: 1.0
*Author URI: https://onemoreprince.com
*License: GPL2
 */
class Quick_Draft_CPT {
  public function __construct() {
    add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
    add_action( 'admin_init', array( $this, 'register_settings' ) );
    add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
  }

  public function add_settings_page() {
    add_options_page( 'Quick draft CPT', 'Quick draft CPT', 'manage_options', 'quick_draft_cpt', array( $this, 'settings_page' ) );
  }

  public function register_settings() {
    register_setting( 'quick_draft_cpt_group', 'quick_draft_cpt_selected' );
  }

  public function settings_page() {
    ?>
    <div class="wrap">
      <h2>Quick draft widget for CPT</h2>
      <p>Select the custom post types for which you would like to show the 'Quick Draft' widget in Dashbaord </p>
      <form method="post" action="options.php">
        <?php
          settings_fields( 'quick_draft_cpt_group' );
          $selected = get_option( 'quick_draft_cpt_selected', array() );
          $post_types = get_post_types( array( 'public' => true ), 'objects' );
          foreach ( $post_types as $post_type ) {
            if ( ! in_array( $post_type->name, array( 'post', 'page' ) ) ) {
              ?>
              <p>
                <input type="checkbox" id="quick_draft_cpt_selected_<?php echo esc_attr( $post_type->name ); ?>" name="quick_draft_cpt_selected[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $selected ) ); ?>>
                <label for="quick_draft_cpt_selected_<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->labels->name ); ?></label>
              </p>
              <?php
            }
          }
        ?>
        <p>
          <input type="submit" class="button-primary" value="Save">
        </p>
      </form>
    </div>
    <?php
  }

  public function add_dashboard_widgets() {
    $selected = get_option( 'quick_draft_cpt_selected', array() );
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    foreach ( $post_types as $post_type ) {
      if ( in_array( $post_type->name, $selected ) ) {
        wp_add_dashboard_widget( 'quick_draft_cpt_' . $post_type->name,
$post_type->labels->name, 
array( $this, 'quick_draft_widget' ), 
null, 
array( 'post_type' => $post_type->name ) 
);
      }
    }
  }

  public function quick_draft_widget( $post, $callback_args ) {
    $post_type = $callback_args['args']['post_type'];
    ?>
    <form method="post">
      <input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>">
      <p>
        <label for="title"><?php _e( 'Title' ); ?></label>
        <input type="text" id="title" name="post_title" class="widefat">
      </p>
      <p>
        <label for="content"><?php _e( 'Content' ); ?></label>
		</p>
      <textarea id="content" name="post_content" class="widefat" rows="3" style="table-layout:fixed"></textarea>
		<p>
        <?php wp_nonce_field( 'quick_draft_cpt', 'quick_draft_cpt_nonce' ); ?>
        <input type="submit" value="<?php esc_attr_e( 'Save Draft' ); ?>" class="button" name="quick_draft">
      </p>
    </form>
    <?php
    if ( isset( $_POST['quick_draft'] ) && wp_verify_nonce( $_POST['quick_draft_cpt_nonce'], 'quick_draft_cpt' ) ) {
      $selected_post_type = sanitize_text_field( $_POST['post_type'] );
      if ( $selected_post_type == $post_type ) {
        $post_title = sanitize_text_field( $_POST['post_title'] );
        $post_content = wp_kses_post( $_POST['post_content'] );
        $post_id = wp_insert_post( array(
          'post_type' => $post_type,
          'post_status' => 'draft',
          'post_title' => $post_title,
          'post_content' => $post_content,
        ) );
        if ( $post_id ) {
          printf( '<p>%s <a href="%s">%s</a></p>', __( 'Draft saved:' ), get_edit_post_link( $post_id ), $post_title );
        } else {
          _e( 'Error saving draft.' );
        }
      }
    }
  }
}

new Quick_Draft_CPT();
