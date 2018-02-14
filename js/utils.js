// Toggle the "enabled" fields in admin lists
var LIBR_toggle = function(cbox, id, type, component) {
    oldval = cbox.checked ? 0 : 1;
    var dataS = {
        "action" : "toggle",
        "id": id,
        "type": type,
        "oldval": oldval,
        "component": component,
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: site_admin_url + "/plugins/library/ajax.php",
        data: data,
        success: function(result) {
            cbox.checked = result.newval == 1 ? true : false;
            try {
                $.UIkit.notify("<i class='uk-icon uk-icon-check'></i>&nbsp;" + result.statusMessage, {timeout: 1000,pos:'top-center'});
            }
            catch(err) {
                console.log(result.statusMessage);
            }
        }
    });
    return false;
};

/**
*   Look up an ISBN from openlibrary.org
*   Sets form field values from the lookup results.
*
*   @param  string  isbn    Item ISBN number
*/
var LIBR_openlibLookup = function(isbn) {
    var key = "ISBN:" + isbn;
    var dataS = {
        "bibkeys" : key,
        "jscmd" : "data",
        "format": "json",
    };
    data = $.param(dataS);
    $.ajax({
        type: "GET",
        dataType: "json",
        url: "https://openlibrary.org/api/books",
        data: data,
        success: function(result, textStatus, jqXHR) {
            try {
                var res = result[key];
                LIBR_updateField(res.authors[0].name, "author");
                LIBR_updateField(res.publishers[0].name, "publisher");
                LIBR_updateField(res.title, "item_name");
                LIBR_updateField(res.subtitle, "short_desc");
            }
            catch(err) {
                $.UIkit.notify("OpenLibrary lookup error", {timeout: 1000,pos:'top-center'});
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
    });
    return false;
};

/**
*   Update a form field from the library lookup results
*
*   @param  string  value   Value to set in field
*   @param  string  elem_id Form element ID
*/
function LIBR_updateField(value, elem_id)
{
    if (typeof(value) != "undefined")
        document.getElementById(elem_id).value = value;
}

var LIBR_ajaxReserve = function(item_id, action)
{
    var dataS = {
        "item_id" : item_id,
        "action" : action,
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: glfusionSiteUrl + "/library/ajax.php",
        data: data,
        success: function(result, textStatus, jqXHR) {
            try {
                if (result.error == 0) {
                    elem = document.getElementById("avail_" + result.item_id);
                    if (typeof(elem) != 'undefined') {
                        elem.innerHTML=result.html;
                    }
                } else {
                    $.UIkit.notify("An error occurred", {timeout: 1000,pos:'top-center'});
                }
            }
            catch(err) {
                console.log("ajaxReserve error");
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
    });
    return false;
};

