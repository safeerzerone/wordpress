<?php
/**
 * The template for displaying archive pages.
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $wpdb;

$queried_object = get_queried_object();
$request_uri    = ! empty( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
$request_path   = trim( (string) parse_url( $request_uri, PHP_URL_PATH ), '/' );
$category_name  = get_query_var( 'category_name' );
$cat_query_var  = (int) get_query_var( 'cat' );

$CurPageId         = 0;
$CurPageSlug       = '';
$CurPageURL        = '';
$resolved_category = null;
$is_all_feeds      = false;

if ( is_category() ) {
	$resolved_category = get_queried_object();
} elseif ( $category_name ) {
	$resolved_category = get_category_by_slug( $category_name );
} elseif ( $cat_query_var ) {
	$resolved_category = get_category( $cat_query_var );
} elseif ( $request_path ) {
	$category_base = trim( (string) get_option( 'category_base' ), '/' );
	if ( '' === $category_base ) {
		$category_base = 'category';
	}

	if ( preg_match( '#^' . preg_quote( $category_base, '/' ) . '/([^/]+)/?$#', $request_path, $matches ) ) {
		$resolved_category = get_category_by_slug( $matches[1] );
	} else {
		$path_segments = explode( '/', $request_path );
		$maybe_slug    = end( $path_segments );
		if ( $maybe_slug && $maybe_slug !== $category_base && 'feeds' !== $maybe_slug ) {
			$resolved_category = get_category_by_slug( $maybe_slug );
		}
	}
}

if ( $resolved_category && ! is_wp_error( $resolved_category ) ) {
	$CurPageId   = (int) $resolved_category->term_id;
	$CurPageSlug = $resolved_category->slug;
	$CurPageURL  = get_category_link( $CurPageId );
	$category_title = $resolved_category->name;
} else {
	$category_base  = trim( (string) get_option( 'category_base' ), '/' );
	$is_feeds_page  = (
		'feeds' === $request_path
		|| ( $category_base && $request_path === $category_base )
		|| ( is_object( $queried_object ) && isset( $queried_object->post_name ) && 'feeds' === $queried_object->post_name )
	);

	if ( $is_feeds_page ) {
		$is_all_feeds   = true;
		$category_title = 'All';

		if ( is_object( $queried_object ) && isset( $queried_object->ID ) ) {
			$CurPageURL = get_permalink( $queried_object->ID );
		} else {
			$feeds_page = get_page_by_path( 'feeds' );
			$CurPageURL = $feeds_page ? get_permalink( $feeds_page->ID ) : home_url( '/feeds/' );
		}
	} else {
		$category_title = single_cat_title( '', false );
	}
}

$years        = array();
$default_year = (int) gmdate( 'Y' );

if ( $CurPageSlug ) {
	$category_post_ids = get_posts(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'category_name'          => $CurPageSlug,
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( ! empty( $category_post_ids ) ) {
		$id_list = implode( ',', array_map( 'intval', $category_post_ids ) );
		$years   = array_map(
			'intval',
			$wpdb->get_col(
				"SELECT DISTINCT YEAR(post_date) AS year
				FROM {$wpdb->posts}
				WHERE ID IN ({$id_list})
				ORDER BY year DESC"
			)
		);
	}
} elseif ( $is_all_feeds ) {
	$years = array_map(
		'intval',
		$wpdb->get_col(
			"SELECT DISTINCT YEAR(post_date) AS year
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
			AND post_type = 'post'
			ORDER BY year DESC"
		)
	);
}

if ( empty( $years ) && $CurPageId ) {
	$term_ids    = array( (int) $CurPageId );
	$child_terms = get_term_children( $CurPageId, 'category' );
	if ( ! is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
		$term_ids = array_merge( $term_ids, array_map( 'intval', $child_terms ) );
	}
	$term_ids = array_unique( $term_ids );

	$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
	$years_query  = $wpdb->prepare(
		"SELECT DISTINCT YEAR(p.post_date) AS year
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
		INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
		WHERE p.post_status = 'publish'
		AND p.post_type = 'post'
		AND tt.taxonomy = 'category'
		AND tt.term_id IN ($placeholders)
		ORDER BY year DESC",
		...$term_ids
	);

	$years = array_map( 'intval', $wpdb->get_col( $years_query ) );
}

if ( ! empty( $years ) ) {
	$default_year = max( $years );
}

$selected_year = isset( $_GET['selected_year'] ) ? (int) $_GET['selected_year'] : $default_year;

$categories = get_categories(array(
    'hide_empty' => 0
));

$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";  

$current_user_id = get_current_user_id();
$current_user_plan = get_user_meta($current_user_id, 'arm_user_last_plan', true);

?>
<main id="content" class="site-main" role="main">

	<section class="category-nav">
		<div class="container pos-r site-main">
			<div class="cat-inner">
				<div class="news-item <?php echo $is_all_feeds ? 'white-triangle-bottom' : ''; ?>">
					<a href="/feeds" class="darkGray-link">All</a>
				</div>
				<?php foreach($categories as $k=>$v){ ?>
                    <?php if($v->slug != 'uncategorized'){ ?>
				<div class="news-item">
					<a href="<?php echo get_category_link($v->term_id) ?>" class="darkGray-link <?php echo $CurPageSlug == $v->slug ? 'white-triangle-bottom':''; ?>"><?php echo $v->name; ?></a>
				</div>
                <?php } } ?>

			</div>

			<div class="absolute-nav pos-a">
				<!-- Category nav dropdown -->
				<div class="news-items-dropdown">
					
						<span class="filter-span">Filter by:</span>
						<select class="filter-select" onchange="window.location.href=this.value;" data-select2-id="1" tabindex="-1" aria-hidden="true">
							<?php foreach ( $years as $k => $v ) { ?>
							<option value="<?php echo esc_url( $CurPageURL . '?selected_year=' . $v ); ?>" <?php echo ( (int) $selected_year === (int) $v ) ? 'selected' : ''; ?>><?php echo esc_html( $v ); ?></option>
							<?php } ?>
						</select>
					
				</div>
			</div>
			
		</div>
	</section>

	
	<div class="page-content">
		<div class="page-content">
		<section class="custom-feed elementor-section elementor-top-section elementor-element elementor-element-aa950e1 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="aa950e1" data-element_type="section">
			<div class="elementor-container elementor-column-gap-default">
				<div class="elementor-column elementor-col-16 elementor-top-column elementor-element elementor-element-2ac4e0d" data-id="2ac4e0d" data-element_type="column">
					<div class="elementor-widget-wrap"></div>
				</div>
				<div class="elementor-column elementor-col-66 elementor-top-column elementor-element elementor-element-27e1c0a" data-id="27e1c0a" data-element_type="column">
					<div class="elementor-widget-wrap elementor-element-populated">
						<div class="elementor-element elementor-element-c094eb9 elementor-widget elementor-widget-heading" data-id="c094eb9" data-element_type="widget" data-widget_type="heading.default">
							<div class="elementor-widget-container">
								<h2 class="elementor-heading-title elementor-size-default">AST Feed - showing <?php echo esc_html( $category_title ); ?> from <?php echo esc_html( $selected_year ); ?></h2>
							</div>
							
						</div>
						
					</div>
					
				</div>
				<div class="elementor-column elementor-col-16 elementor-top-column elementor-element elementor-element-c7739a4" data-id="c7739a4" data-element_type="column">
					<div class="elementor-widget-wrap"></div>
				</div>
			</div>
		</section>
		
		<section class="custom-feed custom-feed-content elementor-section elementor-top-section elementor-element elementor-element-aa950e1 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="aa950e1" data-element_type="section">
			<div class="elementor-container elementor-column-gap-default">
				
				<div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-27e1c0a" data-id="27e1c0a" data-element_type="column">
					<div class="elementor-widget-wrap elementor-element-populated">
						<div class="elementor-element elementor-element-c094eb9 elementor-widget elementor-widget-heading" data-id="c094eb9" data-element_type="widget" data-widget_type="heading.default">
							
							<div class="elementor-widget-container">
								<?php the_archive_description( '<p class="archive-description">', '</p>' ); ?>
							</div>
						</div>
						
					</div>
					
				</div>
				
			</div>
		</section>

        <section class="elementor-section elementor-inner-section elementor-element elementor-element-048e54c elementor-section-boxed elementor-section-height-default elementor-section-height-default middle-section">
			<div class="elementor-container elementor-column-gap-default">
				<div class="elementor-column elementor-col-100 elementor-inner-column elementor-element elementor-element-b73834b">
					<div class="elementor-widget-wrap elementor-element-populated">
						<div class="elementor-element elementor-element-64d43de elementor-widget elementor-widget-elementskit-blog-posts">
							<div class="elementor-widget-container">
								<div class="ekit-wid-con">
									<div id="post-items--64d43de" class="row post-items">
										<?php
										
											$post_year = $selected_year;
											
											$args = array(
												'post_type'=> 'post',
												'orderby'    => 'post_date',
												'post_status' => 'publish',
												'order'    => 'DESC',
												'posts_per_page' => -1,
												'date_query' => array(
													'relation' => 'OR',
													array( 'year' => $post_year ),
												),
											);

											if ( $CurPageSlug ) {
												$args['category_name'] = $CurPageSlug;
											}

											$result = new WP_Query( $args );
											if ( $result->have_posts() ) {
										?>
										
										<?php
											while ( $result->have_posts() ) : $result->the_post();
											
											$arm_access = get_post_meta(get_the_ID(), 'arm_access_plan');
											
											$permalink = get_permalink();
										?>
										<div class="col-lg-4 col-md-6 <?php echo (empty($arm_access) || (!empty($arm_access) && $current_user_plan>0))?'':'myBtn'; ?>">

											<div class="elementskit-post-card">
												<?php if(!empty($arm_access)){ ?>
													<?php if($current_user_plan>0){ ?>
														<span class="lock">
															<i class="fas fa-unlock"></i>
														</span>
													<?php
														}else{
															$permalink = 'javascript:void(0)';
													?>
														<span class="lock">
															<i class="fas fa-lock"></i>
														</span>
													<?php } ?>
												<?php } ?>
												<div class="elementskit-entry-header">
													<div class="post-meta-list">
														<span class="meta-date">
															<i aria-hidden="true" class="icon icon-calendar3"></i>
															<span class="meta-date-text"><?php echo date('F d, Y', strtotime(get_the_date())); ?></span>
														</span>
														<?php
															$category_detail=get_the_category(get_the_ID());
															foreach($category_detail as $k=>$v){
														?>
														<span class="post-cat">
															<i aria-hidden="true" class="icon icon-folder"></i>
															<a href="<?php echo get_category_link($v->term_id); ?>" rel="category tag"><?php echo $v->name; ?></a>
														</span>
															<?php } ?>
													</div>
													<h2 class="entry-title">
														<a href="<?php echo $permalink; ?>"><?php echo get_the_title(); ?></a>
													</h2>
			
												</div><!-- .elementskit-entry-header END -->

												<div class="elementskit-post-body ">
													<div class="btn-wraper">
                                                        <a href="<?php echo $permalink; ?>" class="elementskit-btn learn-more whitespace--normal">
															Learn more <i aria-hidden="true" class="icon icon-play-button"></i>
														</a>
                                    
                                                    </div>
                                                </div><!-- .elementskit-post-body END -->
											</div>
                
										</div>
										<?php endwhile; ?>
										<?php }else{ ?>
										<p>No post found!</p>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		
		<section class="elementor-section elementor-top-section elementor-element elementor-element-49f0232 elementor-section-boxed elementor-section-height-default elementor-section-height-default">
			<div class="elementor-container elementor-column-gap-default">
				<div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-879677b" data-id="879677b" data-element_type="column">
					<div class="elementor-widget-wrap elementor-element-populated">
						<section class="elementor-section elementor-inner-section elementor-element elementor-element-a22ef8d elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="a22ef8d" data-element_type="section">
							<div class="elementor-container elementor-column-gap-default">
								<div class="elementor-column elementor-col-100 elementor-inner-column elementor-element elementor-element-a53f730" data-id="a53f730" data-element_type="column">
									<div class="elementor-widget-wrap elementor-element-populated">
										<div class="elementor-element elementor-element-6fd887f elementor-widget elementor-widget-text-editor" data-id="6fd887f" data-element_type="widget" data-widget_type="text-editor.default">
											<div class="elementor-widget-container">
												<p class=""><strong>Join us to gain full access</strong></p><p class="">Become a member from as little as £20 a year.</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</section>
		
						<section class="elementor-section elementor-inner-section elementor-element elementor-element-4697f21 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="4697f21" data-element_type="section">
							<div class="elementor-container elementor-column-gap-default">
								
								<div class="elementor-column elementor-col-100 elementor-inner-column elementor-element elementor-element-569765b" data-id="569765b" data-element_type="column">
									<div class="elementor-widget-wrap elementor-element-populated">
										<div class="elementor-element elementor-element-b64ae75 elementor-align-center elementor-widget elementor-widget-button" data-id="b64ae75" data-element_type="widget" data-widget_type="button.default">
											<div class="elementor-widget-container">
													<div class="elementor-button-wrapper">
														<a href="/checkout/" class="elementor-button-link elementor-button elementor-size-sm" role="button">
														<span class="elementor-button-content-wrapper">
															<span class="elementor-button-text">More membership options</span>
														</span>
													</a>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</section>
					</div>
				</div>
			</div>
		</section>
	</div>
	
		<?php
		/*while ( have_posts() ) {
			the_post();
			$post_link = get_permalink();
			?>
			<article class="post">
				<?php
				printf( '<h2 class="%s"><a href="%s">%s</a></h2>', 'entry-title', esc_url( $post_link ), wp_kses_post( get_the_title() ) );
				printf( '<a href="%s">%s</a>', esc_url( $post_link ), get_the_post_thumbnail( $post, 'large' ) );
				the_excerpt();
				?>
			</article>
		<?php }*/ ?>
	</div>

	<?php wp_link_pages(); ?>

	<?php
	/*global $wp_query;
	if ( $wp_query->max_num_pages > 1 ) :
		?>
		<nav class="pagination" role="navigation">
			
			<div class="nav-previous"><?php next_posts_link( sprintf( __( '%s older', 'hello-elementor' ), '<span class="meta-nav">&larr;</span>' ) ); ?></div>
			
			<div class="nav-next"><?php previous_posts_link( sprintf( __( 'newer %s', 'hello-elementor' ), '<span class="meta-nav">&rarr;</span>' ) ); ?></div>
		</nav>
	<?php endif;*/ ?>

<!-- The Modal -->
<div id="myModal" class="modal">

	<!-- Modal content -->
	
		<div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div class="modal-section mb-5">
                    <h5 class="modal-title large-body-font" id="exampleModalLabel">This is exclusive AST member content</h5>
                    <p style="margin-bottom: 30px;">Log in to continue reading</p>
                    <a href="<?php echo site_url('login') ?>" class="btn btn-primary modal-login-btn">Log in to access</a>
                </div>
                <div class="modal-section">
                    <h5 class="modal-title large-body-font" id="exampleModalLabel">Become an AST member for instant access</h5>
                    <p style="margin-bottom: 30px;">From £20 per year</p>
                    <a href="<?php echo site_url('memberships') ?>" class="btn btn-primary modal-login-btn modal-join-btn">Join us</a>
                </div>
            </div>
        </div>
	

</div>

<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementsByClassName("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
btn.onclick = function() {
  modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}

jQuery(document).ready(function(){
	jQuery('.myBtn').click(function(){
		jQuery('#myModal').show();
	})
});

</script>

</main>

<style>
	.middle-section {
		padding: 0px 0px 40px 0px;
	}
	main#content {
		background: #e1e1e1;
		width: 100%;
		max-width: 100%;
	}
    section.category-nav {
        padding: 10px 0;
        background: #fff;
    }
	section.category-nav .container {
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
    }
	section.category-nav .container .cat-inner {
        display: flex;
    }
    section.category-nav .container .cat-inner .news-item {
        padding: 10px 20px;
    }
    .absolute-nav.pos-a {
        position: absolute;
        top: 0;
        right: 0;
    }
	.news-items-dropdown {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 3px 0;
    }
    span.filter-span {
        width: 130px;
    }
    a.darkGray-link {
        color: #1d1d1d;
    }
    .white-triangle-bottom {
        position: relative;
    }
    .white-triangle-bottom:after {
        bottom: -2.4rem;
        border-left: 1rem solid transparent;
        border-right: 1rem solid transparent;
        border-top: 1rem solid #fff;
    }
    .white-triangle-bottom:after {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        -webkit-transform: translateX(-50%);
    }
    .custom-feed .elementor-heading-title {
        color: #000000 !important;
        font-family: "Montserrat", Sans-serif !important;
        font-size: 1.85rem !important;
        font-weight: 500 !important;
        text-transform: none !important;
        font-style: normal !important;
        text-decoration: none !important;
        line-height: 1.2em !important;
        letter-spacing: 0px !important;
        text-align: center;
    }

	section.custom-feed {
		padding-top: 30px;
	}
	section.custom-feed-content {
		padding-top: 0;
		padding-bottom: 30px;
	}
	.elementskit-post-card {
		background-color: #FFFFFF !important;
		border-style: solid;
		border-width: 2px 0px 0px 0px;
		border-color: #C2001F;
	}
	.elementskit-btn {
		padding: 0px 0px 0px 0px !important;
		color: #C2001F !important;
		background-color: #FFFFFF !important;

		border-radius: 5px;
		font-size: 15px;
		display: inline-block;
		position: relative;
		display: inline-block;
		line-height: 1;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
		white-space: nowrap;
		vertical-align: middle;
		text-align: center;
		-webkit-transition: all .4s ease;
		transition: all .4s ease;
	}
	.elementskit-btn:hover {
		color: #C2001F;
	}
	a.elementskit-btn:after {
		content: '';
		width: 0px;
		height: 1px;
		margin-top: 5px;
		display: block;
		background: #C2001F;
		transition: 500ms;
	}
	a.elementskit-btn:hover:after {
		width: 100%;
	}
	.elementor-element.elementor-element-49f0232:not(.elementor-motion-effects-element-type-background) {
		background-color: #173354;
		text-align: center;
	}

	.elementor-element.elementor-element-49f0232 {
		transition: background 0.3s, border 0.3s, border-radius 0.3s, box-shadow 0.3s;
		padding: 2% 0% 2% 0%;
	}
	.elementor-element.elementor-element-6fd887f {
		text-align: center;
		color: #FFFFFF;
		font-family: "Montserrat", Sans-serif;
		font-size: 16px;
		font-weight: 500;
		text-transform: none;
		font-style: normal;
		text-decoration: none;
		line-height: 0.6em;
		letter-spacing: 0px;
	}
	.elementor-element.elementor-element-b64ae75 .elementor-button:hover, .elementor-element.elementor-element-b64ae75 .elementor-button:focus {
		color: #FFFFFF;
		background-color: #90001F;
	}
	.elementor-element.elementor-element-b64ae75 .elementor-button {
		fill: #FFFFFF;
		color: #FFFFFF;
		background-color: #C2001F;
		border-radius: 50px 50px 50px 50px;
		padding: 10px 100px 10px 100px;
	}
	.post-items .col-lg-4.col-md-6 {
        margin: 20px 0;
    }
	span.lock {
		position: absolute;
		right: 30px;
		top: 15px;
	}
	
	/* The Modal (background) */
	.modal {
	  display: none; /* Hidden by default */
	  position: fixed; /* Stay in place */
	  z-index: 1; /* Sit on top */
	  padding-top: 100px; /* Location of the box */
	  left: 0;
	  top: 0;
	  width: 100%; /* Full width */
	  height: 100%; /* Full height */
	  overflow: auto; /* Enable scroll if needed */
	  background-color: rgb(0,0,0); /* Fallback color */
	  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
	}

	/* Modal Content */
	.modal-content {
	  background-color: #fefefe;
	  margin: auto;
	  padding: 20px;
	  border: 1px solid #888;
	  width: 50%;
		text-align: center;
		display: grid;
		align-items: center;
	}

	/* The Close Button */
	.close {
		float: right;
		font-size: 2.2rem;
		color: #c2001f;
		border: none;
	}

	.close:hover,
	.close:focus {
	  color: #000;
	  text-decoration: none;
	  cursor: pointer;
	  background:none;
	}
	.modal-login-btn {
		fill: #FFFFFF;
		color: #FFFFFF;
		background-color: #C2001F;
		border-radius: 50px 50px 50px 50px;
		padding: 10px 40px 10px 40px;
	}
	.modal-login-btn:hover {
		color: #fff;
		background-color: #8f0017;
		border-color: #820015;
	}
	.modal-join-btn {
		background-color: transparent;
		color: #c2001f;
		border-color: #c2001f;
		border: 1px solid;
	}
	.modal-join-btn:hover {
		background-color: #90001f;
		color: #fff;
	}
	.modal-section {
		margin: 0px 0px 50px 0px;
	}
</style>