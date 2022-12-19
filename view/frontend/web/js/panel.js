define(['ko','jquery','Magento_Customer/js/customer-data'], function(ko,$,customerData) {
    return function(config) {
        var accountId = config.accountId,
            url = 'https://tracking.recommend.pro/v3/'+accountId+'/recommendation/panel';
        var deviceId = localStorage.getItem("deviceId");
        if (deviceId == null) {
            deviceId = window.deviceId();
            localStorage.setItem("deviceId", deviceId);
        }
        var customer = customerData.get('superbrecommend');
        var customerId = customer().customerId;
        if (customerId !== undefined && customerId !== null) {
            customerId = '"'+customer().customerId+'"';
        } else {
            customerId = null;
        }

        var panelType = config.panel_type;

        if(panelType=='product'){
            var page_type = "Product";
            var panels = '{' +
                '            "id": "'+ config.panel_id +'",' +
                '            "context": {' +
                '                "current": {' +
                '                    "sku": "'+ config.current_sku +'"' +
                '                }' +
                '            }' +
                '        }';
        }

        if(panelType == 'category'){
            var page_type = "Category";
            var panels = '{"id": "'+ config.panel_id +'","context":{"current":{"list_id":"'+config.listId+'"}}}';
        }

        if (panelType == 'cms'){
            var page_type = "CMS";
            var panels = '{"id": "'+ config.panel_id +'","context":{}}';
        }
        if (panelType == 'basket') {
            var page_type = "Basket";
            var panels = '{"id": "' + config.panel_id + '", "context": {"skus": ' + config.skus + '}}';
        }

        var data = '{"device_id":"'+ deviceId +'",' +
            '    "customer_id_hash":'+ customerId +',' +
            '    "store_code": "'+ config.store_code +'",' +
            '    "currency_code": "'+ config.currency_code +'",' +
            '    "price_list": {' +
            '        "code": "'+ config.price_list +'"' +
            '    },' +
            '    "content_type": "json",' +
            '    "page_type": "'+ page_type +'",' +
            '    "panels": [' + panels + ']' +
            '}';

        $.ajax({
            type: "POST",
            url: url,
            data: data,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            async: false,
            success: function(data) {
                result = data['result'][0];
            },
            error: function(errMsg) {
                console.log(errMsg);
            }
        });

	var resultProd = [];
	var resultLength = 0;
	var resultCountry = '';
	if(result !== undefined && result.data !== undefined){
	    resultProd = result.data.products;
	    resultLength = result.data.products.length;
	    resultCountry = result.data.request_country;
	}

	activity = '{"type":"panel_view","data":{"panel_id":"'+config.panel_id+'","products_count":'+resultLength+'}}';

        var dataActivity = '{"customer_id_hash":'+customerId+',' +
                '"store":"'+config.store_code+'",' +
                '"currency":"'+config.currency_code+'",' +
                '"environment":"default",' +
                '"price_list":"default",' +
                '"event_time":1579196874,' +
                '"activity":['+activity+']}';

        window.tracking(dataActivity);

        this.items = resultProd;
        this.request_country = resultCountry;
        this.custom_text = config.custom_text;
        this.title = config.title;
        this.description = config.description;
    }
});
