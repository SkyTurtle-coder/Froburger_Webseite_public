<?php
/** Offline integration checks: real API client, mocked HTTP transport only. */
set_error_handler( function ( $level, $message, $file, $line ) { throw new ErrorException( $message, 0, $level, $file, $line ); } );
require __DIR__ . '/start-timing-check.php';
require __DIR__ . '/../includes/class-avf-event-signup-handler.php';
define( 'AVF_EVENTS_SIGNUP_SHARED_SECRET', 'test-only-secret' );
class WP_Error {
    private $code; private $message; private $data;
    public function __construct( $code, $message, $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code( $response ) { return $response['status']; }
function wp_remote_retrieve_body( $response ) { return json_encode( $response['body'] ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function apply_filters( $name, $value ) { return $value; }
function get_transient( $key ) { throw new RuntimeException( 'Detail cache must never be read.' ); }
function avf_internal_api_request( $method, $path, $args ) {
    $GLOBALS['sent'] = $args;
    return $GLOBALS['response'];
}
$raw = $source + array(
    'status' => 'OFF', 'is_internal' => true, 'signup_policy' => 'members_v1',
    'description' => '', 'date' => '2026-10-01', 'start_time' => '20:00', 'location' => 'Basel',
    'signup_enabled' => true, 'signup_open' => true, 'signup_columns' => array(),
    'signups' => array( array( 'vulgo' => 'Must stay private', 'attending' => true, 'values' => array() ) ),
);
$GLOBALS['response'] = array( 'status' => 200, 'body' => $raw );
$client = new AVF_Events_API_Client();
same( array(), $client->get_event_detail( 'test' )['signups'] );
$GLOBALS['response']['body']['is_internal'] = false;
same( 1, count( $client->get_event_detail( 'test' )['signups'] ) );
$GLOBALS['response']['body']['is_internal'] = true;
unset( $GLOBALS['response']['body']['signup_policy'] );
same( true, is_wp_error( $client->get_event_detail( 'test' ) ) );
$GLOBALS['response'] = array( 'status' => 503, 'body' => array() );
same( true, is_wp_error( $client->get_event_detail( 'test' ) ) );
$GLOBALS['response'] = array( 'status' => 201, 'body' => array( 'success' => true, 'code' => 'signup_created' ) );
$_SERVER['REMOTE_ADDR'] = '192.0.2.5';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';
$client->submit_event_signup( 'test', array( 'vulgo' => 'Test', 'pin' => '1234', 'client_key' => 'forged' ) );
$decoded = json_decode( $GLOBALS['sent']['body'], true );
same( hash_hmac( 'sha256', '192.0.2.5', AVF_EVENTS_SIGNUP_SHARED_SECRET ), $decoded['client_key'] );
same( false, false !== strpos( $GLOBALS['sent']['body'], '192.0.2.5' ) );
$handler = ( new ReflectionClass( AVF_Event_Signup_Handler::class ) )->newInstanceWithoutConstructor();
$map = new ReflectionMethod( $handler, 'map_signup_error_message' );
same( 'Sorry, nur für Froburger.', $map->invoke( $handler, new WP_Error( 'denied', 'private reason', array( 'api_code' => 'member_signup_denied' ) ) ) );
echo "Member checks passed: private data, normal events, old backend, outage, signed visitor key, generic error.\n";
