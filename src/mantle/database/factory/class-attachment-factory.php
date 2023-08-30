<?php
/**
 * Attachment_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Closure;
use Faker\Generator;
use Mantle\Contracts\Database\Core_Object;
use Mantle\Database\Model\Attachment;
use RuntimeException;
use WP_Post;

use function Mantle\Support\Helpers\get_post_object;

/**
 * Attachment Factory
 *
 * @template TObject of \Mantle\Database\Model\Attachment
 */
class Attachment_Factory extends Post_Factory {
	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string
	 */
	protected string $model = Attachment::class;

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return [
			'post_type' => 'attachment',
		];
	}

	/**
	 * Create an attachment object with an underlying image file.
	 *
	 * @throws RuntimeException If unable to generate image.
	 *
	 * @param string $file   The file name to create attachment object from.
	 * @param int    $parent The parent post ID.
	 * @param int    $width  The width of the image.
	 * @param int    $height The height of the image.
	 * @param bool   $recycle Whether to recycle the image file.
	 * @return static
	 */
	public function with_image( string $file = null, int $parent = 0, int $width = 640, int $height = 480, bool $recycle = true ): static {
		if ( ! $file ) {
			static $generated_images = [];

			$hash = md5( $width . $height );

			// If we're recycling, and we've already generated an image of this size, use it.
			if ( $recycle && isset( $generated_images[ $hash ] ) ) {
				$file = $generated_images[ $hash ];
			}

			// If we're not recycling, or we haven't generated an image of this size,
			// generate one and save it for later.
			if ( ! $file ) {
				$file = $generated_images[ $hash ] = $this->faker->image( get_temp_dir(), $width, $height );
			}
		}

		if ( empty( $file ) ) {
			throw new RuntimeException( "Unable to generate {$width}x{$height} image." );
		}

		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $file, $parent ) {
				if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
					require_once ABSPATH . 'wp-admin/includes/image.php';
				}

				$contents = file_get_contents( $file ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
				$upload   = wp_upload_bits( wp_basename( $file ), null, $contents );

				$type = '';

				if ( ! empty( $upload['type'] ) ) {
					$type = $upload['type'];
				} else {
					$mime = wp_check_filetype( $upload['file'] );

					if ( ! empty( $mime['type'] ) ) {
						$type = $mime['type'];
					}
				}

				$args = array_merge(
					$args,
					[
						'file'           => $file,
						'post_title'     => wp_basename( $upload['file'] ),
						'post_content'   => '',
						'post_type'      => 'attachment',
						'post_parent'    => $parent,
						'post_mime_type' => $type,
						'guid'           => $upload['url'],
					],
				);

				// Create the underlying attachment.
				$attachment = $next( $args );

				$id = $attachment instanceof Core_Object ? $attachment->id() : $attachment;

				update_attached_file( $id, $upload['file'] );
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

				return $attachment;
			}
		);
	}

	/**
	 * Saves an attachment.
	 *
	 * @deprecated Use the `with_image()` method instead.
	 *
	 * @param string $file   The file name to create attachment object for.
	 * @param int    $parent ID of the post to attach the file to.
	 *
	 * @return int|\WP_Error The attachment ID on success. The value 0 or WP_Error on failure.
	 */
	public function create_upload_object( $file, $parent = 0 ): int|\WP_Error {
		return $this->with_image( $file, $parent )->create();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return Attachment|WP_Post|int|null
	 */
	public function get_object_by_id( int $object_id ): Attachment|WP_Post|int|null {
		return $this->as_models
			? Attachment::find( $object_id )
			: get_post_object( $object_id );
	}
}
