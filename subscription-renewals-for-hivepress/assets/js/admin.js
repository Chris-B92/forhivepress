jQuery(document).ready(function($) {
    // **Tab Navigation** - Handles tab switching with event delegation
    $(document).on('click', '.nav-tab', function(e) {
        e.preventDefault();
        $('.tab-content').hide();
        $('.nav-tab').removeClass('nav-tab-active');
        $($(this).attr('href')).show();
        $(this).addClass('nav-tab-active');
        localStorage.setItem('hpsr_active_tab', $(this).attr('href'));
    });

    // Set the initial active tab based on saved preference or default to the first tab
    var activeTab = localStorage.getItem('hpsr_active_tab');
    if (activeTab && $(activeTab).length) {
        $('.nav-tab[href="' + activeTab + '"]').trigger('click');
    } else {
        $('.nav-tab:first').trigger('click');
    }

    // **User-Specific Sync Button** - Syncs a specific user's data
    $(document).on('click', '#hpsr-sync-user', function(e) {
        e.preventDefault();
        var $button = $(this);
        var userId = $button.data('user-id');
        var originalText = $button.text();

        if (!userId) {
            showNotice('User ID not found.', 'error');
            return;
        }

        if (typeof hpsr_vars === 'undefined') {
            showNotice('Error: AJAX configuration missing.', 'error');
            return;
        }

        $button.prop('disabled', true).text(hpsr_vars.i18n.process);

        $.ajax({
            url: hpsr_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'hpsr_sync_user',
                nonce: hpsr_vars.nonce,
                user_id: userId
            },
            success: function(response) {
                $button.prop('disabled', false).text(originalText);
                if (response.success) {
                    showNotice(response.data, 'success');
                    var searchValue = $('#hpsr-user-search-input').val().trim();
                    if (searchValue && $('#hpsr-user-lookup').length) {
                        $('#hpsr-user-lookup').trigger('click');
                    }
                } else {
                    showNotice(response.data || 'Unknown error occurred.', 'error');
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false).text(originalText);
                showNotice('An error occurred: ' + (xhr.responseText || status), 'error');
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // **Debug Expiry Button** - Displays debug info for a user's expiry
    $(document).on('click', '#hpsr-debug-expiry', function(e) {
        e.preventDefault();
        var $button = $(this);
        var userId = $button.data('user-id');
        var originalText = $button.text();

        if (!userId) {
            showNotice('User ID not found.', 'error');
            return;
        }

        if (typeof hpsr_vars === 'undefined') {
            showNotice('Error: AJAX configuration missing.', 'error');
            return;
        }

        $button.prop('disabled', true).text(hpsr_vars.i18n.process);

        $.ajax({
            url: hpsr_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'hpsr_debug_expiry',
                nonce: hpsr_vars.nonce,
                user_id: userId
            },
            success: function(response) {
                $button.prop('disabled', false).text(originalText);
                if (response.success) {
                    var $debugInfo = $('<div class="hpsr-debug-info"></div>').html(response.data);
                    $('#hpsr-user-results').append($debugInfo);
                    $('html, body').animate({
                        scrollTop: $debugInfo.offset().top - 50
                    }, 500);
                } else {
                    showNotice(response.data || 'Unknown error occurred.', 'error');
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false).text(originalText);
                showNotice('An error occurred: ' + (xhr.responseText || status), 'error');
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // **AJAX Action Buttons** - Handles generic actions like clearing logs or syncing all
    $(document).on('click', 'button[data-action]', function(e) {
        e.preventDefault();
        var $button = $(this);
        var action = $button.data('action');
        var originalText = $button.text();

        if (typeof hpsr_vars === 'undefined') {
            showNotice('Error: AJAX configuration missing.', 'error');
            return;
        }

        $button.prop('disabled', true).text(hpsr_vars.i18n.process);

        $.ajax({
            url: hpsr_vars.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: hpsr_vars.nonce
            },
            success: function(response) {
                $button.prop('disabled', false).text(originalText);
                if (response.success) {
                    showNotice(response.data, 'success');
                    if (action === 'hpsr_clear_logs' && $('#hpsr-logs').length) {
                        $('#hpsr-logs').val('');
                    } else if (action === 'hpsr_reschedule_cron' || action === 'hpsr_sync_all') {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showNotice(response.data || 'Unknown error occurred.', 'error');
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false).text(originalText);
                showNotice('An error occurred: ' + (xhr.responseText || status), 'error');
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // **User Lookup with Enter Key Support** - Triggers search on Enter key press
    if ($('#hpsr-user-search-input').length) {
        $(document).on('keypress', '#hpsr-user-search-input', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#hpsr-user-lookup').click();
            }
        });
    }

    // **User Lookup Button** - Searches for users based on input
    $(document).on('click', '#hpsr-user-lookup', function(e) {
        e.preventDefault();
        var $button = $(this);
        var searchValue = $('#hpsr-user-search-input').val().trim();

        if (!searchValue) {
            showNotice('Please enter a search term.', 'error');
            return;
        }

        if (typeof hpsr_vars === 'undefined') {
            showNotice('Error: AJAX configuration missing.', 'error');
            return;
        }

        $button.prop('disabled', true).text(hpsr_vars.i18n.process);
        $('#hpsr-user-results').html('');

        $.ajax({
            url: hpsr_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'hpsr_user_lookup',
                nonce: hpsr_vars.nonce,
                search: searchValue
            },
            success: function(response) {
                $button.prop('disabled', false).text(hpsr_vars.i18n.search);
                if (response.success) {
                    $('#hpsr-user-results').html(response.data);
                    $('html, body').animate({
                        scrollTop: $('#hpsr-user-results').offset().top - 50
                    }, 500);
                } else {
                    showNotice(response.data || 'Unknown error occurred.', 'error');
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false).text(hpsr_vars.i18n.search);
                showNotice('An error occurred: ' + (xhr.responseText || status), 'error');
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // **Save Logs Button** - Downloads log content as a file
    $(document).on('click', '#hpsr-save-logs', function(e) {
        e.preventDefault();
        var logContent = $('#hpsr-logs').val();
        if (!logContent.trim()) {
            showNotice('No log content to save.', 'error');
            return;
        }

        var filename = 'subscription-renewals-logs-' + getFormattedDate() + '.txt';
        var blob = new Blob([logContent], { type: 'text/plain' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');

        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();

        setTimeout(function() {
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }, 100);

        showNotice('Logs saved successfully.', 'success');
    });

    // **Copy Logs Button** - Copies log content to clipboard
    $(document).on('click', '#hpsr-copy-logs', function(e) {
        e.preventDefault();
        var $textarea = $('#hpsr-logs');

        if (!$textarea.val().trim()) {
            showNotice('No log content to copy.', 'error');
            return;
        }

        $textarea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showNotice('Logs copied to clipboard.', 'success');
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText($textarea.val())
                    .then(function() {
                        showNotice('Logs copied to clipboard.', 'success');
                    })
                    .catch(function(err) {
                        showNotice('Failed to copy logs: ' + err, 'error');
                    });
            } else {
                showNotice('Failed to copy logs.', 'error');
            }
        } catch (err) {
            showNotice('Failed to copy logs: ' + err, 'error');
        }
    });

    // **Helper Functions**

    // Displays a dismissible notice to the user
    function showNotice(message, type) {
        if (!message) {
            return;
        }

        $('.hpsr-notice').remove();
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible hpsr-notice"><p>' + message + '</p></div>');
        var $button = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        $button.on('click', function() {
            $notice.remove();
        });
        $notice.append($button);

        if ($('.wrap > h1').length) {
            $('.wrap > h1').after($notice);
            $('html, body').animate({
                scrollTop: $('.wrap > h1').offset().top - 50
            }, 300);
        } else {
            $('.wrap').prepend($notice);
        }

        setTimeout(function() {
            $notice.fadeOut(400, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Generates a formatted date string for filenames
    function getFormattedDate() {
        var date = new Date();
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        var seconds = String(date.getSeconds()).padStart(2, '0');
        return year + '-' + month + '-' + day + '-' + hours + '-' + minutes + '-' + seconds;
    }
});