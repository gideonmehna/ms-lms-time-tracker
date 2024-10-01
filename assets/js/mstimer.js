let startTime = new Date().getTime();
let totalTime = 0;
let interval;

// Track time only when the page is focused
function startTimer() {
    interval = setInterval(function() {
        totalTime += 1000;  // Increment time by 1 second
    }, 1000);
}

function stopTimer() {
    clearInterval(interval);
}

// Start timer when the page is focused
window.addEventListener('focus', function() {
    startTimer();
});

// Stop timer when the page loses focus
window.addEventListener('blur', function() {
    stopTimer();
});

// Send data to the backend when the page is unloaded
window.addEventListener('beforeunload', function() {
    let endTime = new Date().getTime();
    let timeSpent = totalTime / 1000;  // Convert to seconds

    // Send AJAX request to save time in the backend
    jQuery.ajax({
        type: 'POST',
        url: mstimer_vars.ajax_url,
        data: {
            action: 'save_student_time',
            user_id: mstimer_vars.user_id,
            course_id: mstimer_vars.course_id,
            time_spent: timeSpent,
        },
    });
});

// Start timer initially
startTimer();
