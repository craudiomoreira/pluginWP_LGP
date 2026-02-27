jQuery(document).ready(function($){
    var modal = $('#lgpd-consent-modal');
    if (modal.length === 0) return;

    function showModal(){
        modal.addClass('show');
    }
    function hideModal(){
        modal.removeClass('show');
    }

    // if consent cookie already set, do not show
    if (document.cookie.indexOf('lgpd_consent=') === -1) {
        showModal();
    }

    $('#lgpd-accept').on('click', function(){
        $.post(lgpd_ajax.ajax_url, {
            action: 'lgpd_save_consent',
            nonce: lgpd_ajax.nonce
        }, function(res){
            if(res.success){
                var days = parseInt(lgpd_ajax.cookie_duration, 10) || 365;
                document.cookie = 'lgpd_consent=1;path=/;max-age=' + (60*60*24*days);
                hideModal();
                // push datalayer for tracking scripts if available
                if (window.dataLayer && typeof window.dataLayer.push === 'function') {
                    window.dataLayer.push({
                        'event': 'lgpd_consent_given',
                        'lgpd_consent': 'accepted'
                    });
                }
            }
        });
    });

    $('#lgpd-close').on('click', function(){
        var secondaryUrl = modal.data('secondary-url') || lgpd_ajax.privacy_link || '#';
        window.open(secondaryUrl, '_blank');
    });

    // Also handle accent button (accept with custom URL)
    $('#lgpd-accept').each(function(){
        var accentUrl = modal.data('accent-url');
        if (accentUrl) {
            $(this).data('redirect-url', accentUrl);
        }
    });
});