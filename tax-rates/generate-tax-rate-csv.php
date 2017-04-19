<?php
/**
 * This script processes the tax rate CSV that WSU provides into a
 * format expected by WooCommerce.
 *
 * Usage:
 *   `php generate-tax-rate-csv.php > woocommerce-tax-rates.csv`
 *
 * The generated woocommerce-tax-rates.csv can be imported through
 * the admin UI.
 */

// This script will only run from the command line.
if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
	die();
}

require_once( dirname( __FILE__ ) . '/parsecsv.php' );

$csv = new parseCSV();
$csv->heading = true;

/**
 * A file named "tax-rates.csv" should exist in the same directory as this script.
 *
 * Expected header row / columns: zip,start,end,rate
 */
$csv->parse( dirname( __FILE__ ) . '/tax-rates.csv' );

// Tracks a default for each zip code, without a plus 4.
$simple_zips = array();

// Define initial values.
$rate = 0;
$range_row = '';
$prev_row = '';

foreach( $csv->data as $datum ) {
	if ( '0' == $datum['start'] ) {
		$simple_zips[ $datum['zip'] ] = $datum['rate'];
	}

	if ( $datum['rate'] !== $rate ) {
		if ( ! empty( $prev_row ) ) {
			$range_row .= '...' . $datum['zip'] . str_pad( $datum['end'], 4, "0", STR_PAD_LEFT );
		}

		$calc_rate = 100 * $rate;
		if ( ! empty( $range_row ) ) {
			echo 'US,WA,' . $range_row . ',,' . $calc_rate . ',' . $calc_rate . '% Sales Tax,1,0,1,' . "\n";
		}
		$range_row = $datum['zip'] . str_pad( $datum['start'], 4, "0", STR_PAD_LEFT );
	}

	$prev_row = $datum;
	$rate = $datum['rate'];
}

foreach( $simple_zips as $zip => $rate ) {
	$calc_rate = 100 * $rate;
	echo 'US,WA,' . $zip . ',,' . $calc_rate . ',' . $calc_rate . '% Sales Tax,2,0,1,' . "\n";
}
