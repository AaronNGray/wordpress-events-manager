<?php
/*
 * This page displays a single event, called during the em_content() if this is an event page.
 * You can override the default display settings pages by copying this file to yourthemefolder/plugins/events-manager/templates/ and modifying it however you need.
 * You can display events however you wish, there are a few variables made available to you:
 * 
 * $args - the args passed onto EM_Events::output() 
 */
global $EM_Category;
/* @var $EM_Category EM_Category */
if( empty($args['id']) ) $args['id'] = rand(); // prevent warnings
$id = esc_attr($args['id']);
?>
<div class="em em-view-container" id="em-view-<?php echo $id; ?>" data-view="category">
	<div class="em-item em-item-single em-taxonomy em-taxonomy-single em-category em-category-single <?php em_template_classes('single-category'); ?> em-category-<?php echo esc_attr($EM_Category->term_id); ?>" id="em-category-<?php echo $id; ?>" data-view-id="<?php echo $id; ?>">
		<?php
		echo $EM_Category->output_single();
		?>
	</div>
</div>