<?PHP ?>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        new IBS_Event(<?PHP echo json_encode($args); ?>);
    });
</script> 
<div id="event-dialog">
    <div class="widefat ibs-event-date">
        <label for="ibs-event-start-date">Start</label>
        <input class="ibs-datepicker event-allday" id="ibs-event-start-date" type="text" placeholder="start date" value="" />
        <input id="ibs-event-start" type="hidden" value="<?php echo $event_start; ?>" name="ibs-event-start" />
        <input class="ibs-timepicker event-allday" id="ibs-event-start-time" type="text" placeholder="start time" value="" />
        <label for="ibs-event-allday" style="width:45px;margin-left:10px;">All day</label>
        <input id="ibs-event-allday" type="checkbox"  class="cb" <?php echo $event_allday ? 'checked' : ''; ?> name="ibs-event-allday" />
    </div>
    <div class="widefat ibs-event-date">
        <label for="ibs-event-end-date">End</label>
        <input class="ibs-datepicker event-allday" id="ibs-event-end-date" type="text" placeholder="end date" value="" />
        <input id="ibs-event-end" type="hidden" value="<?php echo $event_end; ?>" name="ibs-event-end"  />
        <input class="ibs-timepicker event-allday" id="ibs-event-end-time" type="text" placeholder="end time" value="" />
    </div>

    <div class="widefat" ><label class="color-label" >Event color </label>
        <div class="color-box color-box-selected" style = "background-color: #5484ed;"></div>
        <div class="color-box" style = "background-color: #a4bdfc;"></div>
        <div class="color-box" style = "background-color: #46d6db;"></div>
        <div class="color-box" style = "background-color: #7ae7bf;"></div>
        <div class="color-box" style = "background-color: #51b749;"></div>
        <div class="color-box" style = "background-color: #fbd75b;"></div>
        <div class="color-box" style = "background-color: #ffb878;"></div>
        <div class="color-box" style = "background-color: #ff887c;"></div>
        <div class="color-box" style = "background-color: #dc2127;"></div>
        <div class="color-box" style = "background-color: #dbadff;"></div>
        <div class="color-box" style = "background-color: #e1e1e1;"></div>
        <input type="hidden" id="ibs-event-color" value="<?php echo $event_color; ?>" name="ibs-event-color" />
    </div>
    <div></div>
    <div class="widefat">
        <label class="option-name" for="ibs-event-recurr">Repeats
            <input id="ibs-event-recurr" type="checkbox" style="margin-top:1px;" <?php echo $event_recurr ? 'checked' : ''; ?> name="ibs-event-recurr" /></label>
    </div>  
    <div id="repeat-options">
        <div class="repeat-option">
            <label>Daily</label><input name="freq" type="radio" value="3" class="cb" />
            <label>Weekly</label><input name="freq" type="radio" value="2" class="cb" checked />
            <label>Monthly</label><input name="freq" type="radio" value="1" class="cb" />
            <label>Yearly</label><input name="freq" type="radio" value="0" class="cb" />
            <label style="margin-left:10px;" for="ibs-event-frequency">Every</label>
            <input id="repeat-interval" style="width:50px;" type="number" value="1" min="1" name="interval"/><label id="repeat-interval-type">week</label>
        </div>
        <div class="repeat-option widefat">
            <label class="option-name" for="ibs-event-repeats-on">Repeats on</label>
            <label>Sun</label><input type="checkbox" name="byweekday" class="cb" title="Sunday" value="6">
            <label>Mon</label><input type="checkbox" name="byweekday" class="cb" title="Monday"  value="0">
            <label>Tue</label><input type="checkbox" name="byweekday" class="cb" title="Tuesday" value="1">
            <label>Wed</label> <input type="checkbox" name="byweekday" class="cb" title="Wednesday" value="2">
            <label>Thu</label><input type="checkbox" name="byweekday" class="cb" title="Thursday" value="3">
            <label>Fri</label><input type="checkbox" name="byweekday" class="cb" title="Friday" value="4">
            <label>Sat</label><input type="checkbox" name="byweekday" class="cb" title="Saturday" value="5">
        </div>
        <div class="repeat-option widefat">
            <label class="option-name">Starting</label>
            <input id="repeat-dtstart" name="dtstart" placeholder="rrule.dtstart (first date)" type="text"/>
        </div>
        <div class="repeat-option widefat">
            <label class="option-name" for="ibs-event-ends">Ending</label>
            <input name="radio_ends" value="never" type="radio" class="cb" checked /><label>Never</label>
            <input name="radio_ends" type="radio" value="until" class="cb" /><label>Until</label>
            <input class="option-ends" id="repeat-until" type="text" name="until"  placeholder="rrule.until (last date)" disabled />
            <input name="radio_ends" type="radio" value="count" class="cb" /><label>Count</label>
            <input class="option-ends" id="repeat-count" type="number" max="1000" min="1" value="" name="count" disabled/>
        </div>
        <div style="display:none">
            <div class="widefat">
                <label class="option-name">Week starts</label>
                <input id="repeat-wkst" type="hidden" name="wkst" value="0" />
            </div>
            <div class="widefat">
                <label class="option-name">Month</label>
                <input name="bymonth" type="checkbox" value="1" class="cb" /><label>Jan</label>
                <input name="bymonth" type="checkbox" value="2" class="cb" /><label>Feb</label>
                <input name="bymonth" type="checkbox" value="3" class="cb" /><label>Mar</label>
                <input name="bymonth" type="checkbox" value="4" class="cb" /><label>Apr</label>
                <input name="bymonth" type="checkbox" value="5" class="cb" /><label>May</label>
                <input name="bymonth" type="checkbox" value="6" class="cb" /><label>Jun</label>
            </div>
            <div class="widefat">
                <label class="option-name"></label>
                <input name="bymonth" type="checkbox" value="7" class="cb" /><label>Jul</label>
                <input name="bymonth" type="checkbox" value="8" class="cb" /><label>Aug</label>
                <input name="bymonth" type="checkbox" value="9" class="cb" /><label>Sep</label>
                <input name="bymonth" type="checkbox" value="10" class="cb" /><label>Oct</label>
                <input name="bymonth" type="checkbox" value="11" class="cb" /><label>Nov</label>
                <input name="bymonth" type="checkbox" value="12" class="cb" /><label>Dec</label>
            </div>
            <div class="widefat">
                <label class="option-name" >Position</label>
                <input id="repeat-bysetpos"  placeholder="rrule.bysetpos" name="bysetpos"/>  
            </div>
            <div class="widefat">
                <label class="option-name" >Day of mo.</label>
                <input id="repeat-bymonthday" placeholder="rrule.bymonthday" name="bymonthday"/>
            </div>
            <div class="widefat">
                <label class="option-name" >Day of yr.</label>
                <input id="repeat-byyearday"  placeholder="rrule.byyearday" name="byyearday" type="text" value=""/>
            </div>
            <div class="widefat">
                <label class="option-name" >Week no.</label>
                <input id="repeat-byweekno"  placeholder="rrule.byweekno" name="byweekno">
            </div> 
            <div class="widefat">
                <label class="option-name" >Hour</label>
                <input id="repeat-byhour"  placeholder="rrule.byhour" name="byhour">
            </div> 
            <div class="widefat">
                <label class="option-name" >Minute</label>
                <input id="repeat-byminute" placeholder="rrule.byminute" name="byminute"/>
            </div> 
            <div class="widefat">
                <label class="option-name" >Second</label>
                <input id="repeat-bysecond" placeholder="rrule.bysecond" name="bysecond">
            </div> 
        </div>
    </div>
    <div class="widefat">
        <label class="widefat">Repeat Exception List (YYYY-MM-DD, ...)</label>
        <input class="widefat" type="text" id="ibs-event-exceptions" value="<?php echo $event_exceptions; ?>" name="ibs-event-exceptions" />
    </div> 
    <div class="repeat-option widefat"> <label for="ibs-event-repeat">Repeat Pattern</label></div>
    <textarea class="repeat-option widefat" id="ibs-event-repeat-display" readonly  ><?php echo $event_repeat; ?></textarea>
    <input class="repeat-option" type="hidden" id="ibs-event-repeat" name="ibs-event-repeat" value="<?php echo $event_repeat; ?>">
</div>