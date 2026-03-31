(function() {
    // Only run on pages with active processes
    if (!document.querySelector('[id^="process"]')) return;

    function checkCompleted() {
        $.post('ajax.php', {func: 'getProcesses'}, function(data) {
            try {
                var result = (typeof data === 'string') ? JSON.parse(data) : data;
                if (result.status === 'OK' && result.completed > 0) {
                    // A process finished — reload page to show result
                    location.reload();
                }
            } catch(e) {}
        });
    }

    // Check every 5 seconds
    setInterval(checkCompleted, 5000);
})();
