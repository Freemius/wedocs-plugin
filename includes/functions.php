<?php
/**
 * Get template part implementation for wedocs
 *
 * Looks at the theme directory first
 */
function wedocs_get_template_part( $slug, $name = '' ) {
    $wedocs = WeDocs::init();

    $templates = array();
    $name = (string) $name;

    // lookup at theme/slug-name.php or wedocs/slug-name.php
    if ( '' !== $name ) {
        $templates[] = "{$slug}-{$name}.php";
        $templates[] = $wedocs->theme_dir_path . "{$slug}-{$name}.php";
    }

    $template = locate_template( $templates );

    // fallback to plugin default template
    if ( !$template && $name && file_exists( $wedocs->template_path() . "{$slug}-{$name}.php" ) ) {
        $template = $wedocs->template_path() . "{$slug}-{$name}.php";
    }

    // if not yet found, lookup in slug.php only
    if ( !$template ) {
        $templates = array(
            "{$slug}.php",
            $wedocs->theme_dir_path . "{$slug}.php"
        );

        $template = locate_template( $templates );
    }

    if ( $template ) {
        load_template( $template, false );
    }
}

/**
 * Include a template by precedance
 *
 * Looks at the theme directory first
 *
 * @param  string  $template_name
 * @param  array   $args
 *
 * @return void
 */
function wedocs_get_template( $template_name, $args = array() ) {
    $wedocs = WeDocs::init();

    if ( $args && is_array($args) ) {
        extract( $args );
    }

    $template = locate_template( array(
        $wedocs->theme_dir_path . $template_name,
        $template_name
    ) );

    if ( ! $template ) {
        $template = $wedocs->template_path() . $template_name;
    }

    if ( file_exists( $template ) ) {
        include $template;
    }
}

if ( ! function_exists( 'wedocs_breadcrumbs' ) ) :

/**
 * Docs breadcrumb
 *
 * @return void
 */
function wedocs_breadcrumbs() {
    global $post;

    $args = apply_filters( 'wedocs_breadcrumbs', array(
        'delimiter' => '<li class="delimiter">&rarr;</li>',
        'home'      => __( 'Home', 'wedocs' ),
        'before'    => '<li><span class="current">',
        'after'    => '</span></li>'
    ) );

	$breadcrumb_position = 1;

    echo '<ol class="wedocs-breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">';
    echo  wedocs_get_breadcrumb($args['home'], home_url( '/' ), $breadcrumb_position);
	echo $args['delimiter'];
	$breadcrumb_position++;

    if ( $post->post_type == 'docs' && $post->post_parent ) {
        $parent_id   = $post->post_parent;
        $breadcrumbs = array();

        while ($parent_id) {
            $page          = get_post($parent_id);
            $breadcrumbs[] =  wedocs_get_breadcrumb(get_the_title($page->ID), get_permalink($page->ID), $breadcrumb_position);

            $parent_id     = $page->post_parent;
	        $breadcrumb_position++;
        }

        $breadcrumbs = array_reverse($breadcrumbs);
        for ($i = 0; $i < count($breadcrumbs); $i++) {
            echo $breadcrumbs[$i];

            if ( $i != count($breadcrumbs) - 1) {
	            echo $args['delimiter'];
            }
        }

        echo ' ' . $args['delimiter'] . ' ' . $args['before'] . get_the_title() . $args['after'];

    }

    echo '</ol>';
}

endif;

	function wedocs_get_root_doc(){
		$docs = get_children( array(
			'post_type'   => 'docs',
			'numberposts' => - 1,
		) );

		return array_values($docs)[0];
	}

	if ( ! function_exists( 'freemius_wedocs_search_breadcrumbs' ) ) :

		/**
		 * Docs search breadcrumb
		 *
		 * @return void
		 */
		function wedocs_search_breadcrumbs() {
			$args = apply_filters( 'wedocs_breadcrumbs', array(
				'delimiter' => '<li class="delimiter">&rarr;</li>',
				'home'      => __( 'Home', 'wedocs' ),
				'before'    => '<li><span class="current">',
				'after'    => '</span></li>'
			) );

			$breadcrumb_position = 1;

			echo '<ol class="wedocs-breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">';
			echo  wedocs_get_breadcrumb($args['home'], home_url( '/' ), $breadcrumb_position);
			echo $args['delimiter'];
			$breadcrumb_position++;

			$root = wedocs_get_root_doc();

			echo wedocs_get_breadcrumb(get_the_title($root->ID), get_permalink($root->ID), $breadcrumb_position);

				echo ' ' . $args['delimiter'] . ' ' . $args['before'] . __('Search: ', 'wedocs') . get_search_query() . $args['after'];

			echo '</ol>';
		}

	endif;

	if ( ! function_exists( 'wedocs_get_breadcrumb' ) ) :
/**
 * @author Vova Feldman (@svovaf)
 *
 * @param string $label
 * @param string $permalink
 * @param int $position
 *
 * @return string
 */
function wedocs_get_breadcrumb($label, $permalink, $position = 1)
{
	return '<li itemprop="itemListElement" itemscope
      itemtype="http://schema.org/ListItem">
    <a itemprop="item" href="' . esc_attr($permalink) . '">
    <span itemprop="name">' . esc_html($label) . '</span></a>
    <meta itemprop="position" content="' . $position . '" />
  </li>';
}

endif;

/**
 * Next, previous post navigation for a single doc
 *
 * @return void
 */
function wedocs_doc_nav() {
    global $post, $wpdb;

    $next_query = "SELECT ID FROM $wpdb->posts
        WHERE post_parent = $post->post_parent and post_type = 'docs' and post_status = 'publish' and menu_order > $post->menu_order
        ORDER BY menu_order ASC
        LIMIT 0, 1";

    $prev_query = "SELECT ID FROM $wpdb->posts
        WHERE post_parent = $post->post_parent and post_type = 'docs' and post_status = 'publish' and menu_order < $post->menu_order
        ORDER BY menu_order DESC
        LIMIT 0, 1";

    $next_post_id = (int) $wpdb->get_var( $next_query );
    $prev_post_id = (int) $wpdb->get_var( $prev_query );

    if ( $next_post_id || $prev_post_id ) {

        echo '<nav class="wedocs-doc-nav">';
        echo '<h3 class="assistive-text screen-reader-text">'. __( 'Doc navigation', 'wedocs' ) . '</h3>';

        if ( $prev_post_id ) {
            echo '<span class="nav-prev"><a href="' . get_permalink( $prev_post_id ) . '">&larr; ' . apply_filters( 'wedocs_translate_text', get_post( $prev_post_id )->post_title ) . '</a></span>';
        }

        if ( $next_post_id ) {
            echo '<span class="nav-next"><a href="' . get_permalink( $next_post_id ) . '">' . apply_filters( 'wedocs_translate_text', get_post( $next_post_id )->post_title ) . ' &rarr;</a></span>';
        }

        echo '</nav>';
    }
}

if ( ! function_exists( 'wedocs_get_posts_children' ) ) :

/**
 * Recursively fetch child posts
 *
 * @param  integer  $parent_id
 * @param  string  $post_type
 *
 * @return array
 */
function wedocs_get_posts_children( $parent_id, $post_type = 'page' ){
    $children = array();

    // grab the posts children
    $posts = get_posts( array(
        'numberposts'      => -1,
        'post_status'      => 'publish',
        'post_type'        => $post_type,
        'post_parent'      => $parent_id,
        'suppress_filters' => false
    ));

    // now grab the grand children
    foreach ( $posts as $child ) {
        // recursion!! hurrah
        $gchildren = wedocs_get_posts_children( $child->ID, $post_type );

        // merge the grand children into the children array
        if ( !empty($gchildren) ) {
            $children = array_merge($children, $gchildren);
        }
    }

    // merge in the direct descendants we found earlier
    $children = array_merge($children,$posts);
    return $children;
}

endif;

/**
 * Retrieve the tags for a doc formatted as a string.
 *
 * @param string $before Optional. Before tags.
 * @param string $sep Optional. Between tags.
 * @param string $after Optional. After tags.
 * @param int $id Optional. Post ID. Defaults to the current post.
 *
 * @return string|false|WP_Error A list of tags on success, false if there are no terms, WP_Error on failure.
 */
function wedocs_get_the_doc_tags( $post_id, $before = '', $sep = '', $after = '' ) {
    return get_the_term_list( $post_id, 'doc_tag', $before, $sep, $after );
}

// Check if QTranslate plugin is active before function declaration
$is_qtranslate	= wedocs_is_plugin_active( 'qtranslate-x/qtranslate.php' );
if( $is_qtranslate ) {
	/**
	 * Translate dynamic text with QTranslate X plugin
	 *
	 * @param string $text The multilingual text.
	 *
	 * @return string The translated text.
	 */
	function wedocs_translate_text_with_qtranslate( $text ){
		return apply_filters( 'translate_text', $text );
	}
	add_filter( 'wedocs_translate_text', 'wedocs_translate_text_with_qtranslate', 10, 1 );
}

/**
 * Check if a plugin is active
 *
 * @param string $plugin_path_and_name The plugin relative path and filename of the plugin main file.
 *
 * @return bool Whether the plugin is active or not.
 */
function wedocs_is_plugin_active( $plugin_path_and_name ) {

	if( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	return is_plugin_active( $plugin_path_and_name );
}

/**
 * Get FAQ tree.
 *
 * @author Vova Feldman
 *
 * @return array
 */
function wedocs_get_faq_tree() {
	$show_drafts = current_user_can( 'edit_others_pages' );

	$faq = get_posts( array(
		'post_type'      => 'docs',
		'post_status'    => $show_drafts ?
			array( 'publish', 'draft' ) :
			array( 'publish' ),
		'posts_per_page' => '-1',
		'tax_query'      => array(
			array(
				'taxonomy' => 'doc_tag',
				'field'    => 'slug',
				'terms'    => 'faq'
			)
		),
	) );

	$all_categories = get_categories( array(
		'taxonomy' => 'doc_category',
		'hide_empty' => !$show_drafts,
	) );

	$categories_by_slug = array();

	foreach ( $all_categories as $category ) {
		/**
		 * @var WP_Term $category
		 */
		$categories_by_slug[ $category->slug ] = $category;
	}

	$faq_by_category = array();

	$sorted_result = array();

	foreach ( $faq as $q ) {
		/**
		 * @var WP_Post $q
		 */
		$order = get_post_meta( $q->ID, 'faq-order', true );

		if ( empty( $order ) ) {
			$order = array();
		}

		$question_categories = wp_get_object_terms( $q->ID, array( 'doc_category' ) );

		foreach ( $question_categories as $category ) {
			if ( ! isset( $order[ "id_{$category->term_id}" ] ) ) {
				$order[ "id_{$category->term_id}" ] = 0;
			}

			if ( ! isset( $faq_by_category[ $category->slug ] ) ) {
				$faq_by_category[ $category->slug ] = array();
			}

			$faq_by_category[ $category->slug ][] = array(
				'post' => array(
					'id'     => $q->ID,
					'slug'   => $q->post_name,
					'title'  => $q->post_title,
					'status' => $q->post_status,
					'order'  => $order[ "id_{$category->term_id}" ],
					'is_faq' => true,
					'permalink' => get_home_url(null, "help/faq/{$category->slug}/{$q->post_name}/"),
				),
			);
		}
	}

	foreach ( $categories_by_slug as $slug => $category ) {
		if (empty($faq_by_category[ $slug ])){
			$faq_by_category[ $slug ] = array();
		}
		// Sort FAQs in each category.
		usort( $faq_by_category[ $slug ], 'wedocs_sort_callback' );

		$order = get_term_meta( $category->term_id, 'faq-order', true );
		if ( ! is_numeric( $order ) ) {
			$order = 0;
		}

		$sorted_result[] = array(
			'category' => array(
				'id'    => $category->term_id,
				'title' => $category->name,
				'order' => $order,
				'permalink' => get_home_url(null, "help/faq/{$category->slug}/"),
			),
			'child'    => $faq_by_category[ $slug ]
		);
	}

	// Sort categories.
	usort( $sorted_result, 'wedocs_sort_category_callback' );

	return $sorted_result;
}

/**
 * Sort callback for sorting posts with their menu order
 *
 * @param  array  $a
 * @param  array  $b
 *
 * @return int
 */
function wedocs_sort_callback( $a, $b ) {
	return $a['post']['order'] - $b['post']['order'];
}
/**
 * Sort callback for sorting posts with their menu order
 *
 * @author Vova Feldman
 *
 * @param  array  $a
 * @param  array  $b
 *
 * @return int
 */
function wedocs_sort_category_callback( $a, $b ) {
	return $a['category']['order'] - $b['category']['order'];
}