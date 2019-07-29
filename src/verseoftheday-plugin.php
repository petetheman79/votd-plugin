<?php
/*
Plugin Name: Verse Of The Day Plugin
Plugin URI: https://github.com/petetheman79/votd-plugin
Description: A Verse Of The Day Widget that will fetch a daily bible verse from YouVersion.
Version: 1.1
Author: Peter Solomon
Author URI: https://github.com/petetheman79
License: GPL3
 */

if (!function_exists('add_action')) {
    echo 'Forbidden - You cannot cant access this file';
    exit;
}

class VerseOfTheDayWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'verseofthedaywidget', __('YouVersion Verse Of The Day', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'apikey' => '',
            'versionId' => 1,
            'showImage' => true,
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

        <?php
        if ($apikey) {
            $versions = $this->getVersions($apikey);
        ?>
            <p>
            <label for="<?php echo esc_attr($this->get_field_id('versionId')); ?>"><?php _e('Version:', 'text_domain');?></label>
            <select
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('versionId')); ?>"
                name="<?php echo esc_attr($this->get_field_name('versionId')); ?>"
            >

            <?php foreach ($versions["data"] as $key => $value) {
                echo "<option value='$value[id],$value[abbreviation]'";

                if ($value["id"] == $versionId) {
                    echo "selected";
                }

                echo ">";
                echo $value["abbreviation"];
                echo "</option>";
            }
            ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('showImage')); ?>"><?php _e('Show Image:', 'text_domain');?></label>
            <input type=checkbox
                id="<?php echo esc_attr($this->get_field_id('showImage')); ?>"
                name="<?php echo esc_attr($this->get_field_name('showImage')); ?>"
                <?php if ($showImage == true) {
                echo "checked";
            }
            ?>
            />
        </p>

    <?php 
        }
    }

    // Update widget settings
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['apikey'] = isset($new_instance['apikey']) ? wp_strip_all_tags($new_instance['apikey']) : '';
        $versionId = isset($new_instance['versionId']) ? wp_strip_all_tags($new_instance['versionId']) : '';
        $version = explode(',', $versionId);

        $instance['versionId'] = isset($version[0]) ? wp_strip_all_tags($version[0]) : 1;
        $instance['versionAbbreviation'] = isset($version[1]) ? wp_strip_all_tags($version[1]) : 'KJV';

        $instance['showImage'] = isset($new_instance['showImage']) ? true : false;
        return $instance;
    }

    // Display the widget
    public function widget($args, $instance)
    {
        extract($args);

        $title = 'YouVersion Verse of the day';

        // Get the YouVersion Api Key & Version Id
        $apikey = isset($instance['apikey']) ? $instance['apikey'] : '';
        $versionId = isset($instance['versionId']) && strlen($instance['versionId']) > 0 ? $instance['versionId'] : 1;
        $abbreviatiton = isset($instance['versionAbbreviation']) ? '(' . $instance['versionAbbreviation'] . ')' : '';
        $showImage = isset($instance['showImage']) && $instance['showImage'] == 1 ? true : false;

        echo $before_widget;

        if ($title) {
            echo $before_title . $title . $after_title;
        }

        if ($apikey) {
            $dayOfTheYear = date('z') + 1;

            $votd = $this->getVOTD($apikey, $dayOfTheYear, $versionId);

            if (isset($votd["image"]) && $showImage) {
                $imgUrl = str_replace('{width}x{height}', '310x310', $votd["image"]["url"]);
                echo '<img src=' . $imgUrl . '>';
                echo '<p><small>Image: ' . $votd["image"]["attribution"] . '</small></p>';
            }

            if ($votd["verse"]) {
                echo '<p><a href=' . $votd["verse"]["url"] . ' target="_blank"><b>' . $votd["verse"]["human_reference"] . ' ' . $abbreviatiton . ' </b></a><br>';
                echo $votd["verse"]["text"] . '</p>';
            }
        }

        echo $after_widget;
    }

    private function getVersions($apikey)
    {
        $url = "https://developers.youversionapi.com/1.0/versions";

        $json = $this->getRequest($apikey, $url);

        return $json;
    }

    private function getVOTD($apikey, $dayOfTheYear, $versionId)
    {
        $url = "https://developers.youversionapi.com/1.0/verse_of_the_day/$dayOfTheYear?version_id=$versionId";

        $json = $this->getRequest($apikey, $url);
        return $json;
    }

    private function getRequest($apikey, $url)
    {
        $hostName = gethostname();

        $userAgent = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31';

        $headers = array(
            "x-youversion-developer-token: " . $apikey,
            "Accept: application/json",
            "Referer: " . $hostName,
            "User-Agent: " . $userAgent,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);

        return $json;
    }
}

// Register the widget
function my_register_votd_widget()
{
    register_widget('VerseOfTheDayWidget');
}

add_action('widgets_init', 'my_register_votd_widget');
