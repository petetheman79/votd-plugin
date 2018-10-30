<?php
/*
Plugin Name: YouVersion Verse Of The Day Plugin
Plugin URI: https://github.com/petetheman79/verseoftheday-plugin/
Description: A Verse Of The Day Widget that will fetch a daily bible verse from YouVersion.
Version: 1.0
Author: Peter Solomon
Author URI: https://github.com/petetheman79
License: GPL3
 */

if (!function_exists('add_action')) {
    echo 'Forbidden - You cannot cant access this file';
    exit;
}

class YouVersionVerseOfTheDayWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'youversionverseofthedaywidget', __('YouVersion Verse Of The Day', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'apikey' => '',
        );

        extract(wp_parse_args((array) $instance, $defaults));?>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('apikey')); ?>"><?php _e('ApiKey:', 'text_domain');?></label>
			<input
				class="widefat"
				id="<?php echo esc_attr($this->get_field_id('apikey')); ?>"
				name="<?php echo esc_attr($this->get_field_name('apikey')); ?>"
				type="text"
				value="<?php echo esc_attr($apikey); ?>" />

			<small>Get an ApiKey from YouVersion: <a href='https://developers.youversion.com/' target="_blank">https://developers.youversion.com/</a></small>
		</p>

	<?php }

    // Update widget settings
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['apikey'] = isset($new_instance['apikey']) ? wp_strip_all_tags($new_instance['apikey']) : '';
        return $instance;
    }

    // Display the widget
    public function widget($args, $instance)
    {
        extract($args);

        $title = 'YouVersion Verse of the day';

        // Get the YouVersion Api Key
        $apikey = isset($instance['apikey']) ? $instance['apikey'] : '';

        echo $before_widget;

        if ($title) {
            echo $before_title . $title . $after_title;
        }

        if ($apikey) {
            $dayOfTheYear = date('z') + 1;
            $hostName = gethostname();

            $userAgent = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31';

            $headers = array(
                "x-youversion-developer-token: " . $apikey,
                "Accept: application/json",
                "Referer: " . $hostName,
                "User-Agent: " . $userAgent,
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://developers.youversionapi.com/1.0/verse_of_the_day/$dayOfTheYear?version_id=1");
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $votd = json_decode($response, true);

            if ($votd["image"]) {
                $imgUrl = str_replace('{width}x{height}', '310x310', $votd["image"]["url"]);
                echo '<img src=' . $imgUrl . '>';
                echo '<p><small>Image: ' . $votd["image"]["attribution"] . '</small></p>';
            }

            if ($votd["verse"]) {
                echo '<p><a href=' . $votd["verse"]["url"] . ' target="_blank"><b>' . $votd["verse"]["human_reference"] . ' (KJV)</b></a><br>';
                echo $votd["verse"]["text"] . '</p>';
            }

        }

        echo $after_widget;
    }
}

// Register the widget
function my_register_votd_widget()
{
    register_widget('YouVersionVerseOfTheDayWidget');
}

add_action('widgets_init', 'my_register_votd_widget');
