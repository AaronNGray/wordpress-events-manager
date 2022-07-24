<?php /* @var $args array */ ?>
<!-- START Category Search -->
<div class="em-search-category em-search-field">
	<label for="em-search-category-<?php echo absint($args['id']) ?>" class="screen-reader-text"><?php echo esc_html($args['category_label']); ?></label>

	<select name="category[]" class="em-search-category em-selectize always-open checkboxes" id="em-search-category-<?php echo absint($args['id']) ?>" multiple size="10" placeholder="<?php echo esc_attr($args['categories_placeholder']); ?>">
		<?php
		$categories = EM_Categories::get(array('orderby'=>'name','hide_empty'=>0));
		$selected = array();
		if( !empty($args['category']) ){
			if( !is_array($args['category']) ){
				$selected = explode(',', $args['category']);
			} else {
				$selected = $args['category'];
			}
		}
		$walker = new EM_Walker_CategoryMultiselect();
		$args_em = array(
		    'hide_empty' => 0,
		    'orderby' =>'name',
		    'name' => 'category',
		    'hierarchical' => true,
		    'taxonomy' => EM_TAXONOMY_CATEGORY,
		    'selected' => $selected,
		    'show_option_none' => $args['categories_label'],
		    'option_none_value'=> 0,
			'walker'=> $walker
		);
		echo walk_category_dropdown_tree($categories, 0, $args_em);
		?>
	</select>
</div>
<!-- END Category Search -->