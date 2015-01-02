function dateAsTimestamp (observe) {
    return {
        observe: observe,
        update: function ($el, timestamp) {
            if (+timestamp > 0) {
                $el.get(0).valueAsNumber = timestamp * 1000;
            }
        },
        getVal: function ($el) {
            return $el.get(0).valueAsNumber / 1000;
        }
    };
}

exports.dateAsTimestamp = dateAsTimestamp;
