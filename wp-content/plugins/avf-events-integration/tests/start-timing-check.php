<?php
/** Standalone PHP CLI checks for time labels and backward-compatible API data. */
define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );
function sanitize_title( $value ) { return $value; }
function sanitize_key( $value ) { return $value; }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function wp_timezone() { return new DateTimeZone( 'Europe/Zurich' ); }
function wp_date( $format, $timestamp, $timezone ) { return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format ); }
function __( $value, $domain = '' ) { return $value; }
class AVF_Internal_Endpoint_Resolver {
    public static function instance() { return new self(); }
}
require_once __DIR__ . '/../includes/class-avf-events-api-client.php';
require_once __DIR__ . '/../includes/class-avf-events-view-helpers.php';
require_once __DIR__ . '/../includes/class-avf-event-detail-shortcode.php';
require_once __DIR__ . '/../includes/class-avf-upcoming-events-shortcode.php';
function same( $expected, $actual ) {
    if ( $expected !== $actual ) { throw new RuntimeException( var_export( array( $expected, $actual ), true ) ); }
}
$source = array(
    'id' => 1, 'title' => 'Anlass', 'slug' => 'anlass', 'short_description' => 'Test',
    'start_at' => '2026-10-01T20:00:00+02:00', 'end_at' => '2026-10-01T23:00:00+02:00',
    'timezone_name' => 'Europe/Zurich', 'location_name' => 'Basel',
);
$normalize = new ReflectionMethod( AVF_Events_API_Client::class, 'normalize_event' );
$detail = ( new ReflectionClass( AVF_Event_Detail_Shortcode::class ) )->newInstanceWithoutConstructor();
$range = new ReflectionMethod( $detail, 'format_time_range' );
$homepage = ( new ReflectionClass( AVF_Upcoming_Events_Shortcode::class ) )->newInstanceWithoutConstructor();
$home_view = new ReflectionMethod( $homepage, 'build_event_view' );
foreach ( array( 'st' => ' s.t.', 'ct' => ' c.t.', '' => '', 'invalid' => '' ) as $code => $suffix ) {
    $event = $normalize->invoke( new AVF_Events_API_Client(), $source + array( 'start_timing' => $code ) );
    same( $source['start_at'], $event['start_at'] );
    $view = AVF_Events_View_Helpers::build_view( $event );
    same( '20:00 Uhr' . $suffix, $view['time_label'] );
    $meta = $home_view->invoke( $homepage, $event );
    same( '01.10.2026 · 20:00 Uhr' . $suffix . ' · Basel', $meta['meta'] );
    $event['start_time'] = '20:00';
    $event['end_time'] = '23:00';
    same( $suffix ? '20:00 Uhr' . $suffix . ' - 23:00 Uhr' : '20:00 - 23:00 Uhr', $range->invoke( $detail, $event ) );
    $event['end_time'] = '';
    same( '20:00 Uhr' . $suffix, $range->invoke( $detail, $event ) );
}
$legacy = $normalize->invoke( new AVF_Events_API_Client(), $source );
same( '', $legacy['start_timing'] );
same( '', AVF_Events_View_Helpers::timing_suffix( $source ) );
same( '', AVF_Events_View_Helpers::timing_suffix( array( 'start_timing' => array( 'ct' ) ) ) );
echo "Time label checks passed: s.t., c.t., unset, invalid, legacy, homepage and detail.\n";
