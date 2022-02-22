<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
				$this->plugin->dir(),
				[
						'render_callback' => [ $this, 'render_callback' ],
				]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The attributes for the block.
	 * @param string $content The block content, if any.
	 * @param WP_Block $block The instance of this block.
	 *
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$class_name = $attributes['className'] ?? '';
		$post_types =  get_post_types( [ 'public' => true ], 'objects' );
		$post_types_slugs = array_keys( $post_types );
		$counts = array();
		foreach ( $post_types_slugs as $post_type ) {
			$counts[ $post_type ] = 0;
		}
		$query = new WP_Query( array(
				'post_type' => array_keys( $post_types ),
				'posts_per_page' => 100,
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
		) );
		foreach ( $query->posts as $post ) {
			$post_type = $post->post_type;
			$counts[ $post_type ]++;
		}
		ob_start();

		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php _e( 'Post Counts', 'site-counts' ) ?></h2>
			<ul>
				<?php
					foreach ( $counts as $post_type_slug => $count ) :
				?>
					<li>
						<?php printf( __( 'There are %d %s', 'site-counts' ), $count, $post_types[ $post_type_slug ]->labels->name ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p>
				<?php printf( __( 'The current post ID is %d.', 'site-counts' ), absint( $_GET['post_id'] ) ); ?>
			</p>

			<?php
			$query = new WP_Query( array(
				'post_type'     => 'post',
				'post_status'   => 'any',
				'posts_per_page' => 6,
				'tag_id' => 3,
				'cat' => 4,
				// Hard coding the tag and category IDs further optimizes the query
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			) );

			if ( $query->have_posts() ) : ?>
				<h2><?php printf( __( "%d posts with the tag of foo and the category of baz", 'site-counts' ), 5 ) ?></h2>
				<ul>
					<?php
						foreach ( $query->posts as $post ) :
							if ( $post->ID == get_the_ID() ) continue;
					?>

						<li><?php echo esc_html( $post->post_title ) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
