<?php

class IBS_WEvents extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'class' => 'ibs_wevents',
            'description' => 'A widget to display IBS Events'
        );

        parent::__construct(
                'ibs_wevents', 'IBS Events', $widget_ops
        );
    }

    public function form($instance) {
        $widget_defaults = array(
            'title' => 'IBS Events',
            'max' => 50,
            'repeats' => "no",
            'start' => "now",
            'descending' => 'no'
        );

        $instance = wp_parse_args((array) $instance, $widget_defaults);
        $args = get_option('ibs_calendar_options')
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php echo'Title'; ?></label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        <p></p>

        <div class="widefat"><label for="<?php echo $this->get_field_id('start'); ?>"><span  style="display:inline-block; width:100px;"><?php echo 'Start'; ?></span>
                <input type="text" id="<?php echo $this->get_field_id('start'); ?>" name="<?php echo $this->get_field_name('start'); ?>"  value="<?php echo esc_attr($instance['start']); ?>">
            </label></div>
        <p></p>
        
        <div class="widefat"><label for="<?php echo $this->get_field_id('max'); ?>"><span  style="display:inline-block; width:100px;"><?php echo 'Max events'; ?></span>
                <input type="number" min=1 max=100 id="<?php echo $this->get_field_id('max'); ?>" name="<?php echo $this->get_field_name('max'); ?>"  value="<?php echo esc_attr($instance['max']); ?>">
            </label></div>
        <p></p>

        <div class="widefat"><label for="<?php echo $this->get_field_id('repeats'); ?>"><span  style="display:inline-block; width:100px;"><?php echo 'List repeat events'; ?></span>
                <input type="checkbox" id="<?php echo $this->get_field_id('repeats'); ?>" name="<?php echo $this->get_field_name('repeats'); ?>"  <?php echo esc_attr($instance['repeats']) === 'yes' ? 'checked' : ''; ?>  value="yes">
            </label></div>
        <p></p>
        <div class="widefat"><label for="<?php echo $this->get_field_id('descending'); ?>"><span  style="display:inline-block; width:100px;"><?php echo 'Descending order'; ?></span>
                <input type="checkbox" id="<?php echo $this->get_field_id('descending'); ?>" name="<?php echo $this->get_field_name('descending'); ?>"  <?php echo esc_attr($instance['descending']) === 'yes' ? 'checked' : ''; ?>  value="yes">
            </label></div>
        <p></p>
        <?PHP
    }

    public function update($new_instance, $old_instance) {
        $old_instance = $new_instance;

        $instance['title'] = $new_instance['title'];

        return $old_instance;
    }

    public function widget($widget_args, $instance) {
        extract($widget_args);
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        $options = get_option('ibs_events_options');
        $args = $options['list'];
        $args['start'] = strtolower($instance['start']);
        $args['max'] = (int) $instance['max'];
        $args['repeats'] = isset($instance['repeats']);
        $args['descending'] = isset($instance['descending']);
        $args['ajaxUrl'] = admin_url("admin-ajax.php");
        $args['id'] = $widget_id;
        $id = $widget_id;
        ?>
        <div id="ibs-events-<?php echo $id; ?>" ></div>
        <script type="text/javascript">
            new IBS_LIST_EVENTS(jQuery, <?PHP echo json_encode($args); ?>, 'widget');
        </script> 
        <?php
        echo $after_widget;
    }

}

function ibs_register_list_widget() {
    register_widget('IBS_WEvents');
}

add_action('widgets_init', 'ibs_register_list_widget');
