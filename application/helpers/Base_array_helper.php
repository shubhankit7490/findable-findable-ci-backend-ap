<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

if ( ! function_exists( 'is_assoc' ) ) {
	/**
	 * Is Associative
	 *
	 * Checks if the array is associative array.
	 *
	 * @param    array
	 *
	 * @return    bool
	 */
	function is_assoc( $array ) {
		if ( array() === $array ) {
			return false;
		}

		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}
}