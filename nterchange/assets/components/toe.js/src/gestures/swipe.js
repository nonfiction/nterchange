(function ($, touch, window, undefined) {

    $.event.special.swipe = (function () {

        var cfg = {
                distance: 40, // minimum
                duration: 300, // maximum
                direction: 'all',
                finger: 1
            };

        return touch.track({
            touchstart: function (event, state, start) {
                state.finger = state.start.point.length;
            },
            touchmove: function (event, state, move) {
                // if another finger was used then increment the amount of fingers used
                state.finger = move.point.length > state.finger ? move.point.length : state.finger;
            },
            touchend: function (event, state, end) {
                var opt = $.extend(cfg, event.data), 
                    duration,
                    distance;

                // calc
                duration = touch.calc.getDuration(state.start, end);
                distance = touch.calc.getDistance(state.start.point[0], end.point[0]);

                // check if the swipe was valid
                if (duration < opt.duration && distance > opt.distance) {

                    state.angle = touch.calc.getAngle(state.start.point[0], end.point[0]);
                    state.direction = touch.calc.getDirection(state.angle);

                    // fire if the amount of fingers match
                    if (state.finger === opt.finger && (opt.direction === 'all' || state.direction === opt.direction)) {
                        $(event.target).trigger($.Event('swipe', state));
                    }
                }
            }
        });
    }());

}(jQuery, jQuery.toe, this));