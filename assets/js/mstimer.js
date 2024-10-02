let startTime;

function startTimer() {
    startTime = new Date();
}

function stopTimer() {
    let endTime = new Date();
    sendTimeData(startTime, endTime);
}

function sendTimeData(start, end) {
    if (!mstimer_vars.user_id || !mstimer_vars.course_id || !mstimer_vars.lesson_id) {
        console.error('Missing required IDs');
        return;
    } else {
        console.log(mstimer_vars.user_id, mstimer_vars.course_id, mstimer_vars.lesson_id)
    }
    let sessionDate = start.toISOString().split('T')[0];
    jQuery.ajax({
        type: 'POST',
        url: mstimer_vars.ajax_url,
        data: {
            action: 'save_student_time',
            user_id: mstimer_vars.user_id,
            course_id: mstimer_vars.course_id,
            lesson_id: mstimer_vars.lesson_id,
            start_time: start.toISOString(),
            end_time: end.toISOString(),
            session_date: sessionDate,
        },
        success: function(response) {
            console.log('AJAX success:', response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
        }
    });
}

// Start timer when the page is focused
window.addEventListener('focus', startTimer);

// Stop timer when the page loses focus
window.addEventListener('blur', stopTimer);

// Send data to the backend when the page is unloaded
window.addEventListener('beforeunload', stopTimer);

// Start timer initially
startTimer();