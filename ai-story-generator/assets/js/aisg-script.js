jQuery(document).ready(function($) {
    $('#generate-story').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const $output = $('.story-output');
        const prompt = $('#story-prompt').val().trim();
        
        if (!prompt) {
            $output.html('<div class="error">Please enter a prompt</div>');
            return;
        }
        
        $button.prop('disabled', true);
        $output.html('<div class="loading">Generating story...</div>');
        
        $.ajax({
            url: aisg_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_story',
                security: aisg_vars.nonce,
                prompt: prompt
            },
            success: function(response) {
                if (response.success) {
                    $output.html($('<div/>').text(response.data).html());
                } else {
                    $output.html('<div class="error">Error: ' + response.data + '</div>');
                }
            },
            error: function(xhr) {
                $output.html('<div class="error">Request failed: ' + xhr.statusText + '</div>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
});