// Booking form on session_management/schedule.php: month calendar, 30-minute slot grid and the read-only date/time summary.
function initScheduleForm(config) {
    var WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                  'August', 'September', 'October', 'November', 'December'];
    var SLOT = 30;

    var form = document.getElementById('scheduleForm');
    var monthLabel = document.getElementById('calendarMonth');
    var calendarGrid = document.getElementById('calendarGrid');
    var slotGrid = document.getElementById('slotGrid');
    var help = document.getElementById('slotHelp');
    var selectedDate = document.getElementById('selectedDate');
    var dateInput = document.getElementById('sessionDate');
    var startInput = document.getElementById('startTime');
    var endInput = document.getElementById('endTime');
    var timeDisplay = document.getElementById('timeDisplay');
    var durationDisplay = document.getElementById('durationDisplay');

    var dayStart = toMinutes(config.dayStart);
    var dayEnd = toMinutes(config.dayEnd);
    var today = parseDate(config.today);
    var view = new Date(today.getFullYear(), today.getMonth(), 1);
    var state = { date: null, busy: [], start: null, end: null, pickingEnd: false };

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function toMinutes(hhmm) { var p = hhmm.split(':'); return parseInt(p[0], 10) * 60 + parseInt(p[1], 10); }
    function toTime(m) { return pad(Math.floor(m / 60)) + ':' + pad(m % 60); }
    function label12(m) {
        var h = Math.floor(m / 60);
        return pad((h % 12) || 12) + ':' + pad(m % 60) + ' ' + (h >= 12 ? 'PM' : 'AM');
    }
    // 90 -> "1 hr 30 min", 60 -> "1 hour", 30 -> "30 min"
    function durationLabel(minutes) {
        var h = Math.floor(minutes / 60);
        var m = minutes % 60;
        if (h === 0) {
            return m + ' min';
        }
        return m === 0 ? h + (h === 1 ? ' hour' : ' hours') : h + ' hr ' + m + ' min';
    }
    function parseDate(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function isoDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    function isBookableDay(d) {
        return d >= today && config.preferredDays.indexOf(WEEKDAYS[d.getDay()]) !== -1;
    }

    function slotState(m) {
        if (state.date === config.today && m <= toMinutes(config.now)) {
            return 'past';
        }
        for (var i = 0; i < state.busy.length; i++) {
            if (state.busy[i].start < m + SLOT && state.busy[i].end > m) {
                return 'busy';
            }
        }
        return '';
    }

    function isRangeFree(start, end) {
        for (var m = start; m < end; m += SLOT) {
            if (slotState(m)) {
                return false;
            }
        }
        return true;
    }

    function renderCalendar() {
        monthLabel.textContent = MONTHS[view.getMonth()] + ' ' + view.getFullYear();
        form.querySelector('[data-step="-1"]').disabled =
            view.getFullYear() === today.getFullYear() && view.getMonth() === today.getMonth();

        var html = '';
        WEEKDAYS.forEach(function (day) {
            html += '<span class="calendar-weekday">' + day.slice(0, 3) + '</span>';
        });
        for (var blank = 0; blank < view.getDay(); blank++) {
            html += '<span></span>';
        }

        var daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        for (var d = 1; d <= daysInMonth; d++) {
            var date = new Date(view.getFullYear(), view.getMonth(), d);
            var iso = isoDate(date);
            var bookable = isBookableDay(date);
            var classes = 'calendar-day ' + (bookable ? 'is-pref' : 'is-off') + (iso === state.date ? ' is-selected' : '')
                        + (iso === config.today ? ' is-today' : '');
            html += '<button type="button" class="' + classes + '" data-date="' + iso + '"'
                  + (bookable ? '' : ' disabled') + ' aria-label="' + iso + '">' + d + '</button>';
        }
        calendarGrid.innerHTML = html;
    }

    function renderSlots() {
        var preferred = state.date ? (config.preferredTimes[WEEKDAYS[parseDate(state.date).getDay()]] || []) : [];
        var limit = endLimit();
        var html = '';
        for (var m = dayStart; m <= dayEnd; m += SLOT) {
            // The extra slot at dayEnd (8:00 PM) is only shown when it can be the end of the session.
            var endOnly = m === dayEnd;
            if (endOnly && limit !== dayEnd) {
                continue;
            }
            var blocked = !state.date ? 'off' : endOnly ? '' : slotState(m);
            var canEnd = state.pickingEnd && m > state.start && m <= limit;
            var selected = state.start !== null && (state.end === null ? m === state.start : m >= state.start && m < state.end);
            var isPref = !blocked && !endOnly && preferred.some(function (range) {
                return m >= toMinutes(range.start) && m < toMinutes(range.end);
            });
            var classes = 'slot'
                        + (blocked ? ' is-' + blocked : '')
                        + (isPref ? ' is-pref' : '')
                        + (selected ? ' is-selected' : '')
                        + (state.start !== null && m === state.start ? ' is-start' : '')
                        + (!state.pickingEnd && state.end !== null && m === state.end ? ' is-end' : '')
                        + (canEnd ? ' is-can-end' : '')
                        + (state.pickingEnd && m > limit ? ' is-dim' : '');
            // A busy slot can still be clicked as the end time when the session stops right as it begins.
            var title = blocked && canEnd ? ' title="Ends at ' + label12(m) + ' - the volunteer is busy after this."' : '';
            html += '<button type="button" class="' + classes + '" data-minutes="' + m + '"' + title
                  + (blocked && !canEnd ? ' disabled' : '') + '>' + label12(m) + '</button>';
        }
        slotGrid.innerHTML = html;
    }

    // While picking the end: the latest possible end time (the next busy/past slot, or the end of the day).
    function endLimit() {
        if (!state.pickingEnd || state.start === null) {
            return -1;
        }
        for (var m = state.start + SLOT; m < dayEnd; m += SLOT) {
            if (slotState(m)) {
                return m;
            }
        }
        return dayEnd;
    }

    // Hovering a possible end time previews the range in light green.
    function previewRange(endMinutes) {
        slotGrid.querySelectorAll('.slot').forEach(function (slot) {
            var m = parseInt(slot.dataset.minutes, 10);
            slot.classList.toggle('is-preview', endMinutes !== null && m > state.start && m < endMinutes);
        });
    }

    function validate() {
        if (!state.date) {
            return 'Pick a green date first.';
        }
        if (state.start === null) {
            return 'Click a time to choose when the session starts. Green times are the volunteer\'s preference.';
        }
        if (state.end === null) {
            return 'Please select an end time.';
        }
        if (state.end <= state.start) {
            return 'The end time must be after the start time.';
        }
        if (state.start < dayStart || state.end > dayEnd) {
            return 'Sessions must be between ' + label12(dayStart) + ' and ' + label12(dayEnd) + '.';
        }
        if (!isRangeFree(state.start, state.end)) {
            return 'Part of that time is busy or has already passed. Please choose another time.';
        }
        return '';
    }

    function update() {
        var error = validate();
        dateInput.value = state.date || '';
        startInput.value = error ? '' : toTime(state.start);
        endInput.value = error ? '' : toTime(state.end);
        var waitingForEnd = state.start !== null && state.end === null;
        timeDisplay.value = state.start === null ? '' : label12(state.start) + ' – ' + (waitingForEnd ? '?' : label12(state.end));
        durationDisplay.value = error ? '' : durationLabel(state.end - state.start);
        help.classList.toggle('is-error', !!error && state.start !== null && !waitingForEnd);
        help.classList.toggle('is-prompt', waitingForEnd);

        if (!error) {
            help.textContent = '';
        } else if (state.date && state.start === null) {
            help.textContent = 'Please select a start time, then an end time.';
        } else {
            help.textContent = error;
        }
        renderSlots();
    }

    // preset: optional { start, end } in 'HH:MM' to select once the busy times have loaded (edit form).
    function selectDate(iso, preset) {
        var day = parseDate(iso);
        state.date = iso;
        state.busy = [];
        state.start = state.end = null;
        state.pickingEnd = false;
        selectedDate.value = WEEKDAYS[day.getDay()].slice(0, 3) + ', ' + day.getDate() + ' '
                           + MONTHS[day.getMonth()].slice(0, 3) + ' ' + day.getFullYear();
        timeDisplay.value = durationDisplay.value = '';
        renderCalendar();
        renderSlots();
        help.classList.remove('is-error');
        help.textContent = 'Loading the volunteer\'s times...';

        fetch('get_slots.php?volunteer_id=' + encodeURIComponent(config.volunteerId) + '&date=' + encodeURIComponent(iso)
                + '&exclude_session=' + encodeURIComponent(config.excludeSessionId || 0))
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (state.date !== iso) {
                    return;
                }
                state.busy = (data.busy || []).map(function (b) {
                    return { start: toMinutes(b.start), end: toMinutes(b.end) };
                });
                if (preset) {
                    state.start = toMinutes(preset.start);
                    state.end = toMinutes(preset.end);
                }
                update();
            })
            .catch(function () {
                help.textContent = 'Could not load the volunteer\'s times. Please try again.';
            });
    }

    // First click sets the start; the second, later click sets the end time itself (required),
    // so 2:00 PM then 4:00 PM books 2:00 - 4:00 PM.
    function selectSlot(m) {
        if (state.pickingEnd && m > state.start && isRangeFree(state.start, m)) {
            state.end = m;
            state.pickingEnd = false;
        } else {
            state.start = m;
            state.end = null;
            state.pickingEnd = true;
        }
        update();
    }

    form.querySelectorAll('.calendar-nav').forEach(function (button) {
        button.addEventListener('click', function () {
            view = new Date(view.getFullYear(), view.getMonth() + parseInt(button.dataset.step, 10), 1);
            renderCalendar();
        });
    });

    calendarGrid.addEventListener('click', function (event) {
        var day = event.target.closest('.calendar-day');
        if (day && !day.disabled) {
            selectDate(day.dataset.date);
        }
    });

    slotGrid.addEventListener('mouseover', function (event) {
        var slot = event.target.closest && event.target.closest('.slot');
        previewRange(slot && slot.classList.contains('is-can-end') ? parseInt(slot.dataset.minutes, 10) : null);
    });
    slotGrid.addEventListener('mouseleave', function () {
        previewRange(null);
    });

    slotGrid.addEventListener('click', function (event) {
        var slot = event.target.closest('.slot');
        if (slot && !slot.disabled) {
            selectSlot(parseInt(slot.dataset.minutes, 10));
        }
    });

    // Step 4 summary follows the support mode chosen in step 3.
    var modeDisplay = document.getElementById('modeDisplay');
    form.querySelectorAll('input[name="mode"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (modeDisplay && radio.checked) {
                modeDisplay.value = radio.value === 'Online' ? 'Virtual' : 'Face-to-face';
            }
        });
    });

    form.addEventListener('submit', function (event) {
        var error = validate();
        if (error) {
            event.preventDefault();
            help.textContent = error;
            help.classList.add('is-error');
        }
    });

    renderCalendar();
    renderSlots();

    if (config.initial) {
        var initialDay = parseDate(config.initial.date);
        view = new Date(initialDay.getFullYear(), initialDay.getMonth(), 1);
        selectDate(config.initial.date, config.initial);
    }
}
