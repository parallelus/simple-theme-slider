<?php
/*
Plugin Name: Simple Theme Slider
Plugin URI: http://para.llel.us
Description: A simple tool for adding custom slideshows into themes.
Author: Parallelus
Author URI: http://para.llel.us
Version: 1.0.5.1
*/

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'ST_Slider_CPT' ) ) {
	class ST_Slider_CPT {

		public function __construct() {

			$this->init();
		}

		private function init() {
			$this->setup_constants();

			add_action( 'init', array( $this, 'register_post_type' ), 100 );
			add_action( 'admin_menu', array( $this, 'change_slider_menu' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_function' ) );
			add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

			add_filter( 'manage_st-slider_posts_columns', array( $this, 'manage_posts_columns' ) );
			add_action( 'manage_st-slider_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2);

			add_action( 'init', array( $this, 'load_scripts_css' ) );
		}

		private function setup_constants() {

			// Plugin version
			if ( ! defined( 'SLIDER_VERSION' ) ) {
				define( 'SLIDER_VERSION', '1.0.0' );
			}

			// Plugin Folder Path
			if ( ! defined( 'SLIDER_PLUGIN_DIR' ) ) {
				define( 'SLIDER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL
			if ( ! defined( 'SLIDER_PLUGIN_URL' ) ) {
				define( 'SLIDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File
			if ( ! defined( 'SLIDER_PLUGIN_FILE' ) ) {
				define( 'SLIDER_PLUGIN_FILE', __FILE__ );
			}

		}

		public function manage_posts_columns( $defaults ) {

			// Add some custom columns
			$defaults['author'] = __( 'Author', 'st_slider' );

			$defaults['templ_function'] = __( 'Template Function', 'st_slider' );


			return $defaults;
		}

		public function manage_posts_custom_column( $column_name, $post_ID ) {
			if ( $column_name == 'templ_function' ) {

				echo '<code>sts_get_slider( ' . $post_ID . ' )</code>';
			}
		}

		public function register_post_type() {

			$labels = array(
				'name' 				=> _x( 'Sliders', 'post type general name', 'st_slider' ),
				'singular_name' 	=> _x( 'Slider', 'post type singular name', 'st_slider' ),
				'add_new' 			=> __( 'Add New', 'st_slider' ),
				'add_new_item' 		=> __( 'Add New Slider', 'st_slider' ),
				'edit_item' 		=> __( 'Edit Slider', 'st_slider' ),
				'new_item' 			=> __( 'New Slider', 'st_slider' ),
				'all_items' 		=> __( 'All Sliders', 'st_slider' ),
				'view_item' 		=> __( 'View Slider', 'st_slider' ),
				'search_items' 		=> __( 'Search Slider', 'st_slider' ),
				'not_found' 		=> __( 'No Slider found', 'st_slider' ),
				'not_found_in_trash'=> __( 'No Slider found in Trash', 'st_slider' ),
				'parent_item_colon' => '',
				'menu_name' 		=> __( 'Sliders', 'st_slider' )
			);

			$args = array(
				'labels'              => $labels,
				'public'              => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'query_var'           => true,
				'capability_type'     => 'post',
				'hierarchical'        => true,
				'menu_icon'           => 'dashicons-images-alt',
				'menu_position'       => null,
				'rewrite'             => array('slug' => '' /*, 'hierarchical' => false, 'with_front' => true*/ ),
				'supports'            => array( 'title' )
			);

			register_post_type( 'st-slider', $args );
		}

		function change_slider_menu() {
			// add_submenu_page( 'edit.php?post_type=st-slider', __('Settings', 'st_slider' ), __('Settings', 'st_slider' ), 'manage_options', 'st-slider-settings', array( $this, 'settings_page') );
		}

	    public function add_meta_boxes_function() {
			global $post;

	        add_meta_box(
	            'st_slider_area',
	            __( 'Add Images', 'st_slider' ),
				array( $this, 'render_meta_box_images' ),
	            'st-slider',
	            'advanced',
	            'low'
	        );

		}

	    public function render_meta_box_images() {
	    	global $post;

	    	if( $post->post_type != 'st-slider' )
	    		return 0;

			if ( ! did_action('wp_enqueue_media' ) )

				wp_enqueue_media();

			$fields = array();
			$fields = apply_filters( 'st_slider_fields', $fields );


			$meta = get_post_meta( $post->ID, 'st_slider_images' )	;
			$images = empty( $meta[0] ) ? '' : json_decode( $meta[0], true );

			$need_update = get_post_meta( $post->ID, 'update_escaping' );
			if( empty( $need_update ) && is_array( $images ) ) {

				update_post_meta( $post->ID, 'st_slider_images', json_encode( $images, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT ) );

				update_post_meta( $post->ID, 'update_escaping', 1 );
			}

			if( ! isset( $images['image'] ) ) {

				$post_id_default = $post->ID;
				if( defined( 'ICL_LANGUAGE_CODE' ) ) {

					global $sitepress;
					$default_language = $sitepress->get_default_language();
					$post_id_default = (int) apply_filters( 'wpml_object_id', $post->ID, 'st-slider', true, $default_language );

				}

				$meta = get_post_meta( $post_id_default, 'st_slider_images' );
				$images = empty($meta[0])? '' : json_decode($meta[0], true);
			}
			$image_last_number = ( isset( $images['image_last_number'] ) && ! empty( $images['image_last_number'] ) ) ? $images['image_last_number'] : 0;

			?>

				<input class="image-last-number" type="hidden" name="image_last_number"  value="<?php echo $image_last_number + 1; ?>"></input>
				<table class="form-table image-sortable" >

					<tbody>
								<tr class="image-details">
									<td colspan="2" class="no-padding">

									<?php
									if( isset( $images['source'] ) && is_array( $images['source'] ) ) :

											foreach( $images['source'] as $key => $val ): ?>


											<table class="form-table inside-box image-element" data-id="<?php echo $key; ?>">
			   									<thead class="image-header">
												    <tr>
												     	<th colspan="2"><div class="slider-row-actions"><span class="trash"><a href="#" class="remove-image deletion"><?php _e( 'Delete', 'st_slider' ); ?></a></span></div></th>

												    </tr>
												</thead>
												<tbody>
													<tr>
														<td class="image-cell">
															<p><img src="<?php echo $images['source'][$key]; ?>" width="200px" name="image_source[<?php echo $key; ?>]" id="image_source[<?php echo $key; ?>]"></p>
					        							</td>
						        						<td class="inputs-cell">
						        							<?php foreach( $fields as $field_key => $field_val ): ?>

						        									<p>
							        								<?php switch( $field_val['type'] ) {

							        											case 'text': ?>
							        												<label><?php echo $field_val['label']; ?></label>
											        								<input class="widefat" type="text" id="image_<?php echo $field_key.'['.$key.']'; ?>" name="image[<?php echo $field_key.']['.$key.']'; ?>" value="<?php echo isset( $images['image'][$field_key][$key] ) ? esc_attr( $images['image'][$field_key][$key] ) : esc_attr( $field_val['value'] ); ?>"></input> <?php

											        								break;
											        							case 'textarea': ?>
											        								<label><?php echo $field_val['label']; ?></label>
											        								<textarea class="widefat" id="image_<?php echo $field_key.'['.$key.']'; ?>" name="image[<?php echo $field_key.']['.$key.']'; ?>" rows=3><?php echo isset( $images['image'][$field_key][$key] ) ? esc_attr( $images['image'][$field_key][$key] ) : esc_attr( $field_val['value'] ); ?></textarea> <?php

											        								break;
											        							case 'checkbox': ?>
											        								<input type="checkbox" id="image_<?php echo $field_key.'['.$key.']'; ?>" name="image[<?php echo $field_key.']['.$key.']'; ?>" value="" <?php echo ! isset( $images['image'][$field_key][$key] ) ? $field_val['value'] : ( $images['image'][$field_key][$key] == 'checked' ? 'checked' : '' ); ?>>

											        								<input type="hidden" name="image[<?php echo $field_key.'_hidden_cb]['.$key.']'; ?>" value="1">
											        								<label><?php echo $field_val['label']; ?></label> <?php
											        								break;
																			} ?>
																	</p>
								        					<?php endforeach; ?>
				        									<input class="hidden-source" type="hidden" value="<?php echo $images['source'][$key]; ?>" id="hidden_source[<?php echo $key; ?>]" name="hidden_source[<?php echo $key; ?>]"></input>

								        				</td>
													</tr>
												</tbody>
											</table>

											<?php
											endforeach;
										endif;
										?>

										<div id="end-of-slides"></div>

									</td>
								</tr>

								<tr class="" style="display:none;">
									<td class="no-padding">

										<table class="form-table inside-box image-element image-template">
		   									<thead class="image-header">
											    <tr>
											     	<th colspan="2"><div class="slider-row-actions"><span class="trash"><a href="#" class="remove-image deletion"><?php _e( 'Delete', 'st_slider' ); ?></a></span></div></th>

											    </tr>
											</thead>
											<tbody>
												<tr>
													<td class="image-cell">
														<p><img src="" width="200px" class="image-source"></p>
				        							</td>
					        						<td class="inputs-cell">
						        							<?php foreach( $fields as $field_key => $field_val ): ?>

																	<p>
							        									<?php switch( $field_val['type'] ) {

							        											case 'text': ?>
							        												<label><?php echo $field_val['label']; ?></label>
											        								<input class="image-field widefat" type="text" id="image_<?php echo $field_key; ?>" name="newimage[<?php echo $field_key.']'; ?>" value="<?php echo $field_val['value']; ?>"></input> <?php
											        								break;
											        							case 'textarea': ?>
											        								<label><?php echo $field_val['label']; ?></label>
											        								<textarea class="image-field widefat" id="image_<?php echo $field_key; ?>" name="newimage[<?php echo $field_key.']'; ?>" rows=3><?php echo $field_val['value']; ?></textarea> <?php
											        								break;
											        							case 'checkbox': ?>
											        								<input class="image-field" type="checkbox" id="image_<?php echo $field_key; ?>" name="newimage[<?php echo $field_key.']'; ?>" value="" <?php echo $field_val['value']; ?>>
											        								<input class="image-field" type="hidden" name="newimage[<?php echo $field_key.'_hidden_cb]'; ?>" value="1">
											        								<label><?php echo $field_val['label']; ?></label> <?php
											        								break;
																		} ?>
																	</p>
								        					<?php endforeach; ?>
				        								<input class="hidden-source" type="hidden" value=""></input>
    													<input class="hidden-rank" type="hidden" value=""></input>
							        				</td>
												</tr>
											</tbody>
										</table>

									</td>
								</tr>

								<tr class="add-st-slide-row">
									<td>
										<a class="button-secondary add-st-slide"><span class="dashicons dashicons-plus"></span> <?php _e( 'Add Slide', 'st_slider' ); ?></a>
									</td>
								</tr>
					</tbody>
				</table> <?php
			?>
			<?php
	    }

	    public function render_meta_box_setting() {
	    	global $post;

			$meta = get_post_meta( $post->ID, 'st_slider_setting' );
			$setting = empty( $meta[0] ) ? '' : $meta[0];

			echo '<textarea name="setting" class="settings-textarea widefat" rows=5>' . $setting . '</textarea>';

	    }

	    public function save_meta_box_data( $post_id ) {
	    	global $post;

	    	$theme = wp_get_theme();
	    	$shortname = sanitize_title( $theme->get( 'Name' ) ) . '_';


			if ( isset( $_POST['post_type'] ) && $_POST['post_type'] != 'st-slider' )
				return 0;

			if( is_object( $post ) && $post->post_type != 'st-slider' )

				return 0;

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return 0;

			$fields = array();
			$fields = apply_filters( 'st_slider_fields', $fields );

			$images = array();

			if( isset( $_POST['image'] ) && is_array( $_POST['image'] ) ) {

				foreach( $_POST['image'] as $key => $val ) {

					if( is_array( $val ) ) {

						foreach( $val as $k => $v ) {

								if( strpos( $key, '_hidden_cb' ) !== false && $fields[str_replace( '_hidden_cb', '', $key )]['type'] == 'checkbox' ) {

									$cb_key = str_replace( '_hidden_cb', '', $key );

									$images['image'][$cb_key][$k] = isset( $_POST['image'][$cb_key][$k] ) ? 'checked' : '';

								} else {
									$images['image'][$key][$k] = ( $fields[$key]['type'] == 'checkbox' ) ? 'checked' : esc_attr( $v );

									if( $fields[$key]['type'] == 'text' || $fields[$key]['type'] == 'textarea' ) {

										$images['image'][$key][$k] = $v;
									}
								}
						}
					}

				}
			} elseif ( ! is_object( $post ) ) {

				return 0;
			}


			if( isset( $_POST['hidden_source'] ) && is_array( $_POST['hidden_source'] ) ) {

				foreach( $_POST['hidden_source'] as $key => $val ) {

					$source[$key] = esc_attr( $val );

				}
			} elseif ( ! is_object( $post ) ) {

				return 0;
			}

			if( isset( $_POST['hidden_rank'] ) && is_array( $_POST['hidden_rank'] ) ) {

				foreach( $_POST['hidden_rank'] as $key => $val ) {

					$rank[$key] = intval( $val );

				}
			} elseif ( ! is_object( $post ) ) {

				return 0;
			}

			$images['source'] = isset( $source ) ? $source : '';


			$images['rank'] = isset( $rank ) ? $rank : 1;

			$images['image_last_number'] = isset( $_POST['image_last_number'] ) ? $_POST['image_last_number'] : 1;

			if ( defined( 'JSON_UNESCAPED_UNICODE' ) ) {
				update_post_meta( $post_id, 'st_slider_images', json_encode( $images, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT ) );

			} else {
				update_post_meta( $post_id, 'st_slider_images', unescaped_json( $images ) );

			}
	    }

		function load_scripts_css() {

		    wp_register_style( 'slider-css', SLIDER_PLUGIN_URL . 'assets/css/slider.css' );

		    wp_enqueue_style( 'slider-css' );

		    wp_register_script( 'slider-script', SLIDER_PLUGIN_URL . 'assets/js/slider.js', array('jquery'), '', true );

		    wp_enqueue_script('slider-script');
		    // wp_register_script('jquery-ui', SLIDER_PLUGIN_URL . 'assets/js/jquery-ui-1.10.1.custom.min.js', array('jquery'), '', true); // for drag and drop sorting
		    wp_enqueue_script( 'jquery-ui' );

		}
	}
}

if ( ! function_exists( 'st_slider_load' ) ) :

function st_slider_load() {
	if( is_admin() )
		$slider = new ST_Slider_CPT();
}
add_action( 'plugins_loaded', 'st_slider_load' );
endif;


if ( ! function_exists( 'sts_get_slider_image' ) ) :

function sts_get_slider_image( $post_id ) {

	$fields = array();
	$fields = apply_filters('st_slider_fields', $fields);

	$meta = get_post_meta( $post_id, 'st_slider_images' );
	$images = empty($meta[0])? '' : json_decode($meta[0], true);
	//$images = apply_filters('wpml_translate_st_slider', $images, $fields);

	return $images;
}
endif;


if ( ! function_exists( 'sts_get_all_sliders' ) ) :

function sts_get_all_sliders() {

    $args = array(
			    'post_type' 		=> 'st-slider',

			    'posts_per_page' 	=> -1,

	 		   	'orderby' 			=> 'title',

              	'order' 			=> 'ASC',

		    );

	return new WP_Query($args);
}
endif;


if ( ! function_exists( 'sts_get_slider' ) ) :

function sts_get_slider( $post_id ) {
	$values = sts_get_slider_image( $post_id );

	$images = array();
	if( ! empty( $values['source'] ) ) {

		foreach( $values['source'] as $key => $val ) {

			$images[$key]['source'] = $val;
			if( isset( $values['image'] ) && is_array( $values['image'] ) ) {

				foreach( $values['image'] as $k => $v )

					$images[$key][$k] = $values['image'][$k][$key];
			}
		}
	}
	$images = apply_filters( 'sts_get_slider', $images );

	return $images;
}
endif;


//function wpml_translate_st_slider_func( $images, $fields ) {
//	if(defined('ICL_LANGUAGE_CODE')) {
//   		$theme = wp_get_theme();
//   		$shortname = sanitize_title($theme->get( 'Name' )).'_';
//
//		foreach($fields as $key => $field) {
//			if($field['type'] == 'text' || $field['type'] == 'textarea') {
//				foreach($images['image'][$key] as $k => $v) {
//					$images['image'][$key][$k] = apply_filters( 'wpml_translate_single_string', $v, $shortname.'wmpl-st-simple', $key.'-'.$k, ICL_LANGUAGE_CODE );
//				}
//			}
//		}
//	}
//
//	return $images;
//}
//add_filter('wpml_translate_st_slider', 'wpml_translate_st_slider_func', 10, 2);

if ( ! function_exists( 'unescaped_json' ) ) :

function unescaped_json( $arr ) {
	return preg_replace_callback(
					    '/\\\\u([0-9a-f]{4})/i',
					    function ( $matches ) {

					        $sym = mb_convert_encoding(
					                pack('H*', $matches[1]),
					                'UTF-8',
					                'UTF-16'
					                );
						    return $sym;
					    },
					    json_encode( $arr )

			);
}
endif;