<?php


/*
 * Plugin Name: Event Organiser Extended Booking Form
 * Description: Extends the booking form
 * Plugin URI: https://github.com/BartzikWebdesign/wp-event-organiser-extended-booking-form
 * Author: Bartzik Webdesign // BARTZIK.NET
 * Author URI: http://www.barzik.net/
 * Version: 1.0.0
 * License: GNU General Public License, version 3 (GPLv3)
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: event-organiser-extended-booking-form
 * Domain Path: /languages
 */


/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;


/* Load plugin text domain */
function eoebf_load_plugin_textdomain() {
    load_plugin_textdomain( 'event-organiser-extended-booking-form', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'eoebf_load_plugin_textdomain' );


/* Update-Checker */
require plugin_dir_path( __FILE__ ) . 'inc/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/BartzikWebdesign/wp-event-organiser-extended-booking-form',
	__FILE__,
	'event-organiser-extended-booking-form'
);
$myUpdateChecker->setBranch('master');


/* Adding the Attendee Questions */
function my_attach_attendee_questions( $form ){
	
    //Define the field we want to add    
    $attendee_fields = array(
        array(
            'id'   => 'attendee-name',
            'type' => 'name',
            'required' => true,
			'label' => __('Attendee name', 'event-organiser-extended-booking-form')
        ),
        array(
            'id'   => 'attendee-birthdate',
            'type' => 'date',
            'required' => false,
			'label' => __('Attendee birthdate', 'event-organiser-extended-booking-form')
        ),
        array(
            'id'   => 'attendee-membership-number',
            'type' => 'input',
            'required' => false,
			'label' => __('Attendee membership number (e.g. professional association / Verkehrswacht Siegerland-Wittgenstein e. V.)', 'event-organiser-extended-booking-form')
        ),
    );

    //The attendee-questions element acts as a 'holder' for the elements 
    //we want repeated for each ticket
    $attendee_questions = $form->get_element( 'attendee-questions' );

    //If it doesn't exist, we'll create it
    if ( ! $attendee_questions ) {

        $ticketpicker = $form->get_element( 'ticketpicker' );
        $position     = intval( $ticketpicker->get( 'position' ) ) + 1;
        $parent       = $ticketpicker->get_parent();
        $parent       = ( $parent ? $parent->id : false );

        //Create the attendee questions els
        $attendee_questions = EO_Booking_Form_Element_Factory::create(array(
            'id'           => 'attendee-questions',
            'type'         => 'attendee-questions',
            'elements'     => $attendee_fields,
            'ticket_label' => "",
        ));

        //Add the attendee questions right after the ticket picker
        $form->add_element( $attendee_questions, array( 'at' => $position, 'parent' => $parent ) );
		

    } else {
        //Attendee questions field already exists, optional: over-ride with our elements
        $attendee_questions->set( 'elements', $attendee_fields );
        $attendee_questions->_modelElements->set( $attendee_questions->get('elements'), array( 'form' => $form ) );
    }

}
add_action( 'eventorganiser_get_event_booking_form', 'my_attach_attendee_questions', 5 );




/* Add data to csv exports */
add_filter( 'eventorganiser_export_tickets_headers', function( $columns ) {
    $columns['ticket_first_name'] = __('Ticket Holder (first name)', 'event-organiser-extended-booking-form');
    $columns['ticket_last_name'] = __('Ticket Holder (last name)', 'event-organiser-extended-booking-form');
    $columns['ticket_birthdate'] = __('Ticket Holder birthdate', 'event-organiser-extended-booking-form');
    $columns['ticket_membership_number'] = __('Ticket Holder membership number', 'event-organiser-extended-booking-form');
    return $columns;
} );
add_filter( 'eventorganiser_export_tickets_cell', function( $cell, $column, $ticket ) {

    switch( $column ){

        case 'ticket_first_name':
            $name = eo_get_booking_ticket_meta( $ticket->booking_ticket_id, '_eo_booking_meta_attendee-name' ); //_eo_booking_meta_{field id}
            return $name[0]; //$name is an array: array( 'first name', 'last name' );
            break;

        case 'ticket_last_name':
            $name = eo_get_booking_ticket_meta( $ticket->booking_ticket_id, '_eo_booking_meta_attendee-name' ); //_eo_booking_meta_{field id}
            return $name[1]; //$name is an array: array( 'first name', 'last name' );
            break;

        case 'ticket_birthdate':
            return eo_get_booking_ticket_meta( $ticket->booking_ticket_id, '_eo_booking_meta_attendee-birthdate', true ); //_eo_booking_meta_{field id}
            break;

        case 'ticket_membership_number':
            return eo_get_booking_ticket_meta( $ticket->booking_ticket_id, '_eo_booking_meta_attendee-membership-number', true ); //_eo_booking_meta_{field id}
            break;

        default:
            return $cell;
    }

}, 10, 3 );



/* Adding columns to the tickets admin table */
add_filter( 'eventorganiser_booking_tickets_table', function( $columns ){
    $columns['name'] = __('Ticket Holder', 'event-organiser-extended-booking-form');;
	$columns['birthdate'] = __('Ticket Holder birthdate', 'event-organiser-extended-booking-form');
	$columns['membership-number'] = __('Ticket Holder membership number', 'event-organiser-extended-booking-form');
    return $columns;
});
add_action( 'eventorganiser_booking_tickets_table_column', function( $column_name, $item ){
    if( 'name' == $column_name ){
        $name = (array) eo_get_booking_ticket_meta( $item->booking_ticket_id, '_eo_booking_meta_attendee-name', false );
        echo implode( ' ', $name );
    }
	if( 'birthdate' == $column_name ){
        $birthdate = eo_get_booking_ticket_meta( $item->booking_ticket_id, '_eo_booking_meta_attendee-birthdate', true );
        echo $birthdate;
    }
	if( 'membership-number' == $column_name ){
        $membership_number = eo_get_booking_ticket_meta( $item->booking_ticket_id, '_eo_booking_meta_attendee-membership-number', true );
        echo $membership_number;
    }
},10,2);

?>