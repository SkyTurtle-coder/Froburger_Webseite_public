<?php
/** Standalone contract checks; no WordPress database or network access. */
if ( PHP_SAPI !== 'cli' ) { exit; }
define( 'ABSPATH', __DIR__ );
function add_action() {}
function add_shortcode() {}
function __( $text, $domain = '' ) { return $text; }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $text ) { return esc_attr( $text ); }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_attr( $text ); }
function esc_textarea( $text ) { return esc_attr( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function wp_kses_post( $text ) { return $text; }
function wp_unslash( $text ) { return is_array( $text ) ? array_map( 'wp_unslash', $text ) : stripslashes( $text ); }
function sanitize_text_field( $text ) { return trim( strip_tags( $text ) ); }
function sanitize_textarea_field( $text ) { return sanitize_text_field( $text ); }
function sanitize_title( $text ) { return strtolower( $text ); }
function admin_url( $path ) { return 'http://pin.test/wp-admin/' . $path; }
function is_ssl() { return false; }
function wp_create_nonce( $action ) { return 'test-nonce'; }
function wp_nonce_field( $action, $name, $referer = true ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="test-nonce">'; }
function selected( $a, $b ) { if ( (string) $a === (string) $b ) { echo 'selected'; } }
function checked( $a ) { if ( $a ) { echo 'checked'; } }
function nocache_headers() { $GLOBALS['no_cache'] = true; }
function check_ajax_referer( $action, $name ) {
    if ( ( $_POST[$name] ?? '' ) !== 'test-nonce' ) { throw new RuntimeException( 'Invalid nonce' ); }
}
class WP_Error {
    public function __construct( public $code, public $message, public $data = null ) {}
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class JsonReply extends RuntimeException {
    public function __construct( public $success, public $data, public $status ) {}
}
function wp_send_json_error( $data, $status = 200 ) { throw new JsonReply( false, $data, $status ); }
function wp_send_json_success( $data, $status = 200 ) { throw new JsonReply( true, $data, $status ); }
class AVF_Events_API_Client {
    public static $last_payload;
    public static $cleared = false;
    public function get_event_detail( $slug ) { return $GLOBALS['event']; }
    public function submit_event_signup( $slug, $payload ) {
        self::$last_payload = $payload;
        return array( 'signup' => array( 'vulgo' => 'Newton', 'attending' => true, 'values' => array( 'menu' => 'Vegi' ) ) );
    }
    public static function clear_cache() { self::$cleared = true; }
}
require dirname( __DIR__ ) . '/includes/class-avf-event-signup-handler.php';
require dirname( __DIR__ ) . '/includes/class-avf-event-detail-shortcode.php';
$event = array( 'slug' => 'pin-test', 'signup_columns' => array(
    array( 'key' => 'menu', 'label' => 'Menüwahl', 'type' => 'select', 'required' => false, 'public' => true, 'placeholder' => '', 'options' => array( 'Vegi', 'Fleisch' ) ),
    array( 'key' => 'note', 'label' => 'Bemerkung', 'type' => 'textarea', 'required' => false, 'public' => false, 'placeholder' => '', 'options' => array() ),
    array( 'key' => 'bus', 'label' => 'Busfahrt', 'type' => 'checkbox', 'required' => false, 'public' => true, 'placeholder' => '', 'options' => array() ),
) );
function ensure( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
$handler = AVF_Event_Signup_Handler::instance();
$build = new ReflectionMethod( $handler, 'build_payload' );
foreach ( array( '0123', '01234', '012345' ) as $pin ) {
    $_POST = array( 'pin' => $pin, 'vulgo' => 'Newton', 'attending' => '1' );
    $payload = $build->invoke( $handler, $event );
    ensure( ! is_wp_error( $payload ) && $payload['pin'] === $pin, 'PIN must retain leading zeroes' );
}
foreach ( array( '', '123', '1234567', '12ab', "1234\n", array( '1234' ) ) as $pin ) {
    $_POST['pin'] = $pin;
    ensure( is_wp_error( $build->invoke( $handler, $event ) ), 'Invalid PIN accepted' );
}
$_POST['pin'] = '0123';
$state = ( new ReflectionMethod( $handler, 'extract_form_state' ) )->invoke( $handler, $event );
ensure( ! isset( $state['pin'] ), 'PIN leaked to flash state' );
$_SERVER['REQUEST_METHOD'] = 'POST';
foreach ( array( 'read', 'update' ) as $operation ) {
    $_POST = array( 'event_slug' => 'pin-test', 'operation' => $operation, 'pin' => '0123', 'vulgo' => 'Newton', 'attending' => '0', '_avf_event_signup_nonce' => 'test-nonce' );
    try { $handler->handle_edit(); } catch ( JsonReply $reply ) {
        ensure( $reply->success, 'Edit proxy failed' );
    }
    ensure( AVF_Events_API_Client::$last_payload['operation'] === $operation, 'Operation missing' );
    ensure( AVF_Events_API_Client::$last_payload['pin'] === '0123', 'PIN changed' );
    ensure( ! empty( $GLOBALS['no_cache'] ), 'Missing no-cache headers' );
}
ensure( AVF_Events_API_Client::$cleared, 'Update did not invalidate public API cache' );
$_POST['_avf_event_signup_nonce'] = 'invalid';
try { $handler->handle_edit(); throw new LogicException( 'Invalid nonce accepted' ); }
catch ( RuntimeException $error ) { ensure( $error->getMessage() === 'Invalid nonce', 'Unexpected nonce result' ); }
$renderer = AVF_Event_Detail_Shortcode::instance();
$_SERVER['HTTP_HOST'] = 'pin.test';
$_SERVER['REQUEST_URI'] = '/';
$html = ( new ReflectionMethod( $renderer, 'render_signup_form' ) )->invoke( $renderer, $event, array() );
$html .= ( new ReflectionMethod( $renderer, 'render_edit_form' ) )->invoke( $renderer, $event );
preg_match_all( '/\bid="([^"]+)"/', $html, $ids );
ensure( count( $ids[1] ) === count( array_unique( $ids[1] ) ), 'Duplicate form IDs' );
ensure( substr_count( $html, 'pattern="[0-9]{4,6}"' ) === 2, 'Missing PIN constraints' );
echo in_array( '--render', $argv, true ) ? $html : "Signup PIN PHP checks passed.\n";
