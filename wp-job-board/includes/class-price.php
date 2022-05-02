<?php
/**
 * Price
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Price {
	
	/**
	 * Formats price
	 *
	 * @access public
	 * @param $price
	 * @return bool|string
	 */
	public static function format_price( $price ) {
		if ( empty( $price ) || ! is_numeric( $price ) ) {
			return false;
		}

		$price = WP_Job_Board_Mixes::format_number( $price );

		$currency_index = 0;
		$symbol = wp_job_board_get_option('currency_symbol', '$');
		$currency_position = wp_job_board_get_option('currency_position', 'before');

		$currency_symbol = ! empty( $symbol ) ? $symbol : '$';
		$currency_show_symbol_after = $currency_position == 'after' ? true : false;

		if ( ! empty( $currency_symbol ) ) {
			if ( $currency_show_symbol_after ) {
				$price = '<span class="price-text">'.$price.'</span>' . $currency_symbol;
			} else {
				$price = $currency_symbol . '<span class="price-text">'.$price.'</span>';
			}
		}

		return $price;
	}

	public static function format_price_without_html( $price ) {
		if ( empty( $price ) || ! is_numeric( $price ) ) {
			return false;
		}

		$price = WP_Job_Board_Mixes::format_number( $price );

		$currency_index = 0;
		$symbol = wp_job_board_get_option('currency_symbol', '$');
		$currency_position = wp_job_board_get_option('currency_position', 'before');

		$currency_symbol = ! empty( $symbol ) ? $symbol : '$';
		$currency_show_symbol_after = $currency_position == 'after' ? true : false;

		if ( ! empty( $currency_symbol ) ) {
			if ( $currency_show_symbol_after ) {
				$price = $price . $currency_symbol;
			} else {
				$price = $currency_symbol . $price;
			}
		}

		return $price;
	}
}
