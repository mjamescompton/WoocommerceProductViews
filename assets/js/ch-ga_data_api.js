(function ($) {

    var CH_GA_Data_API = {

        init: function() {
            this.bindActions();
        },

        render: function ( $value ) {
            var html =  '<p>' + $value + ' ' + ch_ga_data_api.text[0] +'</p>';
            if ( $value > 1 ) {
                $('.ch-ga.ch-ga__pageviews').append(html);
            }
        },

        getPageViews: function() {

        if ( window.location.pathname.indexOf(ch_ga_data_api.shopBase) == -1) {
            return false;
        }

        $.ajax({
            type : 'POST',
            url : ch_ga_data_api.ajaxurl,
            data : {
                action : "get_views_by_page",
                url: window.location.pathname
            },
            success : function( data ) {
                data = JSON.parse(data);
                if (data) {
                    CH_GA_Data_API.render(data.reports[0].data.totals[0].values[0]);
                }
                
            },
        });

        },

        bindActions: function() {
            if ( $('.ch-ga').length ) {
                this.getPageViews();
            }
        }

    }

    CH_GA_Data_API.init();

})(jQuery);