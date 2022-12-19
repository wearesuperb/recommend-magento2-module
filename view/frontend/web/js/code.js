define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($,customerData) {
    "use strict";
    return function (config) {

        customerData.get('recommend-subscribe').subscribe(function (loadedData) {
            if (loadedData && "undefined" !== typeof loadedData.events) {
                for (var eventCounter = 0; eventCounter < loadedData.events.length; eventCounter++) {
                    var eventData = loadedData.events[eventCounter];
                    if ("undefined" !== typeof eventData.eventAdditional && eventData.eventAdditional) {
                        window.generateTracking('subscribe', eventData.eventAdditional);
                    }
                }
                customerData.set('recommend-subscribe', {});
            }
        });

        window.deviceId = function () {
            var d = new Date().getTime();//Timestamp
            var d2 = (performance && performance.now && (performance.now()*1000)) || 0;//Time in microseconds since page-load or 0 if unsupported
            return 'xxxxxxxxyxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16;
                if(d > 0){
                    r = (d + r)%16 | 0;
                    d = Math.floor(d/16);
                } else {
                    r = (d2 + r)%16 | 0;
                    d2 = Math.floor(d2/16);
                }
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        };

        var deviceId = localStorage.getItem("deviceId");
        if (deviceId == null) {
            deviceId = window.deviceId();
            localStorage.setItem("deviceId", deviceId);
        }

        window.tracking = function (data) {
            var accountId = config.accountId;
            var url = 'https://tracking.recommend.pro/v3/'+accountId+'/device/'+deviceId+'/activity';

            $.ajax({
                type: "POST",
                url: url,
                data: data,
                global:false,
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function(data){console.log(data);},
                error: function(errMsg) {
                    console.log(errMsg);
                }
            });
        };

        window.generateTracking = function (event,eventData) {

            var action = config.actionName;
            var currentURL = config.currentURL;
            var customer = customerData.get('superbrecommend');
            var customerId = customer().customerId;
            var storeID = config.websiteId;
            var currency = config.currency;
            var environment = config.environment;
            var orderId = config.orderId;
            var activity = '';

            if (customerId !== null && customerId !== undefined) {
                customerId = '"'+customer().customerId+'"';
            } else {
                customerId = null;
            }

            if(event=='all'){
                activity = '{"type":"pageview","data":{"url":"'+currentURL+'","page_type":"'+action+'"}}';

                if(action=='catalog_product_view'){
                    var sku = config.productSku;
                    activity += ',{"type":"product_view","data":{"sku":"'+sku+'"}}';
                }
                if(action=='catalog_category_view'){
                    var listId = config.categoryId;
                    activity += ',{"type":"list_view","data":{"list_id":"'+listId+'"}}';
                }
                if(action=='checkout_cart_index'){
                    activity += ',{"type":"checkout","data":{"step":"cart"}}';
                }
                if(action=='checkout_index_index'){
                    activity += ',{"type":"checkout","data":{"step":"1"}}';
                }
                if(action=='multishipping_checkout_index'){
                    activity += ',{"type":"checkout","data":{"step":"1"}}';
                }

                if(action=='checkout_onepage_success'){
                    activity += ',{"type":"sale","data":{"order_id_hash":"'+orderId+'"}}';
                }
                if(action=='onepagecheckout_index_success'){
                    activity += ',{"type":"sale","data":{"order_id_hash":"'+orderId+'"}}';
                }
                if(action=='multishipping_checkout_success'){
                    activity += ',{"type":"sale","data":{"order_id_hash":"'+orderId+'"}}';
                }
		var params = new URLSearchParams(document.location.search.substring(1));
	        var recommendsearch = params.get("recommend");

		if (recommendsearch !== null) {
		    var sku = config.productSku;
		    activity += ',{"type":"product_click","data":{"sku":"'+sku+'","source":{"type":"panel","data":{"panel_id":"'+recommendsearch+'"}}}}';
		    params.delete('recommend');
		}
            } else if(event=='addtocart') {
                $.each(eventData.contents, function(key,val) {
                    if (activity.length > 0) {
                        activity += ',{"type":"add_to_cart","data":{"cart_hash":"'+eventData.quote_id+'","sku":"'+val.id+'","variation_sku":"'+ val.variation+'"}}';
                    } else {
                        activity += '{"type":"add_to_cart","data":{"cart_hash":"'+eventData.quote_id+'","sku":"'+val.id+'","variation_sku":"'+val.variation+'"}}';
                    }
                });
            } else if (event == 'subscribe') {
                if (activity.length > 0) {
                    activity += ',{"type":"subscribe","data":{"email_hash":"' + eventData + '"}}';
                } else {
                    activity += '{"type":"subscribe","data":{"email_hash":"' + eventData + '"}}';
                }
            }




            var data = '{"customer_id_hash":'+customerId+',' +
                '"store":"'+storeID+'",' +
                '"currency":"'+currency+'",' +
                '"environment":"'+environment+'",' +
                '"price_list":"default",' +
                '"event_time":1579196874,' +
                '"activity":['+activity+']}';
            window.tracking(data);
        };

        return window.generateTracking('all','');
    }
});
