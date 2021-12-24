jQuery(document).ready(function() {
    jQuery('.sms-tax').on('click', function(e) {
        e.preventDefault();
        var salesData = {
            status: jQuery('#selectstatus :selected').val(),
            year: jQuery(this).data('year'),
            month: jQuery(this).data('month'),
            day: jQuery(this).data('day'),
        };

        zcJS.ajax({
            url: "ajax.php?act=ajaxMonthlySales&method=getTaxes",
            data: salesData,
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutErrorMessage);
                }
            },
        }).done(function(response) {
            jQuery('#sms-tax-title').html(response.title);
            jQuery('#sms-tax-body').html(response.html);
            jQuery('#smsTaxes').modal();
        });
        return false;
    });
});
