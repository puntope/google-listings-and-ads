<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DimensionUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * A class of utilities for dealing with images.
 *
 * @since x.x.x
 */
class ImageUtility implements Service {

	/**
	 * Try to add a new subsize image.
	 *
	 * @param int              $attachment_id Attachment ID.
	 * @param string           $subsize_key The subsize key that we are trying to generate.
	 * @param DimensionUtility $size The new size for the subsize key.
	 * @param bool             $crop Whether to crop the image.
	 *
	 * @return bool True if the subsize has been added to the attachment metadata otherwise false.
	 */
	public function try_add_subsize_image( int $attachment_id, string $subsize_key, DimensionUtility $size, bool $crop = true ): bool {
		// It is required as wp_update_image_subsizes is not loaded automatically.
		if ( ! function_exists( 'wp_update_image_subsizes' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		add_image_size( $subsize_key, $size->x, $size->y, $crop );

		$metadata = wp_update_image_subsizes( $attachment_id );

		remove_image_size( $subsize_key );

		if ( is_wp_error( $metadata ) ) {
			return false;
		}

		return isset( $metadata['sizes'][ $subsize_key ] );
	}

	/**
	 * Try to recommend a size using the real size, the recommended and the minimum.
	 *
	 * @param DimensionUtility $size Image size.
	 * @param DimensionUtility $recommended Recommended image size.
	 * @param DimensionUtility $minimum Minimum image size.
	 *
	 * @return DimensionUtility|bool False if does not fulfil the minimum size otherwise returns the suggested size.
	 */
	public function recommend_size( DimensionUtility $size, DimensionUtility $recommended, DimensionUtility $minimum ) {
		if ( ! $this->check_minimum_image_size( $size, $minimum ) ) {
			return false;
		}

		$image_ratio       = $size->x / $size->y;
		$recommended_ratio = $recommended->x / $recommended->y;

		if ( $recommended_ratio > $image_ratio ) {
			$x = $size->x > $recommended->x ? $recommended->x : $size->x;
			$y = (int) floor( $x / $recommended_ratio );
		} else {
			$y = $size->y > $recommended->y ? $recommended->y : $size->y;
			$x = (int) floor( $y * $recommended_ratio );
		}

		return new DimensionUtility( $x, $y );
	}

	/**
	 * Checks minimum image size
	 *
	 * @param DimensionUtility $size Width and height values in pixels (in that order).
	 * @param DimensionUtility $recommended Minimum width and height values in pixels (in that order).
	 *
	 * @return bool true if the image size fulfils the minimum requirements otherwise false.
	 */
	public function check_minimum_image_size( DimensionUtility $size, DimensionUtility $recommended ): bool {
		return $size->x >= $recommended->x && $size->y >= $recommended->y;
	}


}


