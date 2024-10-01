let startTime;

function startTimer() {
    startTime = new Date();
}

function stopTimer() {
    let endTime = new Date();
    sendTimeData(startTime, endTime);
}

function sendTimeData(start, end) {
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
        },
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