<?PHP
/*
  Plugin Name: IBS Events
  Plugin URI: http://wordpress.org/extend/plugins/
  Description: Adds post type IBS Event which is an optional event source for IBS Calendar..
  Author: HMoore71
  Version: 1.0
  Author URI: http://indianbendsolutions.net
  License: GPL2
  License URI: none
 */

/*
  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
define('IBS_EVENTS_VERSION', '1.0');
register_activation_hook(__FILE__, array('IBS_EVENTS', 'defaults'));
register_activation_hook(__FILE__, array('IBS_EVENTS', 'custom_post_type'));

register_deactivation_hook(__FILE__, 'ibs_events_deactivate');

function ibs_events_deactivate() {
    delete_option('ibs_events_options');
}

class IBS_EVENTS {

    static $options = array();
    static $options_defaults = array(
        "version" => IBS_EVENTS_VERSION,
        "debug" => false,
        "ui_theme" => "cupertino",
        "post" => false,
        "firstDay" => "1",
        "titleFormat" => "MMM DD, YYYY",
        "timeFormat" => "HH:mm",
        "timeZone" => "local",
        "list" => array(
            "repeats" => false,
            "width" => "100%",
            "align" => "alignleft",
            "dateFormat" => "MMM DD, YYYY",
            "timeFormat" => "HH:mm",
            "max" => 100,
            "calendarId" => '',
            "apiKey" => '',
            "descending" => false,
            "start" => 'now',
            "qtip" => array('style' => "qtip-bootstrap", 'rounded' => false, 'shadow' => false)
        )
    );
    static $add_script = 0;

    static function extendA($a, &$b) {
        foreach ($a as $key => $value) {
            if (!isset($b[$key])) {
                $b[$key] = $value;
            }
            if (is_array($value)) {
                self::extendA($value, $b[$key]);
            }
        }
    }

    static function fixBool(&$item, $key) {
        switch (strtolower($item)) {
            case "null" : $item = null;
                break;
            case "true" :
            case "yes" : $item = true;
                break;
            case "false" :
            case "no" : $item = false;
                break;
            default :
        }
    }

    static function init() {
        self::$options = get_option('ibs_events_options');
        if (isset(self::$options['version']) === false || self::$options['version'] !== IBS_EVENTS_VERSION) {
            self::defaults();
        } else {
            self::extendA(self::$options_defaults, self::$options);
            array_walk_recursive(self::$options, array(__CLASS__, 'fixBool'));
        }
        add_action('manage_ibs_event_posts_custom_column', array(__CLASS__, 'custom_columns_content'), 10, 2);
        add_action('save_post', array(__CLASS__, 'save_event_info'));
        add_filter('manage_edit-ibs_event_columns', array(__CLASS__, 'custom_columns_head'), 10);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_script_style'));
        add_action('add_meta_boxes_ibs_event', array(__CLASS__, 'add_event_info_metabox'));
        add_action('init', array(__CLASS__, 'custom_post_type'));
        if (self::$options['post']) {
            add_filter('pre_get_posts', array(__CLASS__, 'in_home_loop'));
        }

        add_action('admin_init', array(__CLASS__, 'admin_options_init'));
        add_action('admin_menu', array(__CLASS__, 'admin_add_page'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts')); //*****************************
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
        add_action('init', array(__CLASS__, 'register_script'));
        add_action('wp_head', array(__CLASS__, 'print_script_header'));
        add_action('wp_footer', array(__CLASS__, 'print_script_footer'));
        add_action('admin_print_scripts', array(__CLASS__, 'print_admin_scripts'));
        add_action('wp_ajax_ibs_events_get_events', array(__CLASS__, 'get_ibs_events'));
        add_action('wp_ajax_nopriv_ibs_events_get_events', array(__CLASS__, 'get_ibs_events'));
        add_shortcode('ibs-list-events', array(__CLASS__, 'handle_shortcode'));
    }

    static function defaults() { //jason_encode requires double quotes
        $options = get_option('ibs_events_options');
        self::extendA(self::$options_defaults, $options);
        array_walk_recursive($options, array(__CLASS__, 'fixBool'));
        $options['version'] = IBS_EVENTS_VERSION;
        self::$options = $options;
        update_option('ibs_events_options', $options);
    }

    //add ibs_events to home loop for posting on "home" page
    static function in_home_loop($query) {
        if (is_home() && $query->is_main_query()) {
            $query->set('post_type', array('post', 'ibs_event'));
        }
        return $query;
    }

    static function custom_post_type() {
        $labels = array(
            'name' => __('IBS Events', 'ibs'),
            'singular_name' => __('IBS Event', 'ibs'),
            'add_new_item' => __('Add New IBS Event', 'ibs'),
            'all_items' => __('All', 'ibs'),
            'edit_item' => __('Edit IBS Event', 'ibs'),
            'new_item' => __('New', 'ibs'),
            'view_item' => __('View IBS Event', 'ibs'),
            'not_found' => __('No IBS Events Found', 'ibs'),
            'not_found_in_trash' => __('No IBS Events Found in Trash', 'ibs')
        );

        $supports = array(
            'title',
            'editor',
            'author',
            'excerpt',
            'revisions',
            'custom-fields',
            'post-tag',
            'post-formats'
        );

        $args = array(
            'label' => __('IBS Events', 'ibs'),
            'labels' => $labels,
            'description' => __('A list of IBS Events', 'ibs'),
            'public' => true,
            'show_in_menu' => true,
            'menu_icon' => plugins_url('css/document.png', __FILE__),
            'has_archive' => true,
            'rewrite' => true,
            'supports' => $supports,
            'taxonomies' => array('category', 'post_tag')
        );

        register_post_type('ibs_event', $args);
        flush_rewrite_rules();
    }

    static function add_event_info_metabox() {
        add_meta_box('ibs-event-info-metabox', __('Event Schedule', 'ibs'), array(__CLASS__, 'render_event_info_metabox'), 'ibs_event', 'normal', 'core');
    }

    static function custom_columns_head($defaults) {
        unset($defaults['date']);
        $defaults['event_start'] = __('Start', 'ibs');
        $defaults['event_end'] = __('End', 'ibs');
        $defaults['event_allday'] = __('All day', 'ibs');
        $defaults['event_repeats'] = __('Repeats', 'ibs');
        return $defaults;
    }

    static $core_handles = array(
        'jquery',
        'json2',
        'jquery-ui-core',
        'jquery-ui-dialog',
        'jquery-ui-datepicker',
        'jquery-ui-widget'
    );

    static function admin_script_style($hook) {
        global $post_type;
        if (( 'post.php' == $hook || 'post-new.php' == $hook ) && ( 'ibs_event' == $post_type )) {

            wp_enqueue_script(self::$core_handles);

            $theme = self::$options['ui_theme'];
            $min = self::$options['debug'] ? '' : '.min';
            wp_enqueue_style('ibs-calendar-ui-theme-style', plugins_url("css/jquery-ui-themes-1.11.1/themes/$theme/jquery-ui.min.css", __FILE__));

            wp_enqueue_script('ibs-moment-script', plugins_url("js/moment$min.js", __FILE__));

            wp_enqueue_script('rrule-rrule-script', plugins_url("js/rrule$min.js", __FILE__));

            wp_enqueue_style('ibs-timepicker-style', plugins_url('css/ibs-timepicker.css', __FILE__));
            wp_enqueue_script('ibs-timepicker-script', plugins_url("js/ibs-timepicker$min.js", __FILE__));

            wp_enqueue_style('ibs-events-style', plugins_url('css/events.css', __FILE__));
            wp_enqueue_script('ibs-events-script', plugins_url("js/events$min.js", __FILE__));
        }
    }

    static function render_event_info_metabox($post) {
        wp_nonce_field(basename(__FILE__), 'ibs-event-nonce');

        $event_start = get_post_meta($post->ID, 'ibs-event-start', true);
        $event_start = empty($event_start) ? 0 : $event_start;

        $event_end = get_post_meta($post->ID, 'ibs-event-end', true);
        $event_end = empty($event_end) ? $event_start : $event_end;

        $event_allday = get_post_meta($post->ID, 'ibs-event-allday', true);
        $event_allday = empty($event_allday) ? false : $event_allday;

        $event_color = get_post_meta($post->ID, 'ibs-event-color', true);
        $event_color = empty($event_color) ? '#5484ed' : $event_color;

        $event_repeat = get_post_meta($post->ID, 'ibs-event-repeat', true);

        $event_recurr = get_post_meta($post->ID, 'ibs-event-recurr', true);
        $event_recurr = empty($event_recurr) ? false : $event_recurr;

        $event_exceptions = get_post_meta($post->ID, 'ibs-event-exceptions', true);

        $args = get_option('ibs_events_options');
        include('lib/event-html.php');
    }

    static function save_event_info($post_id) {
        if (!isset($_POST['post_type']) || 'ibs_event' != $_POST['post_type']) {
            return;
        }
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = ( isset($_POST['ibs-event-nonce']) && ( wp_verify_nonce($_POST['ibs-event-nonce'], basename(__FILE__)) ) ) ? true : false;
        if ($is_autosave || $is_revision || !$is_valid_nonce) {
            return;
        }
        update_post_meta($post_id, 'ibs-event-start', isset($_POST['ibs-event-start']) ? $_POST['ibs-event-start'] : time());

        update_post_meta($post_id, 'ibs-event-end', isset($_POST['ibs-event-end']) ? $_POST['ibs-event-end'] : time());

        update_post_meta($post_id, 'ibs-event-allday', isset($_POST['ibs-event-allday'])) ? true : false;

        update_post_meta($post_id, 'ibs-event-color', isset($_POST['ibs-event-color']) ? $_POST['ibs-event-color'] : '#5484ed');

        update_post_meta($post_id, 'ibs-event-recurr', isset($_POST['ibs-event-recurr'])) ? true : false;

        update_post_meta($post_id, 'ibs-event-repeat', isset($_POST['ibs-event-repeat']) ? $_POST['ibs-event-repeat'] : '');

        update_post_meta($post_id, 'ibs-event-exceptions', isset($_POST['ibs-event-exceptions']) ? $_POST['ibs-event-exceptions'] : '');
    }

    static function custom_columns_content($column_name, $post_id) {
        switch ($column_name) {
            case 'event_start' :
                $start_date = get_post_meta($post_id, 'ibs-event-start', true);
                $repeats = get_post_meta($post_id, 'ibs-event-recurr', true);
                if ($repeats) {
                    echo get_post_meta($post_id, 'ibs-event-repeat', true);
                } else {
                    echo date('F d, Y', $start_date);
                }
                break;
            case 'event_end' :
                $end_date = get_post_meta($post_id, 'ibs-event-end', true);
                $repeats = get_post_meta($post_id, 'ibs-event-recurr', true);
                if ($repeats) {
                    echo '';
                } else {
                    echo date('F d, Y', $end_date);
                }
                break;
            case 'event_allday' :
                $ans = get_post_meta($post_id, 'ibs-event-allday', true);
                if (empty($ans)) {
                    echo '';
                } else {
                    echo 'X';
                }
                break;
            case 'event_repeats' :
                $ans = get_post_meta($post_id, 'ibs-event-recurr', true);
                if (empty($ans)) {
                    echo '';
                } else {
                    echo 'X';
                }
                break;
        }
    }

//==============================================================================

    static function admin_options_init() {
        register_setting('ibs_events_options', 'ibs_events_options');
        add_settings_section('section-general-events', '', array(__CLASS__, 'admin_general_header'), 'general-events');
        add_settings_field('debug', 'debug', array(__CLASS__, 'field_debug'), 'general-events', 'section-general-events');
        add_settings_field('ui_theme', 'ui theme', array(__CLASS__, 'field_ui_theme'), 'general-events', 'section-general-events');
        add_settings_field('post', 'Post IBS Events', array(__CLASS__, 'field_post'), 'general-events', 'section-general-events');

        add_settings_section('section-event', '', array(__CLASS__, 'admin_event_header'), 'event-events');
        add_settings_field('firstDay', 'First Day', array(__CLASS__, 'field_firstDay'), 'event-events', 'section-event');
        add_settings_field('timeFormat', 'Time Format', array(__CLASS__, 'field_timeFormat'), 'event-events', 'section-event');
        add_settings_field('titleFormat', 'Date Format', array(__CLASS__, 'field_titleFormat'), 'event-events', 'section-event');

        add_settings_section('section-list-events', '', array(__CLASS__, 'admin_list_header'), 'list-events');
        add_settings_field('repeats', 'Show repeats', array(__CLASS__, 'field_repeats'), 'list-events', 'section-list-events');
        add_settings_field('align', 'List Align', array(__CLASS__, 'field_align'), 'list-events', 'section-list-events');
        add_settings_field('width', 'List Width', array(__CLASS__, 'field_width'), 'list-events', 'section-list-events');
        add_settings_field('max', 'Max Events', array(__CLASS__, 'field_max'), 'list-events', 'section-list-evewnts');
        add_settings_field('timeFormat', 'Time Format', array(__CLASS__, 'field_timeFormat_list'), 'list-events', 'section-list-events');
        add_settings_field('dateFormat', 'Date Format', array(__CLASS__, 'field_dateFormat'), 'list-events', 'section-list-events');
        add_settings_field('startDate', 'Starting Date', array(__CLASS__, 'field_start'), 'list-events', 'section-list-events');
        add_settings_field('descending', 'Sort descending', array(__CLASS__, 'field_descending'), 'list-events', 'section-list-events');
        add_settings_field('shortcode', 'Shortcode', array(__CLASS__, 'field_shortcode'), 'list-events', 'section-list-events');

        add_settings_section('section-event-qtip', '', array(__CLASS__, 'admin_event_qtip_header'), 'event-qtip-events');
        add_settings_field('rounded', 'Rounded', array(__CLASS__, 'field_qtip_rounded'), 'event-qtip-events', 'section-event-qtip');
        add_settings_field('shadow', 'Shadow', array(__CLASS__, 'field_qtip_shadow'), 'event-qtip-events', 'section-event-qtip');
        add_settings_field('style', 'Style', array(__CLASS__, 'field_qtip_style'), 'event-qtip-events', 'section-event-qtip');
    }

    static function admin_general_header() {
        echo '<div class="ibs-admin-bar">General settings</div>';
    }

    static function admin_event_header() {
        echo '<div class="ibs-admin-bar">Event settings</div>';
    }

    static function admin_list_header() {
        echo '<div class="ibs-admin-bar">Shortcode [ibs-list-events] default settings</div>';
    }

    static function admin_event_qtip_header() {
        echo '<div class="ibs-admin-bar">Widget Qtip settings</div>';
    }

    static function field_debug() {
        $checked = self::$options['debug'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_events_options[debug]" value="true"' . $checked . '/>';

        $version = self::$options['version'];
        echo "<input type='hidden' name='ibs_events_options[version]' value='$version'/>";
    }

    static function field_ui_theme() {
        $result = array();
        $dir = get_home_path() . 'wp-content/plugins/ibs-events/css/jquery-ui-themes-1.11.1/themes/';
        if (file_exists($dir)) {
            $files = scandir($dir);
            natcasesort($files);
            if (count($files) > 2) { /* The 2 accounts for . and .. */
                foreach ($files as $file) {
                    if (file_exists($dir . $file) && $file != '.' && $file != '..' && is_dir($dir . $file)) {
                        $result[] = $file;
                    }
                }
            }
        }
        foreach ($result as &$line) {
            $line = "<option selected value='$line' >$line</option>";
        }
        echo "<select name='ibs_events_options[ui_theme]'>";
        foreach ($result as $option) {
            if (strpos($option, self::$options['ui_theme']) == false) {
                $option = str_replace('selected', '', $option);
            }
            echo $option;
        }
        echo "</select>";
    }

    static function field_firstDay() {
        $value = self::$options['firstDay'];
        echo '<select name="ibs_events_options[firstDay]" value="' . $value . '" />';
        $selected = self::$options['firstDay'] == "0" ? 'selected' : '';
        echo '<option value="0" ' . $selected . '>Sunday</option>';
        $selected = self::$options['firstDay'] == "1" ? 'selected' : '';
        echo '<option value="1" ' . $selected . '>Monday</option>';
        $selected = self::$options['firstDay'] == "2" ? 'selected' : '';
        echo '<option value="2" ' . $selected . '>Tuesday</option>';
        $selected = self::$options['firstDay'] == "3" ? 'selected' : '';
        echo '<option value="3" ' . self::$options['firstDay'] == "3" ? 'checked' : '' . '>Wednesday</option>' . "\n";
        $selected = self::$options['firstDay'] == "4" ? 'selected' : '';
        echo '<option value="4" ' . $selected . '>Thursday</option>';
        $selected = self::$options['firstDay'] == "5" ? 'selected' : '';
        echo '<option value="5" ' . $selected . '>Friday</option>';
        $selected = self::$options['firstDay'] == "6" ? 'selected' : '';
        echo '<option value="6" ' . $selected . '>Saturday</option>';
        echo '</select>';
    }

    static function field_titleFormat() {
        $value = self::$options['titleFormat'];
        echo '<input name="ibs_events_options[titleFormat]" type="text" size="25" value="' . $value . '"/>';
    }

    static function field_timeFormat() {
        $value = self::$options['timeFormat'];
        echo '<input name="ibs_events_options[timeFormat]" type="text" size="25" value="' . $value . '"/>';
    }

    static function field_timeZone() {
        $value = self::$options['timeZone'];
        echo '<input name="ibs_events_options[timeZone]" type="text" size="25" value="' . $value . '"/>';
    }

    static function field_post() {
        $checked = self::$options['post'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_events_options[post]" value="true"' . $checked . '/>';
    }

    static function field_repeats() {
        $checked = self::$options['list']['repeats'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_events_options[list][repeats]" value="true"' . $checked . '/>';
    }

    static function field_start() {
        $value = self::$options['list']['start'];
        echo "<input type='text' name='ibs_events_options[list][start]'  value='$value'  /><span> now or yyyy-mm-dd </span>";
    }

    static function field_descending() {
        $checked = self::$options['list']['descending'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_events_options[list][descending]" value="yes"' . $checked . '/>';
    }

    static function field_align() {
        echo '<select name="ibs_events_options[list][align]"  />';
        $selected = self::$options['list']['align'] == "alignleft" ? 'selected' : '';
        echo '<option value="alignleft" ' . $selected . '>left</option>';
        $selected = self::$options['list']['align'] == "aligncenter" ? 'selected' : '';
        echo '<option value="aligncenter" ' . $selected . '>center</option>';
        $selected = self::$options['list']['align'] == "alignright" ? 'selected' : '';
        echo '<option value="alignright" ' . $selected . '>right</option>';
        echo '</select>';
    }

    static function field_max() {
        $value = self::$options['list']['max'];
        echo "<input type='number' name='ibs_events_options[list][max]'  value='$value'  />";
    }

    static function field_width() {
        $value = self::$options['list']['width'];
        echo '<input name="ibs_events_options[list][width]" type="text" size="25" value="' . $value . '"/>';
    }

    static function field_dateFormat() {
        $value = self::$options['list']['dateFormat'];
        echo '<input name="ibs_events_options[list][dateFormat]" type="text" size="25" value="' . $value . '"/><a href="http://momentjs.com/docs/#/displaying/" target="_blank" title="moment.js formatting">help</a>';
    }

    static function field_timeFormat_list() {
        $value = self::$options['list']['timeFormat'];
        echo '<input name="ibs_events_options[list][timeFormat]" type="text" size="25" value="' . $value . '"/><a href="http://momentjs.com/docs/#/displaying/" target="_blank" title="moment.js formatting">help</a>';
    }

    static function field_shortcode() {
        $value = '[ibs-list-events repeats="no" align="alignleft" width="100%" max="100" dateFormat="dddd MMM DD" timeFormat="HH:mm" start="now" descending="no" ]';
        echo "<textarea class='widefat' >$value</textarea>";
    }

    static function field_qtip_rounded() {
        $checked = self::$options['list']['qtip']['shadow'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_events_options[list][qtip][shadow]" value="qtip-rounded"' . $checked . '/>';
    }

    static function field_qtip_shadow() {
        $checked = self::$options['list']['qtip']['rounded'] ? "checked" : '';
        echo '<input type="checkbox" name="ibs_events_options[list][qtip][rounded]" value="qtip-shadow"' . $checked . '/>';
    }

    static function field_qtip_style() {
        echo "<select name='ibs_events_options[list][qtip][style]'> ";
        $value = self::$options['list']['qtip']['style'];
        $selected = $value === '' ? "selected" : '';
        echo "<option id='qtip-none'     $selected  value=''  selected >none</option>";
        $selected = $value === 'qtip-light' ? "selected" : '';
        echo "<option id='qtip-light'    $selected value='qtip-light' >light coloured style</option>";
        $selected = $value === 'qtip-dark' ? "selected" : '';
        echo "<option id='qtip-dark'     $selected value='qtip-dark' >dark style</option>";
        $selected = $value === 'qtip-cream' ? "selected" : '';
        echo "<option id='qtip-cream'    $selected value='qtip-cream' >cream</option>";
        $selected = $value === 'qtip-red' ? "selected" : '';
        echo "<option id='qtip-red'      $selected value='qtip-red' >Alert-ful red style </option>";
        $selected = $value === 'qtip-green' ? "selected" : '';
        echo "<option id='qtip-green'   $selected value='qtip-green' >Positive green style </option>";
        $selected = $value === 'qtip-blue' ? "selected" : '';
        echo "<option id='qtip-blue'     $selected value='qtip-blue' >Informative blue style </option>";
        $selected = $value === 'qtip-bootstrap' ? "selected" : '';
        echo "<option id='qtip-bootstrap'$selected value='qtip-bootstrap' >Twitter Bootstrap style </option>";
        $selected = $value === 'qtip-youtube' ? "selected" : '';
        echo "<option id='qtip-youtube'  $selected value='qtip-youtube' >Google's new YouTube style</option>";
        $selected = $value === 'qtip-tipsy' ? "selected" : '';
        echo "<option id='qtip-tipsy'    $selected value='qtip-tipsy' >Minimalist Tipsy style </option>";
        $selected = $value === 'qtip-tipped' ? "selected" : '';
        echo "<option id='qtip-tipped'   $selected value='qtip-tipped' >Tipped libraries</option>";
        $selected = $value === 'qtip-jtools' ? "selected" : '';
        echo "<option id='qtip-jtools'   $selected value='qtip-jtools' >Tools tooltip style </option>";
        $selected = $value === 'qtip-cluetip' ? "selected" : '';
        echo "<option id='qtip-cluetip'  $selected value='qtip-cluetip' >Good ole'' ClueTip style </option>";
        echo "</select>";
    }

    static function admin_add_page() {
        add_options_page('IBS Events', 'IBS Events', 'manage_options', 'ibs_events', array(__CLASS__, 'admin_options_page'));
    }

    static function admin_options_page() {
        ?>
        <form action="options.php" method="post">
            <?php settings_fields('ibs_events_options'); ?>
            <div>
                <?php do_settings_sections('general-events'); ?>

                <?php do_settings_sections('event-events'); ?>

                <?php do_settings_sections('list-events'); ?>

                <?php do_settings_sections('event-qtip-events'); ?>

                <?php submit_button(); ?>
            </div>
        </form>
        <?PHP
    }

    static function handle_shortcode($atts, $content = null) {
        self::$add_script += 1;
        $args = self::$options['list'];
        if (is_array($atts)) {
            foreach ($args as $key => $value) {
                if (isset($atts[strtolower($key)])) {
                    $args[$key] = $atts[strtolower($key)];
                }
            }
        }
        array_walk_recursive($args, array(__CLASS__, 'fixBool'));
        $args['id'] = self::$add_script;
        $args['ajaxUrl'] = admin_url("admin-ajax.php");
        $id = self::$add_script;
        $html = '<div id="ibs-list-events-id" class="' . $args['align'] . ' ibs-events" style="width:%w;" ></div>';
        $html = str_replace('-id', '-' . $id, $html);
        $html = str_replace('%w', $args['width'], $html);
        ob_start();
        echo $html;
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                new IBS_LIST_EVENTS(jQuery, <?PHP echo json_encode($args); ?>, 'shortcode');
            });
        </script> 
        <?PHP
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    static function register_script() {
        $min = self::$options['debug'] ? '' : '.min';
        wp_register_style('ibs-list-events-style', plugins_url("css/ibs-list-events.css", __FILE__));
        wp_register_script('ibs-list-events-script', plugins_url("js/ibs-list-events$min.js", __FILE__), self::$core_handles);
        wp_register_script('ibs-moment-script', plugins_url("js/moment$min.js", __FILE__));
        wp_register_style('ibs-admin-style', plugins_url("css/admin.css", __FILE__));
        wp_register_script('rrule-rrule-script', plugins_url("js/rrule$min.js", __FILE__));
    }

    static $core_handles_list = array(
        'jquery',
        'json2'
    );
    static $script_handles_list = array(
        'ibs-list-events-script',
        'ibs-moment-script',
        'rrule-rrule-script'
    );
    static $style_handles_list = array(
        'ibs-list-events-style'
    );

    static function enqueue_scripts() {
        foreach (self::$core_handles_list as $handle) {
            wp_enqueue_script($handle);
        }
        if (is_active_widget('', '', 'ibs_wgcal_events', true)) {
            self::print_admin_scripts();
            wp_enqueue_style(self::$style_handles_list);
            wp_enqueue_script(self::$script_handles_list);
        }
    }

    static function admin_enqueue_scripts($page) {
        if ($page === 'settings_page_ibs_events') {

            wp_enqueue_style(self::$style_handles_list);
            wp_enqueue_script(self::$script_handles_list);
            wp_enqueue_style('ibs-admin-style');
        }
    }

    static function print_admin_scripts() {
        ?>
        <?PHP
    }

    static function print_script_header() {
        
    }

    static function print_script_footer() {
        if (self::$add_script > 0) {
            self::print_admin_scripts();
            wp_print_styles(self::$style_handles_list);
            wp_print_scripts(self::$script_handles_list);
        }
    }

    static function get_ibs_events() {
        Global $post;
        $query_args = array(
            'post_type' => 'ibs_event',
            'posts_per_page' => 9999,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'meta_query' => ''
        );
        $result = array();
        $events = new WP_Query($query_args);
        while ($events->have_posts()) {
            $events->the_post();
            $item = array(
                'id' => 'IBS-' . $post->ID,
                'title' => get_the_title($post->ID),
                'start' => get_post_meta($post->ID, 'ibs-event-start', true),
                'end' => get_post_meta($post->ID, 'ibs-event-end', true),
                'allDay' => get_post_meta($post->ID, 'ibs-event-allday', true),
                'color' => get_post_meta($post->ID, 'ibs-event-color', true),
                'repeat' => get_post_meta($post->ID, 'ibs-event-repeat', true),
                'recurr' => get_post_meta($post->ID, 'ibs-event-recurr', true),
                'exceptions' => get_post_meta($post->ID, 'ibs-event-exceptions', true),
                'url' => get_the_permalink($post->ID),
                'description' => get_the_excerpt()
            );
            if (empty($item['recurr'])) {
                $item['recurr'] = false;
            }
            if (false === $item['recurr']) {
                $item['repeat'] = null;
                $item['exceptions'] = null;
            }
            $result[] = $item;
        }
        echo json_encode($result);
        exit;
    }

}

IBS_EVENTS::init();
include( 'widget-ibs-events.php' );
