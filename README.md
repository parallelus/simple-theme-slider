# Simple Theme Slider
A simple WP admin UI for users to create slides. The theme author can define the admin fields which appear for each
slide and then retrieve the data and output it with their own HTML and JS structure.

To specify the admin input fileds available, define them in your theme or plugin with the `st_slider_fields` filter. 
This gives the theme author control over the options for each slide specific to the design of their theme's slide show.

<h3>Define slide fields</h3>
An example of defining fields for your theme:

```
#-----------------------------------------------------------------
# Filters for plugin: Simple Theme Slider
#-----------------------------------------------------------------
/**
 * Plugin: Simple Theme Slider
 * Define fields available for each slide.
 *
 * Specify the default field name, value and lable creating a new
 * array instance for each input. These inputs will appear as the
 * options for each slide created in the admin for the plugin.
 *
 * Field types: text, textarea and checkbox
 *
 * Example:
 *
 * 	$fields[{field_name}] = array(
 * 		'type'  => {field_type},
 * 		'label' => {label_text},
 * 		'value' => {default_value}
 * 	);
 */
if ( ! function_exists( 'theme_simple_slider_input_fields' ) ) :
function theme_simple_slider_input_fields( $fields = array() ) {

	// Text (Title)
	$fields['title'] = array(
		'type'  => 'text',  // the field type
		'label' => __('Title', 'framework'), // the label
		'value' => '',      // the default value
	);
	// Textarea (Description)
	$fields['description'] = array(
		'type'  => 'textarea',
		'label' => __('Description', 'framework'),
		'value' => '',
	);
	// Text (URL)
	$fields['slide-link'] = array(
		'type'  => 'text',
		'label' => __('Link URL', 'framework'),
		'value' => '',
	);
	// Checkbox (Open in new window)
	$fields['open-new-window'] = array(
		'type'  => 'checkbox',
		'label' => __('Open in New Window', 'framework'),
		'value' => 'checked',
	);

	return $fields;
}
add_filter('st_slider_fields', 'theme_simple_slider_input_fields' );
endif;
```

<h3>Add slides to a template</h3>
Output the slider by specifying the ID for the slide show for example this could be included in a theme template file:

```
$id = 123;
$simple_slider = sts_get_slider( $id );
if ( is_array($simple_slider) && !empty($simple_slider) ) { 

	// Gather together the slides data
	$all_slides = array();
	foreach ($simple_slider as $index => $slide) {
		// Title
		$all_slides[$index]['title'] = (isset($slide['title'])) ? $slide['title'] : '';

		// Description
		$all_slides[$index]['description'] = (isset($slide['description'])) ? $slide['description'] : '';

		// Image
		$all_slides[$index]['image'] = (isset($slide['source'])) ? $slide['source'] : '';
		if ( !empty($all_slides[$index]['image']) ) : 
			// the image with CSS
			$all_slides[$index]['image'] = 'background-image: url('.$all_slides[$index]['image'].')'; 
		endif; 

		// Link
		$all_slides[$index]['link']   = (isset($slide['slide-link'])) ? $slide['slide-link'] : '';
		$all_slides[$index]['target'] = (isset($slide['open-new-window']) && $slide['open-new-window'] == 'checked') ? '_blank' : '';
	}


	// We have slides, let's show them!
	if ( !empty($all_slides) ) {
		?>
		<div class="slider">
			<?php
			foreach ($all_slides as $key => $value) {

				// check for a link target (like a new window)
				$target = '';
				if (isset($value['target']) && !empty($value['target'])) {
					$target = 'target="'. esc_attr($value['target']) .'"';
				}
				?>
				<div class="item">
					<div class="bg-img" style="<?php echo esc_attr($value['image']) ?>">
						<article>
							<h3><?php echo wp_kses_post($value['title']) ?></h3>
							<p class="lead"><?php echo wp_kses_post($value['description']) ?></p>
							<a href="<?php echo esc_url($value['link']) ?>" <?php echo $target; ?>><?php _e('Read More', 'st_slider') ?></a>
						</article>
					</div>
				</div>
				<?php
			} ?>
		</div>
		<?php
	} // end !empty($all_slides)
} // end is_array($simple_slider)
```

This plugin only creates the slides UI in the WP admin. The code above gives theme authors controls to set the fields 
and output the data. There is no JavaScript included to create a slider effect. This leaves that entirely up to the author 
of the theme to choose the scripts and sources for this functionality which best suit their needs.
