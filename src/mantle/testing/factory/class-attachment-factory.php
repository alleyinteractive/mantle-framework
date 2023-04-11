<?php
/**
 * Attachment_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Closure;
use Faker\Generator;
use Mantle\Database\Model\Attachment;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use WP_Post;

use function Mantle\Support\Helpers\get_post_object;

/**
 * Attachment Factory
 *
 * @template TObject
 */
class Attachment_Factory extends Post_Factory {
	/**
	 * Faker instance.
	 *
	 * @var Generator
	 */
	protected $faker;

	/**
	 * Constructor.
	 *
	 * @param Generator $generator Faker generator.
	 */
	public function __construct( Generator $generator ) {
		$this->faker = $generator;
	}

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 * @return int|null|
	 */
	public function create( array $args = [] ): ?int {
		$args['post_type'] = 'attachment';

		return $this->make( $args, Attachment::class )?->id();
	}

	/**
	 * Create an attachment object with an underlying image file.
	 *
	 * @param string $file   The file name to create attachment object from.
	 * @param int    $parent The parent post ID.
	 * @param int    $width  The width of the image.
	 * @param int    $height The height of the image.
	 * @return static
	 */
	public function with_image( string $file = null, int $parent = 0, int $width = 640, int $height = 480 ): static {
		if ( ! $file ) {
			$file = $this->faker->image( sys_get_temp_dir(), $width, $height );
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
					if ( $mime ) {
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

				$id = $attachment instanceof Model ? $attachment->id() : $attachment;

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
