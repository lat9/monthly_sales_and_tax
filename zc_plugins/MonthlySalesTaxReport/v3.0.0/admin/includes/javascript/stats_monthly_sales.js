/**
 * Monthly Sales and Tax Summary mod for Zen Cart
 * Version 2.1.0
 * @copyright Portions Copyright 2004-2024 Zen Cart Team
 * @author Vinos de Frutas Tropicales (lat9)
****************************************************************************
    Copyright (C) 2024  Vinos de Frutas Tropicales (lat9)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/
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
