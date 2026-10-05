function formatTime12(time) {
    var parts = time.split(':');
    var hour = parseInt(parts[0], 10);
    var suffix = hour >= 12 ? 'PM' : 'AM';
    return ((hour % 12) || 12) + ':' + parts[1] + ' ' + suffix;
}

function initScheduleForm(volunteerId) {
    var dateInput = document.getElementById('session_date');
    var timeSelect = document.getElementById('session_time');
    var slotHelp = document.getElementById('slotHelp');

    if (!dateInput || !timeSelect) {
        return;
    }

    dateInput.addEventListener('change', function () {
        var chosenDate = dateInput.value;
        timeSelect.innerHTML = '<option value="">Loading times...</option>';
        slotHelp.textContent = '';

        if (!chosenDate) {
            return;
        }

        fetch('get_slots.php?volunteer_id=' + encodeURIComponent(volunteerId) + '&date=' + encodeURIComponent(chosenDate))
            .then(function (response) { return response.json(); })
            .then(function (times) {
                timeSelect.innerHTML = '';

                if (times.length === 0) {
                    timeSelect.innerHTML = '<option value="">No times available on this date</option>';
                    slotHelp.textContent = 'Please try a different date.';
                    return;
                }

                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select a time';
                timeSelect.appendChild(placeholder);

                times.forEach(function (time) {
                    var option = document.createElement('option');
                    option.value = time;
                    option.textContent = formatTime12(time);
                    timeSelect.appendChild(option);
                });

                slotHelp.textContent = times.length + ' time(s) available.';
            })
            .catch(function () {
                timeSelect.innerHTML = '<option value="">Could not load times</option>';
                slotHelp.textContent = 'Please try again.';
            });
    });
}
