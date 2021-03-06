/*
 Author URI: http://indianbendsolutions.com
 License: GPL
 
 GPL License: http://www.opensource.org/licenses/gpl-license.php
 
 This program is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
function IBS_LIST_EVENTS($, args, mode) {
    this.init(args, mode)
}
(function ($) {
    IBS_LIST_EVENTS.prototype.init = function (args, mode) {
        var list = this;
        this.options = {
            repeats: true,
            dateFormat: 'ddd MMM DD',
            timeFormat: 'h:mm a',
            errorMsg: 'No events in calendar',
            max: 100,
            start: 'now',
            descending: false
        }
        for (var arg in args) {
            if (typeof this.options[arg] !== 'undefined' && args[arg] !== '') {
                this.options[arg] = args[arg];
            }
        }
        this.qtip_params = function (event) {
            var loc = '';
            if (typeof event.location !== 'undefined' && event.location !== '') {
                loc = '<p>' + 'Location: ' + event.location + '</p>';
            }
            var desc = '';
            if (typeof event.description !== 'undefined' && event.description !== '') {
                desc = '<p>' + event.description + '</p>'
            }
            var time = moment(event.start).format(list.options.dateFormat + ' ' + list.options.timeFormat) + moment(event.end).format(' - ' + list.options.timeFormat);
            if (event.allDay) {
                time = moment(event.start).format(args.dateFormat) + ' ' + 'All day';
            }
            return {
                content: {'text': '<p>' + event.title + '</p>' + loc + desc + '<p>' + time + '</p>'},
                position: {
                    my: 'bottom center',
                    at: 'top center'
                },
                style: {
                    classes: args['qtip']['style'] + ' ' + args['qtip']['rounded'] + args['qtip']['shadow']

                },
                show: {
                    event: 'mouseover'
                },
                hide: {
                    event: 'mouseout mouseleave'
                }
            };
        }
        this.ibs_events = null;
       
        if (list.options.start === 'now') {
            list.options.start = moment();
        } else {
            list.options.start = moment(list.options.start);
        }
        list.options.end = moment(list.options.start).add(2, 'year');
        $.get(args.ajaxUrl, {
            action: 'ibs_events_get_events',
            cache: false,
            dataType: 'json'
        }).then(
                function (data) {
                    if (data !== "") {
                        data = decodeURIComponent(data);
                        list.ibs_events = JSON.parse(data);
                        for (var i in list.ibs_events) {
                            list.ibs_events[i].title = jQuery('<div>').html(list.ibs_events[i].title).text();
                            list.ibs_events[i].description = jQuery('<div>').html(list.ibs_events[i].description).text();
                            list.ibs_events[i].editable = false;
                            list.ibs_events[i].start = moment.unix(parseInt(list.ibs_events[i].start));
                            list.ibs_events[i].end = moment.unix(parseInt(list.ibs_events[i].end));
                        }
                        console.log("IBS Events loaded.");
                    } else {
                        list.ibs_events = [];
                    }
                    var result = [];
                    try {
                        for (var ex in list.ibs_events) {
                            var event = list.ibs_events[ex];
                            if (false === event.recurr) {
                                if (list.options.start.diff(event.start) <= 0) {
                                    result.push(event);
                                }
                            } else {
                                if (list.options.repeats) {
                                    var exceptions = [];
                                    if (event.exceptions) {
                                        exceptions = event.exceptions.split(',');
                                        for (var i in exceptions) {
                                            exceptions[i] = moment(exceptions[i]).startOf('day');
                                        }
                                    }
                                    var rule = new RRule(RRule.parseString(event.repeat));
                                    var to, from;
                                    from = list.options.start.toDate();
                                    to = list.options.end.toDate()
                                    var dates = rule.between(from, to);
                                    for (i in dates) {
                                        dates[i] = moment(dates[i]).startOf('day');
                                    }
                                    var isException = function (index) {
                                        for (var i in exceptions) {
                                            if (exceptions[i].diff(dates[index]) === 0) {
                                                return true;
                                            }
                                        }
                                        return false;
                                    };
                                    var duration = moment(event.end).diff(moment(event.start), 'seconds');
                                    var start_time = moment(event.start).unix() - moment(event.start).startOf('day').unix();

                                    for (var i in dates) {
                                        if (isException(i)) {
                                            continue;
                                        }
                                        var theDate = dates[i].startOf('day');
                                        var current = {
                                            start: theDate.add(start_time, 'seconds'),
                                            end: theDate.add(duration, 'seconds'),
                                            id: event.id,
                                            title: event.title,
                                            allDay: event.allDay,
                                            color: event.color,
                                            textColor: event.textColor,
                                            description: event.description,
                                            url: event.url,
                                            repeat: event.repeat,
                                            exceptions: event.exceptions
                                        };
                                        result.push(current);
                                    }
                                }
                            }
                        }
                    } catch (e) {
                        console.log(ex + e);
                    }
                    var events = result.sort(function (a, b) {
                        return a.start.unix() - b.start.unix();
                    });
                    if (events.length === 0) {
                        events.push({
                            title: 'No events found',
                            start: moment(),
                            end: moment(),
                            location: '',
                            description: '',
                            url: ''
                        });
                    }
                    if (list.options.descending) {
                        events = events.reverse();
                    }
                    events = events.slice(0, list.options.max);
                    if (mode === 'shortcode') {
                        var event_div = '#ibs-list-events-' + args.id;
                        $(event_div).empty().css('cursor', 'pointer');
                        for (var i = 0; i < events.length; i++) {
                            var pattern = args.dateFormat
                            var d = moment(events[i].start).format(pattern);
                            var f = moment(events[i].start).format(args.timeFormat);
                            var t = moment(events[i].end).format(args.timeFormat);
                            $(event_div)
                                    .append($('<div>')
                                            .append($('<div>').addClass('bar')
                                                    .append($('<a>').attr({href: events[i].url, target: '_blank'}).text(events[i].title)))
                                            .append($('<div>').addClass('when-div')
                                                    .append($('<span>').text(d))
                                                    .append($('<span>').text(f))
                                                    .append($('<span>').text('to'))
                                                    .append($('<span>').text(t)))
                                            .append($('<div>').text(events[i].location).addClass('where-div'))
                                            .append($('<div>').css('display', events[i].description === '' ? 'none' : 'block')
                                                    .append($('<div>').html(events[i].description).addClass('textbox')))
                                            );
                        }
                    } else {
                        var event_table = '#ibs-events-' + args.id;
                        for (var i = 0; i < events.length; i++) {
                            var qtp = list.qtip_params(events[i]);
                            $(event_table)
                                    .append($('<div>').qtip(qtp)
                                            .append($('<a>').attr({href: events[i].url, target: '_blank'}).text(events[i].title)));
                        }
                    }

                },
                function () {
                    console.log("Get IBS Events failed.");
                });
    };
}(jQuery));
