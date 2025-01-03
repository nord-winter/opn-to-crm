jQuery(document).ready(function($) {
    // Handle test mode toggle
    $('#opn_test_mode').on('change', function() {
        const isTestMode = $(this).is(':checked');
        toggleAPIKeys(isTestMode);
    });

    // Function to toggle API key fields visibility
    function toggleAPIKeys(isTestMode) {
        $('.test-mode-keys').toggleClass('hidden', !isTestMode);
        $('.live-mode-keys').toggleClass('hidden', isTestMode);
    }

    // Copy to clipboard functionality for API keys
    $('.copy-key').on('click', function(e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        const input = document.getElementById(targetId);
        
        input.select();
        document.execCommand('copy');
        
        // Show copied message
        const $this = $(this);
        const originalText = $this.text();
        $this.text('Copied!');
        setTimeout(() => {
            $this.text(originalText);
        }, 2000);
    });

    // Form validation
    $('#opn-to-crm-settings-form').on('submit', function(e) {
        const testMode = $('#opn_test_mode').is(':checked');
        let valid = true;
        let firstError = null;

        // Validate SalesRender settings
        const srRequired = ['sr_company_id', 'sr_api_token', 'sr_project_id'];
        srRequired.forEach(field => {
            const $field = $(`#${field}`);
            if (!$field.val()) {
                valid = false;
                showFieldError($field, 'This field is required');
                firstError = firstError || $field;
            }
        });

        // Validate OPN settings based on mode
        const opnFields = testMode ? 
            ['opn_test_public_key', 'opn_test_secret_key'] :
            ['opn_live_public_key', 'opn_live_secret_key'];

        opnFields.forEach(field => {
            const $field = $(`#${field}`);
            if (!$field.val()) {
                valid = false;
                showFieldError($field, 'This field is required');
                firstError = firstError || $field;
            }
        });

        if (!valid) {
            e.preventDefault();
            firstError.focus();
            return false;
        }
    });

    // Show field error message
    function showFieldError($field, message) {
        const $error = $('<div class="error-message">')
            .text(message)
            .insertAfter($field);
        
        $field.addClass('error');
        
        // Remove error when field is changed
        $field.one('input', function() {
            $field.removeClass('error');
            $error.remove();
        });
    }

    // Test API connection
    $('#test-sr-connection').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Testing...');

        // Get current values
        const data = {
            action: 'sr_test_connection',
            company_id: $('#sr_company_id').val(),
            api_token: $('#sr_api_token').val(),
            _wpnonce: $('#_wpnonce').val()
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                alert('Connection successful!');
            } else {
                alert('Connection failed: ' + response.data.message);
            }
        })
        .fail(function() {
            alert('Connection test failed. Please check your settings.');
        })
        .always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    });

    // Password visibility toggle
    $('.toggle-password').on('click', function() {
        const $this = $(this);
        const $input = $($this.data('target'));
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $this.text('Hide');
        } else {
            $input.attr('type', 'password');
            $this.text('Show');
        }
    });

    // Initialize tooltips
    $('.sr-tooltip').tooltip({
        position: { my: 'left+5 center', at: 'right center' }
    });

    // Save settings via AJAX
    $('#save-settings-ajax').on('click', function(e) {
        e.preventDefault();
        const $form = $('#opn-to-crm-settings-form');
        const $button = $(this);
        
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }

        $button.prop('disabled', true).text('Saving...');

        const formData = new FormData($form[0]);
        formData.append('action', 'sr_save_settings');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotice('Settings saved successfully!', 'success');
                } else {
                    showNotice('Failed to save settings: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Failed to save settings. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Save Settings');
            }
        });
    });

    // Show admin notice
    function showNotice(message, type = 'success') {
        const $notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        const $dismiss = $('<button type="button" class="notice-dismiss">');
        
        $notice.append($dismiss);
        $('.wrap h1').after($notice);

        $dismiss.on('click', function() {
            $notice.fadeOut(200, function() {
                $notice.remove();
            });
        });

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            $notice.fadeOut(200, function() {
                $notice.remove();
            });
        }, 5000);
    }
});