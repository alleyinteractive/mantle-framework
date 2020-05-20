<?php
/**
 * Attachment class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use Mantle\Framework\Contracts;

/**
 * Attachment Model
 */
class Attachment extends Post implements Contracts\Database\Core_Object, Contracts\Database\Updatable {
	/**
	 * Attachment type for the model.
	 *
	 * @var string
	 */
	public static $object_name = 'attachment';

	/**
	 * Get an attachment's URL.
	 *
	 * @param array|string $size Image URL.
	 * @return string|null
	 *
	 * @throws Model_Exception Thrown when getting image.
	 */
	public function image_url( $size ): ?string {
		if ( ! $this->id() ) {
			throw new Model_Exception( 'Unable to get attachment URL for unsaved attachment.' );
		}

		$url = wp_get_attachment_image_url( $this->id(), $size );
		return $url ?? null;
	}

	/**
	 * Get the full-size attachment URL.
	 *
	 * @return string|null
	 */
	public function url(): ?string {
		return \wp_get_attachment_url( $this->id() ) ?? null;
	}

	/**
	 * Create or get an already saved attachment from an URL address.
	 *
	 * @param string $url Image URL.
	 * @param array  $args  {
	 *        Optional. Arguments for the attachment. Default empty array.
	 *
	 *        @type string      $alt            Alt text.
	 *        @type string      $caption        Caption text.
	 *        @type string      $description    Description text.
	 *        @type array       $meta           Associate array of meta to set.
	 *                                          The value of alt text will
	 *                                          automatically be mapped into
	 *                                          this value and will be
	 *                                          overridden by the alt explicitly
	 *                                          passed into this array.
	 *        @type null|int    $parent_post_id Parent post id.
	 *        @type null|string $title          Title text. Null defaults to the
	 *                                          sanitized filename.
	 * }
	 * @return Model
	 * @throws Model_Exception Thrown on error sideloading image.
	 */
	public static function create_from_url( string $url, array $args ): Model {
		$existing = static::query()->whereMeta( '_source_url', $url )->first();

		if ( $existing ) {
			return $existing;
		}

		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$attachment_id = \media_sideload_image( $url, 0, $args['description'] ?? '', 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			throw new Model_Exception( 'Error sideloading image: ' . $attachment_id->get_error_message() );
		}

		// Update the arguments if they exist.
		if ( ! empty( array_filter( $args ) ) ) {
			$args['ID'] = $attachment_id;
			\wp_update_post( $args );
		}

		return static::find( $attachment_id );
	}

	/**
	 * Save the model.
	 *
	 * @param array $attributes Attributes to save.
	 * @return bool
	 *
	 * @throws Model_Exception Thrown on error saving.
	 */
	public function save( array $attributes = [] ) {
		$this->set_attributes( $attributes );

		$id = $this->id();

		if ( empty( $id ) ) {
			$save = \wp_insert_attachment( $this->get_attributes() );
		} else {
			$save = \wp_update_post(
				array_merge(
					$this->get_modified_attributes(),
					[
						'ID' => $id,
					]
				)
			);
		}

		if ( \is_wp_error( $save ) ) {
			throw new Model_Exception( 'Error saving model: ' . $save->get_error_message() );
		}

		// Set the attachment ID attribute.
		$this->set_raw_attribute( 'ID', $save );

		$this->reset_modified_attributes();

		return true;
	}

	/**
	 * Delete the attachment.
	 *
	 * @param bool $force Force delete the model.
	 * @return mixed
	 */
	public function delete( bool $force = false ) {
		return \wp_delete_attachment( $this->id(), $force );
	}
}
