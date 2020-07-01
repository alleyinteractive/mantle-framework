<?php
/**
 * Uploaded_File class file.
 *
 * @package Mantle
 * @phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
 */

namespace Mantle\Framework\Http;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
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
	 * @return string|false
	 */
	public function store( $parent_id = 0, $options = [] ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$filearray = [
			'name'     => $this->getClientOriginalName(),
			'type'     => $this->getClientMimeType(),
			'tmp_name' => $this->getPathname(),
			'error'    => $this->getError(),
			'size'     => filesize( $this->getPathname() ),
		];
		$file      = \wp_handle_upload( $filearray, [ 'test_form' => false ] );

		if ( is_wp_error( $file ) ) {
			return $file;
		}
		$url     = $file['url'];
		$type    = $file['type'];
		$file    = $file['file'];
		$title   = preg_replace( '/\.[^.]+$/', '', basename( $file ) );
		$parent  = (int) absint( $parent_id ) > 0 ? absint( $parent_id ) : 0;
		$details = [
			'post_mime_type' => $type,
			'guid'           => $url,
			'post_parent'    => $parent,
			'post_title'     => $title,
			'post_content'   => '',
		];
		$id      = \wp_insert_attachment( $details, $file, $parent );
		if ( ! is_wp_error( $id ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$data = wp_generate_attachment_metadata( $id, $file );
			wp_update_attachment_metadata( $id, $data );
		}
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

		return wpcom_vip_file_get_contents( $this->getPathname() );
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
