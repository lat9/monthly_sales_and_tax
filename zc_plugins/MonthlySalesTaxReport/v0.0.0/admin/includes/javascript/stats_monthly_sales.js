/**
 * Monthly Sales and Tax Summary mod for Zen Cart
 * Version 3.0.0
 * @copyright Portions Copyright 2004-2025 Zen Cart Team
 * @author Vinos de Frutas Tropicales (lat9)
*/
jQuery(document).ready(function() {
    jQuery('.sms-tax').on('click', function(e) {
        e.preventDefault();
        var salesData = {
            status: jQuery('#selectstatus :selected').val(),
            year: jQuery(this).data('year'),
            month: jQuery(this).data('month'),
            day: jQuery(this).data('day'),
            state: jQuery('#selected-state :selected').val(),
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
