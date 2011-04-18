<?php 

class flgalleryWidget extends WP_Widget
{
	function flgalleryWidget()
	{
		$this->WP_Widget(
			false,
			'Flash Gallery',
			array(
				'description' => 'Global Flash Galleries'
			),
			array(
			)
		);
	}

	function widget( $args, $instance )
	{
		include FLGALLERY_GLOBALS;

		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$gallery_id = (int)$instance['gallery'];

		echo $before_widget;

		if ( !empty($title) )
			echo $before_title. $title. $after_title;

		if ( !empty($gallery_id) )
		{
			$a = array(
				'id' => $gallery_id,
				'popup' => true
			);
			if ($instance['show'] == 'preview')
				$a['preview'] = $instance['preview'];
			else
				$a['text'] = $instance['text'];

			echo
				'<div class="textwidget">'.
					$plugin->flashGallery($a).
				'</div>';
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance )
	{
		if ( empty($new_instance['text']) )
			$new_instance['text'] = __('To view the gallery in a popup window, #click here#.');

		return $new_instance;
	}

	function form( $instance )
	{
		include FLGALLERY_GLOBALS;

		$title = esc_attr($instance['title']);
		$selectedGallery = esc_attr($instance['gallery']);
		$show = esc_attr($instance['show']);
		$text = esc_attr($instance['text']);
		$preview = esc_attr($instance['preview']);
;echo '		<p>
			<label for="'; echo $this->get_field_id('title'); ;echo '">'; _e('Title:'); ;echo '</label>
			<input type="text" class="widefat" id="'; echo $this->get_field_id('title'); ;echo '" name="'; echo $this->get_field_name('title'); ;echo '" value="'; echo $title; ;echo '" />
		</p>
		<p>
			<label for="'; echo $this->get_field_id('gallery'); ;echo '">'; _e('Gallery:'); ;echo '</label>
			<select class="widefat" id="'; echo $this->get_field_id('gallery'); ;echo '" name="'; echo $this->get_field_name('gallery'); ;echo '">
';
				$galleries = $wpdb->get_results("
					SELECT *
					FROM `{$plugin->dbGalleries}`
				");
				foreach ($galleries as $gallery)
				{
;echo '					<option value="'; echo $gallery->id; ;echo '"'; if ($gallery->id == $selectedGallery) echo ' selected="selected"' ;echo '>'; echo htmlspecialchars(stripslashes($gallery->name)); ;echo '</option>
';
				}
;echo '			</select>
		</p>
		<p id="'; echo $this->get_field_id('show'); ;echo '-field">
			<label for="'; echo $this->get_field_id('show'); ;echo '">'; _e('Show:'); ;echo '</label><br />
			<label style="white-space:nowrap; margin-right:1em;">
				<input type="radio" class="radio" id="'; echo $this->get_field_id('show'); ;echo '" name="'; echo $this->get_field_name('show'); ;echo '" value="text"'; if ($show != 'preview') echo ' checked="checked"'; ;echo ' />
				'; _e('Text link'); ;echo '			</label>
			<label style="white-space:nowrap;">
				<input type="radio" class="radio" name="'; echo $this->get_field_name('show'); ;echo '" value="preview"'; if ($show == 'preview') echo ' checked="checked"'; ;echo ' />
				'; _e('Preview image'); ;echo '			</label>
		</p>
		<p id="'; echo $this->get_field_id('text'); ;echo '-field">
			<label for="'; echo $this->get_field_id('text'); ;echo '">'; _e('Text:'); ;echo '</label><br />
			<textarea class="widefat" name="'; echo $this->get_field_name('text'); ;echo '">'; echo $text; ;echo '</textarea>
		</p>
		<p id="'; echo $this->get_field_id('preview'); ;echo '-field">
			<label for="'; echo $this->get_field_id('preview'); ;echo '">'; _e('Image URL:'); ;echo '</label><br />
			<input type="text" class="widefat" id="'; echo $this->get_field_id('preview'); ;echo '" name="'; echo $this->get_field_name('preview'); ;echo '" value="'; echo $preview; ;echo '" />
		</p>
		<script type="text/javascript">//<![CDATA[
		jQuery(document).ready(function($) {
			$("#'; echo $this->get_field_id('show'); ;echo '-field input.radio").click(function() {
				if (this.value == \'preview\') {
					$("#'; echo $this->get_field_id('text'); ;echo '-field").hide();
					$("#'; echo $this->get_field_id('preview'); ;echo '-field").show();
				}
				else {
					$("#'; echo $this->get_field_id('preview'); ;echo '-field").hide();
					$("#'; echo $this->get_field_id('text'); ;echo '-field").show();
				}
			});
			$("#'; echo $this->get_field_id('show'); ;echo '-field input.radio[checked]").click();
		});
		//]]></script>
';
	}

}


?>