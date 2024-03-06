<?php
/**
 * Output_Style class file
 *
 * @package Mantle
 */

namespace Mantle\Console;

use Symfony\Component\Console\Style\SymfonyStyle;

use function Mantle\Support\Helpers\collect;

/**
 * Output Style
 */
class Output_Style extends SymfonyStyle {
	/**
	 * Format JSON for output.
	 *
	 * @param array $headers Headers for the data.
	 * @param array $data Data with no keys.
	 */
	public function format_json( array $headers, array $data ): void {
		// Merge the headers with the data.
		$data = collect( $data )
			->map( fn( $row ) => array_combine( $headers, $row ) )
			->to_array();

		$this->write( json_encode( $data, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}

	/**
	 * Format data for output in a CSV.
	 *
	 * @param array $headers Headers for the data.
	 * @param array $data Data with no keys.
	 */
	public function format_csv( array $headers, array $data ): void {
		// Merge the headers with the data.
		$data = collect( $data )
			->map( fn( $row ) => array_combine( $headers, $row ) )
			->to_array();

		// todo: update to write to output.
		$fp = fopen( 'php://output', 'wb' );

		fputcsv( $fp, $headers ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv

		foreach ( $data as $row ) {
			fputcsv( $fp, $row ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv
		}

		fclose( $fp );
	}

	/**
	 * Format data for output in XML format.
	 *
	 * @param array $headers Headers for the data.
	 * @param array $data Data with no keys.
	 */
	public function format_xml( array $headers, array $data ): void {
		// Merge the headers with the data.
		$data = collect( $data )
			->map( fn( $row ) => array_combine( $headers, $row ) )
			->to_array();

		$xml = new \SimpleXMLElement( '<root/>' );

		foreach ( $data as $row ) {
			$item = $xml->addChild( 'item' );

			foreach ( $row as $key => $value ) {
				$item->addChild( $key, (string) $value );
			}
		}

		$this->write( $xml->asXML() );
	}
}
