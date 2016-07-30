<?php
// exit if direct access
if (!defined('ABSPATH')) 
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	die();
}

/**
*	Cron class. Sets constants, variables etc
*
**/


class SC_cron
{
	/**
	*
	*	Sets cron`s frequency
	*
	**/

	// cron job via wp_cron; $value is check frequency

	
	function set_cron($value)
	{ 
	  
	  if( wp_next_scheduled( 'start_auto_check' ) ) 
	  {
	    $timestamp = wp_next_scheduled('start_auto_check');
	    wp_unschedule_event($timestamp, 'start_auto_check');    
	  }
	  
	  
	  switch($value)
	  {
	    case 1: wp_schedule_event( time(), 'daily', 'start_auto_check' ); break;
	    case 2: wp_schedule_event( time(), 'twicedaily', 'start_auto_check' ); break;
	    case 24: wp_schedule_event( time(), 'hourly', 'start_auto_check' ); break;    
	  }


	}




}