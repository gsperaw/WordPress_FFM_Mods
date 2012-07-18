<?php

//hook into the init action and call create_book_taxonomies when it fires

add_action( 'init', 'create_media_taxonomy', 0 );



//create two taxonomies, genres and writers for the post type "book"

function create_media_taxonomy() 

{

  // Add new taxonomy, make it hierarchical (like categories)

  $labels = array(

    'name' => __( 'Media Categories', 'frontfilemanager' ),

    'singular_name' => __( 'Media Category', 'frontfilemanager' ),

    'search_items' =>  __( 'Search Media Categories', 'frontfilemanager' ),

    'all_items' => __( 'All Media Categories', 'frontfilemanager' ),

    'parent_item' => __( 'Parent Media Category', 'frontfilemanager' ),

    'parent_item_colon' => __( 'Parent Media Category:', 'frontfilemanager' ),

    'edit_item' => __( 'Edit Media Category', 'frontfilemanager' ), 

    'update_item' => __( 'Update Media Category', 'frontfilemanager' ),

    'add_new_item' => __( 'Add New Media Category', 'frontfilemanager' ),

    'new_item_name' => __( 'New Media Category Name', 'frontfilemanager' ),

    'menu_name' => __( 'Media Category', 'frontfilemanager' ),

  ); 	



  register_taxonomy('mediacat',array('attachment'), array(

    'hierarchical' => true,

    'labels' => $labels,

    'show_ui' => true,

    'query_var' => true,

    'rewrite' => array( 'slug' => 'media-category' ),

  ));

}



function ffm_shortcode($atts) {

	$mime_types = get_allowed_mime_types();

	extract(shortcode_atts(array(

	  'type' => null,

	  'parent' => 0,

	  'count' => 10,

	  'downloadable' => true,

	  'pagination' => 'bottom'

	), $atts));



    $mime_type = ( in_array($type, array( 'image', 'video', 'audio', 'text', 'application' ) ) ) ? $type : $mime_types[$type];	



	// find out on which page are we

	$paging =  isset( $_GET['list'] ) && ! empty( $_GET['list'] ) ? $_GET['list'] : 1 ;

	$mcat =  isset( $_GET['mcat'] ) && ! empty( $_GET['mcat'] ) ? $_GET['mcat'] : '';



	// arguments for listed pages

	$query_args = array(

 		'posts_per_page' => $count, 'paged' => $paging, 'post_type' => 'attachment', 'post_mime_type' => $mime_type, 'post_status' => 'inherit', 'post_parent' => $parent

	);



	if ( $downloadable ) {

		$query_args['meta_key'] = '_downloadable';

		$query_args['meta_value'] = 1;

	}



	$query_args['tax_query'] = isset( $_GET['mcat'] ) && ! empty( $_GET['mcat'] ) ? array( array( 'taxonomy' => 'mediacat', 'field' => 'id', 'terms' => array( $mcat ) ) ) : '';



	$attachments = get_posts( $query_args );



	if ( $mcat ) {

		$term = get_term( $mcat, 'mediacat' );

		$return .= sprintf( __('<div id="category-notice">You are currently displaying the files from <b>%s</b> category. <a href="%s">All Categories</a></div>', 'frontfilemanager' ), $term->name, remove_query_arg( array( 'list', 'mcat' ) ) );



	}



	if ( count( $attachments ) > 0 ) {

	    $return .= '<table>

					<tr>

					<th>'.__( 'Preview', 'frontfilemanager' ).'</th>

					<th>'.__( 'Title', 'frontfilemanager' ).'</th>

					<th>'.__( 'Category', 'frontfilemanager' ).'</th>

					<th>'.__( 'Link', 'frontfilemanager' ).'</th>

					</tr>';

	    foreach ( $attachments as $post ) {

	    	$term_links = '';

	        // http://codex.wordpress.org/Function_Reference/setup_postdata

	        setup_postdata($post);

	        $return .= '<tr>

			<td'.$attributes.'>';

			if ( $thumb = wp_get_attachment_image( $post->ID, array( 80, 60 ), true ) ) {

					$return .= $thumb;

			}

			$return .= '</td>	        

	        <td style="vertical-align:middle">'.$post->post_title.'</td>

	        <td style="vertical-align:middle">';

			$terms = get_the_terms( $post->ID, 'mediacat' );

			if ( $terms )

				foreach ( $terms as $term ) {

					$link = remove_query_arg('list');

					$link = add_query_arg( 'mcat', $term->term_id, $link );

					$term_links .= '<a href="' . $link . '" rel="tag">' . $term->name . '</a> ';

				}

			$return .= $term_links . '</td>

	        <td style="vertical-align:middle"><a href="'.wp_get_attachment_url($post->ID).'">'.__('Download', 'frontfilemanager').'</a></td>

	        </tr>';

	    }

	    $return .= '</table>';

	} else {

	    $return .= '<p>No attachments!</p>';

	}



	$count_query = array(

 		'posts_per_page' => -1, 'post_type' => 'attachment', 'post_mime_type' => $mime_type, 'post_status' => 'inherit', 'post_parent' => $parent,

	);

	$count_query['tax_query'] = $query_args['tax_query'];



	if ( $downloadable ) {

		$count_query['meta_key'] = '_downloadable';

		$count_query['meta_value'] = 1;

	}

			

	// how many pages do we need?

	$all_count = count( get_posts($count_query) );

	$count_pages = ceil( $all_count / $count );



	// display the navigation

	if ( $count_pages > 0 && $all_count > $count ) {

	    $wrap_pagination = '<div>';

	    for ($i = 1; $i <= $count_pages; $i++) {

	        $separator = ( $i < $count_pages ) ? ' | ' : '';

	        // http://codex.wordpress.org/Function_Reference/add_query_arg

	        $selected = ( $i == $paging ) ? 'style="font-weight:bold;"' : '';

	        $url_args = add_query_arg( 'list', $i );

	        $wrap_pagination .=  "<a $selected href='$url_args'>$i</a>".$separator;

	    }

	    $wrap_pagination .= '</div>';

	}



	// http://codex.wordpress.org/Function_Reference/wp_reset_postdata

	wp_reset_postdata();



	$bottom = $top = '';

	if ( $pagination == 'bottom' )

		$bottom = $wrap_pagination;

	else if ( $pagination == 'top' )

		$top = $wrap_pagination;

	else

		$bottom = $top = $wrap_pagination;

		

	return $top.$return.$bottom;

}

add_shortcode('ffm-list', 'ffm_shortcode');



/**

 * This part of code was taken from PS Taxonomy Expander plugin http://wordpress.org/extend/plugins/ps-taxonomy-expander/

 */

class ffm_media {

	var $edit_post_type;

	var $disp_taxonomies;



	function PS_Taxonomy_Expander() {

		$this->__construct();

	}



	function __construct() {

		add_action( 'load-upload.php'					, array( &$this, 'get_tax_columns' ) );

		add_action( 'admin_print_styles-upload.php'		, array( &$this, 'add_tax_column_styles' ) );

		add_action( 'right_now_content_table_end'		, array( &$this, 'display_taxonomy_post_count' ) );

		add_action( 'load-media.php'					, array( &$this, 'join_media_taxonomy_datas' ) );

		add_action( 'load-media-upload.php'				, array( &$this, 'join_media_taxonomy_datas' ) );

		add_action( 'admin_menu'						, array( &$this, 'add_media_taxonomy_menu' ) );

		add_action( 'restrict_manage_posts'				, array( &$this, 'add_filter_tax_dropdown' ) );

		add_action( 'attachment_fields_to_save'			, array( &$this, 'downloadable_attachment_fields_to_save' ), 10, 2 );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )

					add_action( 'admin_init'	, array( &$this, 'get_tax_columns' ) );	



		add_filter( 'attachment_fields_to_edit'			, array( &$this, 'replace_attachement_taxonomy_input_to_check' ), 100, 2 );

		add_filter( 'posts_where' 						, array( &$this,'media_tax_posts_where' ) );		



	}

	

	function join_media_taxonomy_datas() {

		global $wp_taxonomies;



		if ( ! isset( $_POST['attachments'] ) ) { return; }

		check_admin_referer('media-form');



		$media_taxonomies = array();

		if ( $wp_taxonomies ) {

			foreach ( $wp_taxonomies as $key => $obj ) {

				if ( count( $obj->object_type ) == 1 && $obj->object_type[0] == 'attachment' ) {

					$media_taxonomies[$key] = $obj;

				}

			}

		}



		if ( $media_taxonomies ) {

			foreach ( $media_taxonomies as $key => $media_taxonomy ) {

				foreach ( $_POST['attachments'] as $attachment_id => $post_val ) {

					if ( isset( $_POST['attachments'][$attachment_id][$key] ) ) {

						if ( is_array( $_POST['attachments'][$attachment_id][$key] ) ) {

							$_POST['attachments'][$attachment_id][$key] = implode( ', ', $_POST['attachments'][$attachment_id][$key] );

						}

					} else {

						$_POST['attachments'][$attachment_id][$key] = '';

					}

				}

			}

		}

	}



	function add_media_taxonomy_menu() {

		global $wp_taxonomies, $submenu;



		$media_taxonomies = array();

		if ( $wp_taxonomies ) {

			foreach ( $wp_taxonomies as $key => $obj ) {

				if ( count( $obj->object_type ) == 1 && $obj->object_type[0] == 'attachment' && $obj->show_ui ) {

					$media_taxonomies[$key] = $obj;

				}

			}

		}



		if ( $media_taxonomies ) {

			$priority = 50;

			foreach ( $media_taxonomies as $key => $media_taxonomy ) {

				if ( current_user_can( $media_taxonomy->cap->manage_terms ) ) {

					$submenu['upload.php'][$priority] = array( $media_taxonomy->labels->menu_name, 'upload_files', 'edit-tags.php?taxonomy=' . $key );

					$priority += 5;

				}

			}

		}

	}

	

	function replace_attachement_taxonomy_input_to_check( $form_fields, $post ) {

		if ( $form_fields ) {

			foreach ( $form_fields as $taxonomy => $obj ) {

				if ( isset( $obj['hierarchical'] ) && $obj['hierarchical'] ) {

					$terms = get_terms( $taxonomy, array( 'get' => 'all' ) );

					$taxonomy_tree = array();

					$branches = array();

					$term_id_arr = array();



					foreach( $terms as $term ) {

						$term_id_arr[$term->term_id] = $term;

						if ( $term->parent == 0 ) {

							$taxonomy_tree[$term->term_id] = array();

						} else {

							$branches[$term->parent][$term->term_id] = array();

						}

					}



					if ( count( $branches ) ) {

						foreach( $branches as $foundation => $branch ) {

							foreach( $branches as $branche_key => $val ) {

								if ( array_key_exists( $foundation, $val ) ) {

									$branches[$branche_key][$foundation] = &$branches[$foundation];

									break 1;

								}

							}

						}



						foreach ( $branches as $foundation => $branch ) {

							if ( isset( $taxonomy_tree[$foundation] ) ) {

								$taxonomy_tree[$foundation] = $branch;

							}

						}

					}



					$html = $this->walker_media_taxonomy_html( $post->ID, $taxonomy, $term_id_arr, $taxonomy_tree );

					if ( $terms ) {

						$form_fields[$taxonomy]['input'] = 'checkbox';

						$form_fields[$taxonomy]['checkbox'] = $html;

					} else {

						$form_fields[$taxonomy]['input'] = 'html';

						$form_fields[$taxonomy]['html'] = sprintf( __( '%s is not registerd.', 'frontfilemanager' ), esc_html( $obj['labels']->singular_name ), esc_html( $obj['labels']->name ) );

					}

				}

			}

		}

		$form_fields["downloadable"]["label"] = __('Downloadable', 'frontfilemanager');  

		$form_fields["downloadable"]["input"] = "html";

		$checked = ( get_post_meta($post->ID, "_downloadable", true) ) ? 'checked="checked"' : '';

		$form_fields["downloadable"]["html"] = "<input type='checkbox' value='1' 

		    name='attachments[{$post->ID}][downloadable]' 

		    id='attachments[{$post->ID}][downloadable]' $checked />"; 

		return $form_fields;

	}

	

	function downloadable_attachment_fields_to_save($post, $attachment) {  

	    // $attachment part of the form $_POST ($_POST[attachments][postID])  

	    // $post attachments wp post array - will be saved after returned  

	    //     $post['post_type'] == 'attachment'  

	    if( isset($attachment['downloadable']) ){  

	        // update_post_meta(postID, meta_key, meta_value);  

	        update_post_meta( $post['ID'], '_downloadable', $attachment['downloadable'] );  

	    } else {

	    	// delete meta data

	    	delete_post_meta( $post['ID'], '_downloadable', 1 );

	    }

	    return $post;  

	}  



	function walker_media_taxonomy_html( $post_id, $taxonomy,  $term_id_arr, $taxonomy_tree, $html = '', $cnt = 0 ) {

		foreach ( $taxonomy_tree as $term_id => $arr ) {

			$checked = is_object_in_term( $post_id, $taxonomy, $term_id ) ? ' checked="checked"' : '';

			$html .= str_repeat( '—', count( get_ancestors( $term_id, $taxonomy ) ) );

			$html .= ' <input type="checkbox" id="attachments[' . $post_id . '][' . $taxonomy . ']-' . $cnt . '" name="attachments[' . $post_id . '][' . $taxonomy . '][]" value="' . esc_attr( $term_id_arr[$term_id]->name ) . '"' . $checked . ' /><label for="attachments[' . $post_id . '][' . $taxonomy . ']-' . $cnt . '">' . esc_html( $term_id_arr[$term_id]->name ) . "</label><br />\n";

			$cnt++;

			if ( count( $arr ) ) {

				$html = $this->walker_media_taxonomy_html( $post_id, $taxonomy, $term_id_arr, $arr, $html, &$cnt );

			}

		}

		return $html;

	}	



	function get_tax_columns() {		

		$taxonomies = get_object_taxonomies( 'attachment', 'object' );

		if ( ! empty( $taxonomies ) ) {

			$this->add_tax_columns = array();

			foreach ( $taxonomies as $tax_slug => $taxonomy ) {

				if ( ! in_array( $tax_slug, array( 'category', 'post_tag' ) ) && $taxonomy->show_ui !== false ) {

					$this->add_tax_columns[$tax_slug] = $taxonomy;

				}

			}

			if ( ! empty( $this->add_tax_columns ) ) {

				add_filter( 'manage_media_columns', array( &$this, 'add_tax_columns' ) );

			}

		}

	}



	function add_tax_columns( $posts_columns ) {

		if ( ! empty( $this->add_tax_columns ) ) {

			$new_columns = array();

			$add_flag = false;

			foreach ( $posts_columns as $column_name => $column_display_name ) {

				if ( in_array( $column_name, array( 'comments', 'date' ) ) && $add_flag === false ) {

					$new_columns['downloadable'] = '<img title="Downloadable" alt="Downloadable" src="'.FFM_URL.'/images/downloadable.png">';

					foreach ( $this->add_tax_columns as $tax_slug => $taxonomy ) {

						$new_columns[$tax_slug] = $taxonomy->labels->name;

						add_action( 'manage_media_custom_column', array( &$this, 'display_taxonomy_column' ), 10, 2 );

					}

					$add_flag = true;

				}

				$new_columns[$column_name] = $column_display_name;

			}

			$posts_columns = $new_columns;

		}

		return $posts_columns;

	}



	function display_taxonomy_column( $column_name, $post_id ) {

		$term_links = array();

		if ( in_array( $column_name, array_keys( $this->add_tax_columns ) ) ) {

			$terms = get_the_terms( (int)$post_id, $column_name );

			if ( ! empty( $terms ) ) {

				$term_links = array();

				foreach ( $terms as $term ) {

					$url = add_query_arg( array( 'taxonomy' => $column_name, 'term' => $term->slug ), remove_query_arg( 'paged' ) );

					$term_links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';

				}

			}

			if ( empty( $term_links ) ) {

				echo esc_html( sprintf( __( 'No %s', 'frontfilemanager' ), $this->add_tax_columns[$column_name]->labels->name ) );

			} else {

				echo implode( ', ', $term_links );

			}

		} else if ( $column_name == 'downloadable' ) {

			if ( get_post_meta($post_id, "_downloadable", true) )

				echo '<img src="'.esc_url( admin_url( 'images/yes.png' ) ).'"/>';

		}

	}



	function add_tax_column_styles() {

		if ( ! empty( $this->add_tax_columns ) ) {

			$selectors = array();

			foreach ( $this->add_tax_columns as $tax_slug => $taxonomy ) {

				$selectors[] = '.fixed .column-' . esc_html( $tax_slug );

			}

	?>

	<style type="text/css" charset="utf-8">

	<?php echo implode( ', ', $selectors ); ?> {

		width: 15%;

	}

	.fixed .column-downloadable { width: 40px; vertical-align: middle; }

	</style>

	<?php

		}

	}



	function display_taxonomy_post_count() {

		$user = wp_get_current_user();

			$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), false );

		if ( count( $taxonomies ) ) {

			foreach ( $taxonomies as $tax_slug => $taxonomy ) {

				$num = wp_count_terms( $tax_slug );

				// Ummm....

				$text = $num == 1 ? $taxonomy->labels->singular_name : $taxonomy->labels->name;

				$num = number_format_i18n( $num );

				$text = esc_html( $text );

				if ( current_user_can( $taxonomy->cap->manage_terms ) ) {

					$num = '<a href="edit-tags.php?taxonomy=' . $tax_slug . '">' . $num . '</a>';

					$text = '<a href="edit-tags.php?taxonomy=' . $tax_slug . '">' . $text . '</a>';

				}

				?>

				<tr>

					<td class="b b-<?php echo esc_attr( $tax_slug ); ?>"><a><?php echo $num; ?></a></td>

					<td class="last t"><a><?php echo $text; ?></a></td>

				</tr>

				<?php

			}

		}

	}



	function add_filter_tax_dropdown() {

		$taxonomies = get_object_taxonomies( 'attachment', 'object' );

		if ( ! empty( $taxonomies ) ) {

			foreach ( $taxonomies as $tax_slug => $taxonomy ) {

				if ( ! in_array( $tax_slug, array( 'category', 'post_tag' ) ) && $taxonomy->show_ui !== false && $taxonomy->hierarchical ) {

					$dropdown_options = array(

						'show_option_all' => sprintf( __( 'View all %s', 'frontfilemanager' ), $taxonomy->labels->name ),

						'hide_empty' => 0,

						'hierarchical' => 1,

						'show_count' => 0,

						'orderby' => 'name',

						'selected' => get_query_var( $tax_slug ),

						'taxonomy' => $tax_slug,

						'name' => $tax_slug

					);

					$this->dropdown_taxonomies( $dropdown_options );

				}

			}

		} 

		$current = isset($_GET['down'])? $_GET['down']:'';

?>

		<select name="down" id="down" :class="postform">

		 <option value="" <?php selected( $current, '' ); ?>>Show all</option>

		 <option value="1" <?php selected( $current, 1 ); ?>>Downloadable</option>

		 <option value="0" <?php selected( $current, 0 ); ?>>Not downloadable</option>

		</select>

<?php

	}

	

	function media_tax_posts_where( $where ) {

		global $wpdb, $pagenow;

		if ( is_admin() && $pagenow == 'upload.php' && isset($_GET['down']) && $_GET['down'] != '' ) {

			if ( $_GET['down'] == 1 ) {

				$where .= " AND {$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_downloadable' )";

		    	return $where;

			} else if ( $_GET['down'] == 0 ) {

		    	$where .= " AND {$wpdb->posts}.ID NOT IN ( SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_downloadable' )";

		    	return $where;

			}

		}

		return $where;

	}



	function dropdown_taxonomies( $args = '' ) {

		$defaults = array(

			'show_option_all' => '', 'show_option_none' => '',

			'orderby' => 'id', 'order' => 'ASC',

			'show_last_update' => 0, 'show_count' => 0,

			'hide_empty' => 1, 'child_of' => 0,

			'exclude' => '', 'echo' => 1,

			'selected' => 0, 'hierarchical' => 0,

			'name' => 'cat', 'id' => '',

			'class' => 'postform', 'depth' => 0,

			'tab_index' => 0, 'taxonomy' => 'category',

			'hide_if_empty' => false

		);



		$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;



		// Back compat.

		if ( isset( $args['type'] ) && 'link' == $args['type'] ) {

			_deprecated_argument( __FUNCTION__, '3.0', '' );

			$args['taxonomy'] = 'link_category';

		}



		$r = wp_parse_args( $args, $defaults );



		if ( !isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] ) {

			$r['pad_counts'] = true;

		}



		$r['include_last_update_time'] = $r['show_last_update'];

		extract( $r );



		$tab_index_attribute = '';

		if ( (int) $tab_index > 0 )

			$tab_index_attribute = " tabindex=\"$tab_index\"";



		$categories = get_terms( $taxonomy, $r );

		$name = esc_attr( $name );

		$class = esc_attr( $class );

		$id = $id ? esc_attr( $id ) : $name;



		if ( ! $r['hide_if_empty'] || ! empty($categories) )

			$output = '<select name="' . $name . '" id="' . $id . '" class="' . $class . '" ' . $tab_index_attribute . '>' . "\n";

		else

			$output = '';



		if ( empty($categories) && ! $r['hide_if_empty'] && !empty($show_option_none) ) {

			$show_option_none = apply_filters( 'list_cats', $show_option_none );

			$output .= "\t<option value='-1' selected='selected'>$show_option_none</option>\n";

		}



		if ( ! empty( $categories ) ) {



			if ( $show_option_all ) {

				$show_option_all = apply_filters( 'list_cats', $show_option_all );

				$selected = ( '' === strval($r['selected']) ) ? " selected='selected'" : '';

				$output .= "\t<option value=''$selected>$show_option_all</option>\n";

			}



			if ( $show_option_none ) {

				$show_option_none = apply_filters( 'list_cats', $show_option_none );

				$selected = ( '-1' === strval($r['selected']) ) ? " selected='selected'" : '';

				$output .= "\t<option value='-1'$selected>$show_option_none</option>\n";

			}



			if ( $hierarchical )

				$depth = $r['depth'];  // Walk the full depth.

			else

				$depth = -1; // Flat.



			$output .= $this->walk_taxonomy_dropdown_tree( $categories, $depth, $r );

		}

		if ( ! $r['hide_if_empty'] || ! empty($categories) )

			$output .= "</select>\n";





		$output = apply_filters( 'wp_dropdown_cats', $output );



		if ( $echo )

			echo $output;



		return $output;

	}



	function walk_taxonomy_dropdown_tree() {

		$args = func_get_args();

		// the user's options are the third parameter

		if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') )

			$walker = new ffm_TaxonomyDropdown;

		else

			$walker = $args[2]['walker'];



		return call_user_func_array(array( &$walker, 'walk' ), $args );

	}

} // class end

$ffm_media = new ffm_media();



class ffm_TaxonomyDropdown extends Walker_CategoryDropdown {

	function start_el( &$output, $term, $depth, $args ) {

		$pad = str_repeat('&nbsp;', $depth * 3);



		$term_name = apply_filters( 'list_cats', $term->name, $term );

		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $term->slug ) . "\"";

		if ( $term->slug == $args['selected'] )

			$output .= ' selected="selected"';

		$output .= '>';

		$output .= $pad.esc_html( $term_name );

		if ( $args['show_count'] )

			$output .= '&nbsp;&nbsp;('. $term->count .')';

		if ( $args['show_last_update'] ) {

			$format = 'Y-m-d';

			$output .= '&nbsp;&nbsp;' . gmdate($format, $term->last_update_timestamp);

		}

		$output .= "</option>\n";

	}

}

// PS Taxonomy Expander END