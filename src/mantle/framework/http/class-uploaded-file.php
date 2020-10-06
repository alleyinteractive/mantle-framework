<?php
/**
 * Uploaded_File class file.
 *
 * @package Mantle
 * @phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
 */

namespace Mantle\Framework\Http;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * Handles Uploaded Files
 */
class Uploaded_File extends SymfonyUploadedFile {
	/**
	 * Store the uploaded file on a filesystem disk.
	 *
	 * @param int          $parent_id The id of the parent post.
	 * @param  array|string $options Any options for the file.
	 * @return int Attachment ID.
	 *
	 * @throws RuntimeException Thrown on error saving uploaded file.
	 */
	public function store( int $parent_id = null, $options = [] ): int {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$filearr = [
			// Rename the file name with a random hash.
			'name'     => wp_generate_password( 15, false, false ) . '.' . $this->getClientOriginalExtension(),
			'type'     => $this->getClientMimeType(),
			'tmp_name' => $this->getPathname(),
			'error'    => $this->getError(),
			'size'     => filesize( $this->getPathname() ),
		];

		$file = \wp_handle_upload( $filearr, [ 'test_form' => false ] );

		if ( is_wp_error( $file ) ) {
			throw new RuntimeException( 'Error handling uploaded file: ' . $file->get_error_message() );
		} elseif ( ! empty( $file['error'] ) ) {
			throw new RuntimeException( 'Error handling uploaded file: ' . $file['error'] );
		}

		$title   = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
		$parent  = $parent_id >= 0 ? $parent_id : 0;
		$details = [
			'post_mime_type' => $file['type'] ?? '',
			'guid'           => $file['url'] ?? '',
			'post_parent'    => $parent,
			'post_title'     => $title,
			'post_content'   => '',
		];

		$id = \wp_insert_attachment( $details, $file, $parent );

		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( 'Error saving attachment: ' . $id->get_error_message() );
		}

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

		return $id;
	}

	/**
	 * Get the contents of the uploaded file.
	 *
	 * @return bool|string
	 *
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException When file not found.
	 */
	public function get() {
		if ( ! $this->isValid() ) {
			throw new FileNotFoundException( "File does not exist at path {$this->getPathname()}." );
		}

		return file_get_contents( $this->getPathname() ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	}

	/**
	 * Get the file's extension supplied by the client.
	 *
	 * @return string
	 */
	public function clientExtension() {
		return $this->guessClientExtension();
	}

	/**
	 * Create a new file instance from a base instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\UploadedFile $file
	 * @param  bool                                                $test
	 * @return static
	 */
	public static function createFromBase( \Symfony\Component\HttpFoundation\File\UploadedFile $file, $test = false ) {
		return $file instanceof static ? $file : new static(
			$file->getPathname(),
			$file->getClientOriginalName(),
			$file->getClientMimeType(),
			$file->getError(),
			$test
		);
	}

}
