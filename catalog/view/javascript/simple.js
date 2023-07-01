(function($) {
    var Simple = function() {
    };

    Simple.prototype.human = false;

    Simple.prototype.instances = [];

    Simple.prototype.resources = {
        loading: "catalog/view/image/loading.gif",
        next: "catalog/view/image/next_gray.png",
        nextCompleted: "catalog/view/image/next_green.png"
    };

    Simple.prototype.serializeFields = function($block) {
        var serialized = $block.find("input,select,textarea").serializeArray();

        $block.find('input:checkbox:not(:checked)').each(function() {
            var found = false;

            for (var i in serialized) {
                if (!serialized.hasOwnProperty(i)) continue;

                if (serialized[i].name == this.name) {
                    found = true;
                }
            }

            if (!found) {
                serialized.push({
                    name: this.name,
                    value: this.dataset && this.dataset.uncheckedValue ? this.dataset.uncheckedValue : ''
                });
            }
        });

        return serialized;
    };

    Simple.prototype.getValidationRules = function() {
        var self = this;

        return {
            notEmpty: function($rule, silent) {
                var fieldId = $rule.attr("data-for");
                var fieldType = $rule.attr("data-for-type");

                if (typeof silent === "undefined") {
                    silent = false;
                }

                if (fieldId) {
                    var $field = $(self.params.mainContainer).find("#" + fieldId);

                    if (fieldType == 'checkbox' || fieldType == 'radio') {
                        $field = $(self.params.mainContainer).find("[id*=" + fieldId + "_]");
                    }
                    
                    if ($field.length) {
                        if (!$rule.attr("data-required")) {
                            $rule.hide();
                            return true;
                        }

                        var value = "";

                        if (fieldType == "checkbox" || fieldType == "radio") {
                            var arr = $field.serializeArray();
                            var values = [];
                            for (var i in arr) {
                                if (!arr.hasOwnProperty(i)) continue;
                                values.push(arr[i].value);                                   
                            }
                            value = values.join(',');
                        } else {
                            value = $field.val();
                        }

                        if (!value) {
                            if (!silent) {
                                if (typeof toastr !== 'undefined' && self.params.notificationToasts) {
                                    toastr.error($rule.text());
                                }
                                
                                if (self.params.notificationDefault) {
                                    $rule.show();
                                }
                            }
                            return false;
                        } else {
                            $rule.hide();
                            return true;
                        }
                    }
                }
                return true;
            },
            equal: function($rule, silent) {
                var fieldId = $rule.attr("data-for");
                var equalId = $rule.attr("data-equal");

                if (typeof silent === "undefined") {
                    silent = false;
                }

                if (fieldId && equalId) {
                    var $mainContainer = $(self.params.mainContainer);

                    var $field = $mainContainer.find("#" + fieldId);
                    var $equal = $mainContainer.find("#" + equalId);

                    if ($field.length && $equal.length) {
                        if ($equal.val() != $field.val()) {
                            if (!silent) {
                                if (typeof toastr !== 'undefined' && self.params.notificationToasts) {
                                    toastr.error($rule.text());
                                }
                                
                                if (self.params.notificationDefault) {
                                    $rule.show();
                                }
                            }
                            return false;
                        } else {
                            $rule.hide();
                            return true;
                        }
                    }
                }
                return true;
            },
            byLength: function($rule, silent) {
                var fieldId = $rule.attr("data-for");

                if (typeof silent === "undefined") {
                    silent = false;
                }

                if (fieldId) {
                    var $field = $(self.params.mainContainer).find("#" + fieldId);

                    if ($field.length) {
                        var min = 0;
                        var max = 1000;

                        if ($rule.attr("data-length-min")) {
                            min = ~~$rule.attr("data-length-min");
                        }

                        if ($rule.attr("data-length-max")) {
                            max = ~~$rule.attr("data-length-max");
                        }

                        var value = ($field.val() + '').trim();

                        if (!value && !$rule.attr("data-required")) {
                            $rule.hide();
                            return true;
                        }

                        if (value.length < min || value.length > max) {
                            if (!silent) {
                                if (typeof toastr !== 'undefined' && self.params.notificationToasts) {
                                    toastr.error($rule.text());
                                }
                                
                                if (self.params.notificationDefault) {
                                    $rule.show();
                                }
                            }
                            return false;
                        } else {
                            $rule.hide();

                            return true;
                        }
                    }
                }
                return true;
            },
            regexp: function($rule, silent) {
                var fieldId = $rule.attr("data-for");
                var fieldType = $rule.attr("data-for-type");

                if (typeof silent === "undefined") {
                    silent = false;
                }

                if (fieldId) {
                    var $field = $(self.params.mainContainer).find("#" + fieldId);

                    if (fieldType == 'checkbox' || fieldType == 'radio') {
                        $field = $(self.params.mainContainer).find("[id*=" + fieldId + "_]");
                    }
                    
                    if ($field.length) {
                        var regexp = $rule.attr("data-regexp");

                        if (regexp) {
                            var value = "";

                            if (fieldType == "checkbox" || fieldType == "radio") {
                                var arr = $field.serializeArray();
                                var values = [];
                                for (var i in arr) {
                                    if (!arr.hasOwnProperty(i)) continue;
                                    values.push(arr[i].value);                                   
                                }
                                value = values.join(',');
                            } else {
                                value = $field.val();
                            }

                            if (!value && !$rule.attr("data-required")) {
                                $rule.hide();
                                return true;
                            }

                            try {
                                if (!value.match(regexp)) {
                                    if (!silent) {
                                        if (typeof toastr !== 'undefined' && self.params.notificationToasts) {
                                            toastr.error($rule.text());
                                        }
                                        
                                        if (self.params.notificationDefault) {
                                            $rule.show();
                                        }
                                    }
                                    return false;
                                } else {
                                    $rule.hide();
                                    return true;
                                }
                            } catch (err) {

                            }
                        }
                    }
                }
                return true;
            },
            api: function($rule, silent) {
                var fieldId = $rule.attr("data-for");
                var fieldType = $rule.attr("data-for-type");

                var $mainContainer = $(self.params.mainContainer);

                if (typeof silent === "undefined") {
                    silent = false;
                }

                if (fieldId) {
                    var $field = $mainContainer.find("#" + fieldId);

                    if (fieldType == 'checkbox' || fieldType == 'radio') {
                        $field = $(self.params.mainContainer).find("[id*=" + fieldId + "_]");
                    }

                    if ($field.length) {
                        var method = $rule.attr("data-method");

                        if (method) {
                            var filter = "";

                            if ($rule.attr("data-filter")) {
                                var filterType = $rule.attr("data-filter-type");

                                var $filter = $mainContainer.find("#" + $rule.attr("data-filter"));

                                if (filterType == 'checkbox' || filterType == 'radio') {
                                    $filter = $mainContainer.find("[id*=" + $rule.attr("data-filter") + "_]");
                                }

                                if ($filter.length) {
                                    if (filterType == "radio" || filterType == "checkbox") {
                                        var arr = $filter.serializeArray();
                                        var values = [];
                                        for (var i in arr) {
                                            if (!arr.hasOwnProperty(i)) continue;
                                            values.push(arr[i].value);                                   
                                        }
                                        filter = values.join(',');
                                    } else {
                                        filter = $filter.val();
                                    }
                                } else if ($rule.attr("data-filter-value")) {
                                    filter = $rule.attr("data-filter-value");
                                }
                            }

                            var custom = $rule.attr("data-custom") ? true : false;

                            var deferred = $.Deferred();

                            var value = "";

                            if (fieldType == "checkbox" || fieldType == "radio") {
                                var arr = $field.serializeArray();
                                var values = [];
                                for (var i in arr) {
                                    if (!arr.hasOwnProperty(i)) continue;
                                    values.push(arr[i].value);                                   
                                }
                                value = values.join(',');
                            } else {
                                value = $field.val();
                            }

                            $.get("index.php?" + self.params.additionalParams + "route=common/simple_connector/validate&method=" + method + "&filter=" + encodeURIComponent(filter) + "&value=" + encodeURIComponent(value) + (custom ? "&custom=1 " : ""), function(data) {
                                data = data.trim();
                                
                                if (data == "invalid") {
                                    if (!silent) {
                                        if (typeof toastr !== 'undefined' && self.params.notificationToasts) {
                                            toastr.error($rule.text());
                                        }
                                        
                                        if (self.params.notificationDefault) {
                                            $rule.show();
                                        }
                                    }
                                    deferred.resolve(false);
                                } else {
                                    $rule.hide();
                                    deferred.resolve(true);
                                }
                            });

                            return deferred.promise();
                        }
                    }
                }

                return true;
            }
        };
    };

    Simple.prototype.initValidationRules = function() {
        var self = this;
        var $mainContainer = $(self.params.mainContainer);

        $mainContainer.find(".simplecheckout-rule-group").each(function() {
            var $ruleGroup = $(this);
            var fieldId = $(this).attr("data-for");
            var promises = [];

            if (fieldId) {
                var $field = $mainContainer.find("#" + fieldId);
                
                if (!$field.length) {
                    $field = $mainContainer.find("[id*=" + fieldId + "_]");
                }

                if ($field.length) {
                    var checker = function() {
                        var result = true;

                        $ruleGroup.find(".simplecheckout-rule").each(function() {
                            var $rule = $(this);
                            var type = $(this).attr("data-rule");
                            var rules = self.getValidationRules();
                            
                            if (typeof rules[type] === "function") {
                                var promise = $.when(rules[type]($rule)).then(function(ruleResult){
                                    if (!ruleResult) {
                                        result = false;
                                    }

                                    return $.when(ruleResult);
                                });

                                promises.push(promise);
                            }
                        });

                        $.when.apply($, promises).then(function() {
                            $field.attr("data-valid", result ? "true" : "false");

                            if (!result) {
                                $field.parents(".form-group").addClass("has-error")
                            } else  {
                                $field.parents(".form-group").removeClass("has-error")
                            }
                        });
                    };

                    var $pairField = false;
                    
                    $ruleGroup.find(".simplecheckout-rule").each(function() {
                        if ($(this).attr("data-rule") == 'equal' && $(this).attr("data-equal")) {
                            if ($("#" + $(this).attr("data-equal")).length) {
                                $pairField = $("#" + $(this).attr("data-equal"));
                            }
                        }
                    });

                    if (!self.params.notificationToasts && $field.attr('data-validate-on') && typeof $field[$field.attr('data-validate-on')] === 'function') {
                        $field[$field.attr('data-validate-on')](checker);

                        if ($pairField) {
                            $pairField[$field.attr('data-validate-on')](checker);
                        }                  
                    } else {
                        $field.change(checker);

                        if ($pairField) {
                            $pairField.change(function() { 
                                if ($field.val()) {
                                    checker.call($field);
                                }
                            });
                        }
                    }                    
                }
            }
        });
    };

    Simple.prototype.checkRules = function(container, silent) {
        var self = this;
        var fields = {};
        var resultAll = true;
        var allPromises = [];
        var $mainContainer =  $(self.params.mainContainer);
        var $container = $mainContainer;

        if (container) {
            $container = $mainContainer.find(container);
        }

        if (typeof silent === "undefined") {
            silent = false;
        }

        if ($container.length && ($container.is(":visible") || $container.find('*').is(":visible"))) {
            $container.find(".simplecheckout-rule-group").each(function() {
                var ruleGroupResult = true;
                var promises = [];
                var $ruleGroup = $(this);
                var fieldId = $(this).attr("data-for");

                if (fieldId) {
                    var $field = $mainContainer.find("#" + fieldId);

                    if (!$field.length) {
                        $field = $mainContainer.find("[id*=" + fieldId + "_]");
                    }

                    if ($field.length) {
                        $ruleGroup.find(".simplecheckout-rule").each(function() {
                            var $rule = $(this);
                            var type = $(this).attr("data-rule");
                            var rules = self.getValidationRules();

                            if (typeof rules[type] === "function") {
                                var promise = $.when(rules[type]($rule, silent)).then(function(ruleResult){
                                    if (!ruleResult) {
                                        ruleGroupResult = false;
                                    }

                                    return $.when(ruleResult);
                                });

                                promises.push(promise);
                            }
                        });

                        var groupPromise = $.when.apply($, promises).then(function() {
                            $field.attr("data-valid", ruleGroupResult ? "true" : "false");

                            if (!ruleGroupResult) {
                                $field.parents(".form-group").addClass("has-error")
                            } else  {
                                $field.parents(".form-group").removeClass("has-error")
                            }

                            if (!ruleGroupResult) {
                                resultAll = false;
                            }
                        });

                        allPromises.push(groupPromise);
                    }
                }
            });
        }

        var deferred = $.Deferred();

        $.when.apply($, allPromises).then(function() {
            deferred.resolve(resultAll);
        });

        return deferred.promise();
    };

    Simple.prototype.setAddressFields = function(block, countryId, zoneId, city, postcode, callbackAfterChanging) {
        var self = this;
        var $mainContainer = $(self.params.mainContainer);

        var setFields = function() {
            if (countryId) {
                $mainContainer.find("#" + block + "_country_id").val(countryId);
            }
            if (zoneId) {
                $mainContainer.find("#" + block + "_zone_id").val(zoneId);
            }
            if (city) {
                $mainContainer.find("#" + block + "_city").val(city);
            }
            if (postcode) {
                $mainContainer.find("#" + block + "_postcode").val(postcode);
            }

            if (typeof callbackAfterChanging === "function") {
                callbackAfterChanging();
            } else if (typeof reloadAll === "function") {
                reloadAll();
            }
        };

        if ($mainContainer.find("#" + block + "_country_id").val() != countryId) {
            $mainContainer.find("#" + block + "_zone_id").load("index.php?" + self.params.additionalParams + "route=common/simple_connector/zone&country_id=" + countryId, function() {
                setFields();
            });
        } else {
            setFields();
        }
    };

    Simple.prototype.initFileUploader = function(beforeUploading, afterUploading) {
        var self = this;
        var $mainContainer = $(self.params.mainContainer);

        $mainContainer.find("[data-file]").each(function() {
            var hiddenInputId = $(this).attr("data-file");
            var fileNameId = hiddenInputId ? "text_" + hiddenInputId : "";

            $(this).on("click", function() {
                $("#form-upload").remove();

                $("body").prepend("<form enctype='multipart/form-data' id='form-upload' style='display: none;'><input type='file' name='file' /></form>");

                $("#form-upload input[name='file']").trigger("click");

                if (typeof fileUploaderTimer !== "undefined") {
                    clearInterval(fileUploaderTimer);
                }

                fileUploaderTimer = setInterval(function() {
                    if ($("#form-upload input[name='file']").val() != "") {
                        clearInterval(fileUploaderTimer);

                        $.ajax({
                            url: "index.php?" + self.params.additionalParams + "route=common/simple_connector/upload",
                            type: "post",
                            dataType: "json",
                            data: new FormData($("#form-upload")[0]),
                            cache: false,
                            contentType: false,
                            processData: false,
                            beforeSend: function() {
                                if (typeof beforeUploading === "function") {
                                    beforeUploading();
                                }
                            },
                            complete: function() {
                                $("#form-upload").remove();

                                if (typeof afterUploading === "function") {
                                    afterUploading();
                                }
                            },
                            success: function(json) {
                                if (json["file"]) {
                                    if (hiddenInputId) {
                                        $mainContainer.find("#" + hiddenInputId).val(json["file"]).trigger('change');
                                    }
                                    if (fileNameId) {
                                        var fname = typeof json["filename"] !== "undefined" && json["filename"] ? json["filename"] : file;
                                        $mainContainer.find("#" + fileNameId).text(fname);
                                    }
                                }

                                if (json["error"]) {
                                    if (hiddenInputId) {
                                        $mainContainer.find("#" + hiddenInputId).attr("value", "");
                                    }
                                    if (fileNameId) {
                                        $mainContainer.find("#" + fileNameId).text(json["error"]);
                                    }
                                }
                            },
                            error: function(xhr, ajaxOptions, thrownError) {
                                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                            }
                        });
                    }
                }, 500);
            });            
        });
    };

    Simple.prototype.initAutocomplete = function(callbackAfterChanging) {
        var self = this;
        var $fields = $(self.params.mainContainer).find("#payment_address_city, #shipping_address_city, #register_city, #address_city");
        if (typeof($fields.autocomplete) !== "undefined") {
            $fields.each(function() {
                var $field = $(this)
                var tmp = $field.attr("data-onchange");
                if (tmp) {
                    $field.removeAttr("data-onchange");
                    $field.removeAttr("data-reload-payment-form");
                    $field.attr("data-onchange-delayed", tmp);
                }

                $field.on("keydown", function(){
                    $field.data("status", "started");
                });
            
                $field.autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: "index.php?" + self.params.additionalParams + "route=common/simple_connector/geo",
                            dataType: "json",
                            data: {
                                term: typeof request.term !== "undefined" ? request.term : request
                            },
                            success: function(data) {
                                response($.map(data, function(item) {
                                    if (typeof request.term === "undefined") {
                                        return {
                                            id: item.id,
                                            label: item.full,
                                            value: item.id,
                                            postcode: item.postcode,
                                            zone_id: item.zone_id,
                                            country_id: item.country_id,
                                            city: item.city
                                        };
                                    } else {
                                        return {
                                            id: item.id,
                                            label: item.full,
                                            value: item.city,
                                            postcode: item.postcode,
                                            zone_id: item.zone_id,
                                            country_id: item.country_id,
                                            city: item.city
                                        };
                                    }

                                }));
                            }
                        });
                    },
                    minLength: 2,
                    delay: 300,
                    close: function( event, ui ) {
                        if ($field.data("status") == "started") {
                            //$field.val("");
                            callbackAfterChanging($field);
                        }
                    },
                    select: function(event, ui) {
                        $field.data("status", "selected");
                        var name = $field.attr("name");
                        var from = name.substr(0, name.indexOf("["));
                        
                        if (ui !== undefined) {
                            self.setAddressFields(from, ui.item.country_id, ui.item.zone_id, ui.item.city, ui.item.postcode, function() {
                                callbackAfterChanging($field);
                            });
                        } else {
                            self.setAddressFields(from, event.country_id, event.zone_id, event.city, event.postcode, function() {
                                callbackAfterChanging($field);
                            });
                        }
                    }
                });
            });
        }
    };

    Simple.prototype.initPopups = function() {
        var self = this;
        var $mainContainer = $(self.params.mainContainer);

        if (typeof($.fancybox) == "function") {
            $mainContainer.find(".fancybox").fancybox({
                width: 560,
                height: 560,
                autoDimensions: false
            });
        }

        if (typeof($.colorbox) == "function") {
            $mainContainer.find(".colorbox").colorbox({
                width: 560,
                height: 560
            });
        }

        if (typeof($.prettyPhoto) !== "undefined") {
            $mainContainer.find("a[rel^='prettyPhoto']").prettyPhoto({
                theme: "light_square",
                opacity: 0.5,
                social_tools: "",
                deeplinking: false
            });
        }
    };

    Simple.prototype.initTooltips = function(bootstrap) {
        var self = this;
        var $mainContainer = $(self.params.mainContainer);

        $mainContainer.find(".simplecheckout-tooltip[data-for]").each(function() {
            var $target = $mainContainer.find("#" + $(this).attr("data-for"));

            if ($target.length) {
                if ($target.attr("data-file")) {
                    $(this).show();
                } else {
                    if (bootstrap && typeof $target.tooltip === "function") {
                        $target.tooltip({
                            html: true,
                            title: $(this).html(),
                            placement: 'bottom',
                            container: 'body'
                        });
                    } else if (typeof $target.easyTooltip === "function") {
                        $target.easyTooltip({
                            useElement: self.params.mainContainer + " .simplecheckout-tooltip[data-for='" + $(this).attr("data-for") + "']",
                            clickRemove: true
                        });
                    }
                }
            }
        });
    };

    Simple.prototype.initMasks = function() {
        var self = this;
        var $mainContainer = $(self.params.mainContainer);

        if (typeof $(document).inputmask !== "undefined" /*&& !window.localStorage.getItem('inputmaskFailed')*/) {
            var masked = [];
            $mainContainer.find("input[data-simple-mask]").each(function(indx) {
                var mask = $(this).attr("data-simple-mask");
                var id = $(this).attr("id");
                if (mask && id) {
                    masked[masked.length] = [id, mask];
                }
            });
            try {
                for (var i = 0; i < masked.length; i++) {
                    var id = masked[i][0];
                    var mask = masked[i][1];

                    $mainContainer.find("input[id=" + id + "]")
                        .inputmask({
                            mask: mask,
                            clearMaskOnLostFocus: true,
                            clearIncomplete: true,
                        });
                        /*.on('change', function() {
                            if ($(this).val().indexOf('_') > -1) {
                                window.localStorage.setItem('inputmaskFailed', true);
                                $(this).inputmask('remove');
                            }                      
                        });*/
                }
            } catch (err) {}
        }
    };

    Simple.prototype.initSelect2 = function() {
        var self = this;
       
        if (typeof $(document).select2 !== "undefined") {
            $(self.params.mainContainer).find("select[data-type=select2]").each(function() {
                $(this).select2({
                    
                });
            });
        }
    };

    Simple.prototype.initDatepickers = function(callbackAfterChanging) {
        var self = this;
        var days = false;

        var checkWeekendAndHoliday = function(date) {
            if (typeof $.datepicker !== "undefined") {
                var noWeekend = $.datepicker.noWeekends(date);
            } else {
                var noWeekend = [(date.getDay() == 0 || date.getDay() == 6), ""];
            }

            if (noWeekend[0]) {
                return checkNationalDay(date);
            } else {
                return noWeekend;
            }
        };

        var checkNationalDay = function(date) {
            var days = [
                [1, 1, "ru"],
                [1, 7, "ru"],
                [5, 9, "ru"]
            ];

            for (i = 0; i < days.length; i++) {
                if (date.getMonth() == days[i][0] - 1 && date.getDate() == days[i][1]) {
                    return [false, days[i][2] + "_day"];
                }
            }
            return [true, ""];
        };

        var addDays = function(add, onlyWeekdays) {
            var result = add | 0;
            var self = this;
            if (onlyWeekdays) {
                var i = 1;
                while (i <= result) {
                    var d = new Date();
                    d.setDate(d.getDate() + i);
                    var test = checkWeekendAndHoliday(d);
                    if (!test[0]) {
                        result++;
                    }
                    i++;
                }
            }

            return result;
        };

        var checkDays = function(date) {
            for (var i = 0; i < days.length; i++) {
                if (date.getDay() == days[i]) {
                    return [true, ""];
                }
            }

            return [false, ""];
        };

        $(self.params.mainContainer).find("input[type=date],input[type=datetime],input[data-type=date],input[data-type=datetime]").each(function() {
            var onlyWeekdays = $(this).attr("data-weekdays-only") ? true : false,
                min = new Date(),
                max = new Date();
            
            if ($(this).attr("data-days-only")) {
                days = $(this).attr("data-days-only").split(",");
                onlyWeekdays = false;
            }
            
            if ($(this).attr("data-start-day")) {
                min = $(this).attr("data-start-day");
            } else if ($(this).attr("data-start-after")) {
                min.setDate(min.getDate() + addDays($(this).attr("data-start-after"), onlyWeekdays));
            }
            
            if ($(this).attr("data-end-day")) {
                max = $(this).attr("data-end-day");
            } else if ($(this).attr("data-end-after")) {
                max.setDate(max.getDate() + addDays($(this).attr("data-end-after"), onlyWeekdays));
            }
            
            if (typeof($(this).datetimepicker) !== "undefined") {
                var disabledDays = [];

                if (onlyWeekdays || typeof days.length !== "undefined") {
                    var useDays = false;

                    if (days.length !== "undefined" && days.length) {
                        useDays = true;
                    }

                    for (var i = 0; i < 7; i++) {
                        if (((i == 6 || i == 0) && onlyWeekdays) || (useDays && $.inArray(i + "", days) == -1)) {
                            disabledDays.push(i);
                        }
                    }
                }
                
                var $el = $(this);
 
                $el.datetimepicker({
                    pickDate: true,
                    pickTime: $el.attr("data-type") == "datetime" ? true : false,
                    showTimepicker: false,
                    daysOfWeekDisabled: disabledDays,
                    firstDay: 1,
                    beforeShowDay: onlyWeekdays ? checkWeekendAndHoliday : (days ? checkDays : null),
                    minDate: min ? min : null,
                    maxDate: max ? max : null,
                    useCurrent: false,
                    showButtonPanel: false,
                    language: self.params.languageCode,
                    locale: self.params.languageCode,
                    onSelect: function() {
                        $el.datetimepicker('hide');
                    }
                }).on('dp.change', function(dateText, inst) {
                    if (typeof callbackAfterChanging === "function") {
                        callbackAfterChanging($el);
                    }
                });

                $el.next().on('click', function () {
                    $el.focus();
                });
            } else if (typeof($(this).datepicker) !== "undefined") {
                $(this).datepicker({
                    firstDay: 1,
                    beforeShowDay: onlyWeekdays ? checkWeekendAndHoliday : (days ? checkDays : null),
                    minDate: min ? min : null,
                    maxDate: max ? max : null,
                    onSelect: function(dateText, inst) {
                        if (typeof callbackAfterChanging === "function") {
                            callbackAfterChanging($(this));
                        }
                    }
                });
            }
        });
    };

    Simple.prototype.initTimepickers = function(callbackAfterChanging) {
        var self = this;
        var min = "";

        if ($(this).attr("data-min-time")) {
            min = ~~ ($(this).attr("data-min-time").split(":")[0]);
        }

        var max = "";

        if ($(this).attr("data-max-time")) {
            max = ~~ ($(this).attr("data-max-time").split(":")[0]);
        }

        var onlyHours = $(this).attr("data-hours-only") ? true : false;

        $(self.params.mainContainer).find("input[type=time],input[data-type=time]").each(function() {
            if (typeof($(this).datetimepicker) !== "undefined") {
                var $el = $(this);
                
                $el.datetimepicker({
                    pickDate: false,
                    //language: document.cookie.match(new RegExp('language=([^;]+)')) && document.cookie.match(new RegExp('language=([^;]+)'))[1],
                    language: self.params.languageCode,
                    locale: self.params.languageCode,
                    pickTime: true,
                    showMinute: !onlyHours,
                    useCurrent: false
                }).on('dp.change', function(dateText, inst) {
                    if (typeof callbackAfterChanging === "function") {
                        callbackAfterChanging($el);
                    }
                });

                $el.next().on('click', function () {
                    $el.focus();
                });
            } else if (typeof($(this).timepicker) !== "undefined") {
                $(this).timepicker({
                    hourMin: min,
                    hourMax: max,
                    showMinute: !onlyHours,
                    onSelect: function(datetimeText, datepickerInstance) {
                        if (typeof callbackAfterChanging === "function") {
                            callbackAfterChanging($(this));
                        }
                    },
                    onClose: function() {
                        if (typeof callbackAfterChanging === "function") {
                            callbackAfterChanging($(this));
                        }
                    }
                });
            }
        });
    };

    Simple.prototype.checkIsHuman = function() {
        var self = this;
        var timeoutId = 0;

        $("body").on("mousemove", function() {
            if (!self.human) {
                clearTimeout(timeoutId);

                timeoutId = window.setTimeout(function(){
                    self.human = true;
                    $.get("index.php?" + self.params.additionalParams + "route=common/simple_connector/human");
                }, 300);
            }
        });
    };

    $.ajaxSetup({ cache: false });

    window.Simple = Simple;
})(jQuery || $);

if (typeof String.prototype.trim !== "function") {
    String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g, "");
    };
}

function includeScript(url) {
    document.write("<script type='text/javascript' src='" + url + "'></script>");
}

function includeStyle(url) {
    document.write("<link rel='stylesheet' type='text/css' href='" + url + "' media='screen' />");
}

function bind(func, context) {
    return function() {
        return func.apply(context, arguments);
    };
}

function inherit(proto) {
    function F() {}
    F.prototype = proto;
    var object = new F();
    return object;
}

function reloadAll() {
    if (typeof Simple.prototype.instances !== "undefined") {
        for (var i in Simple.prototype.instances) {
            if (!Simple.prototype.instances.hasOwnProperty(i)) continue;

            if (typeof Simple.prototype.instances[i].reloadAll === "function") {
                Simple.prototype.instances[i].reloadAll();
            }
        }
    }
}

function reloadBlock() {
    if (typeof Simple.prototype.instances !== "undefined") {
        for (var i in Simple.prototype.instances) {
            if (!Simple.prototype.instances.hasOwnProperty(i)) continue;

            if (typeof Simple.prototype.instances[i].reloadBlock === "function") {
                Simple.prototype.instances[i].reloadBlock.apply(Simple.prototype.instances[i], arguments);
            }
        }
    }
}

if (!window.localStorage) {
  window.localStorage = {
    getItem: function (sKey) {
      if (!sKey || !this.hasOwnProperty(sKey)) { return null; }
      return unescape(document.cookie.replace(new RegExp("(?:^|.*;\\s*)" + escape(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*((?:[^;](?!;))*[^;]?).*"), "$1"));
    },
    key: function (nKeyId) {
      return unescape(document.cookie.replace(/\s*\=(?:.(?!;))*$/, "").split(/\s*\=(?:[^;](?!;))*[^;]?;\s*/)[nKeyId]);
    },
    setItem: function (sKey, sValue) {
      if(!sKey) { return; }
      document.cookie = escape(sKey) + "=" + escape(sValue) + "; expires=Tue, 19 Jan 2038 03:14:07 GMT; path=/";
      this.length = document.cookie.match(/\=/g).length;
    },
    length: 0,
    removeItem: function (sKey) {
      if (!sKey || !this.hasOwnProperty(sKey)) { return; }
      document.cookie = escape(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
      this.length--;
    },
    hasOwnProperty: function (sKey) {
      return (new RegExp("(?:^|;\\s*)" + escape(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
    }
  };
  window.localStorage.length = (document.cookie.match(/\=/g) || window.localStorage).length;
}
