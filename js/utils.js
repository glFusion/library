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
*   Look up an ISBN from local AJAX, which relies on the Amazon Astore plugin.
*   Sets form field values from the lookup results.
*
*   @param  string  isbn    Item ISBN number
*/
var LIBR_astoreLookup = function(isbn) {
    LIBR_showIcon("working");
    var key = "ISBN:" + isbn;
    var dataS = {
        "isbn" : isbn,
        "action" : "lookup",
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: site_admin_url + "/plugins/library/ajax.php",
        data: data,
        success: function(result, textStatus, jqXHR) {
            try {
                var res = result;
                if (res.error == '') {
                    LIBR_updateField(res.by_statement, "author");
                    LIBR_updateField(res.publisher, "publisher");
                    LIBR_updateField(res.title, "f_title");
                    LIBR_updateField(res.dscp, "f_dscp");
                    if (window.CKEDITOR) {
                        CKEDITOR.instances["f_dscp"].setData(res.dscp);
                    }
                    LIBR_updateField(res.publish_date, "f_pub_date");
                    $.UIkit.notify("Data Updated", {timeout: 1000,pos:'top-center'});
                } else {
                    $.UIkit.notify(res.error, {timeout: 1000,pos:'top-center'});
                }
            }
            catch(err) {
                console.log(url);
                console.log(data);
                $.UIkit.notify("Astore lookup error", {timeout: 1000,pos:'top-center'});
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
    });
    LIBR_showIcon("search");
    return false;
};


/**
*   Look up an ISBN from openlibrary.org
*   Sets form field values from the lookup results.
*
*   @param  string  isbn    Item ISBN number
*/
var LIBR_openlibLookup = function(isbn) {
    LIBR_showIcon("working");
    var indicator = isbn.toLowerCase()
    indicator = indicator.substring(0,2);
    if (indicator == "ol") {
        var key = "OLID:" + isbn;
    } else {
        var key = "ISBN:" + isbn;
    }
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
                console.log(res.title);
                LIBR_updateField(res.by_statement, "author");
                LIBR_updateField(res.publishers[0].name, "publisher");
                LIBR_updateField(res.title, "f_title");
                LIBR_updateField(res.subtitle, "f_subtitle");
                LIBR_updateField(res.publish_date, "f_pub_date");
                LIBR_updateField(res.excerpt, "f_dscp");
            }
            catch(err) {
                console.log(data);
                $.UIkit.notify("OpenLibrary lookup error", {timeout: 1000,pos:'top-center'});
            }
            LIBR_showIcon("search");
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            LIBR_showIcon("search");
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
    console.log("Setting " + elem_id + " to " + value);
    if (typeof(value) != "undefined") {
        document.getElementById(elem_id).value = value;
    }
}

/**
*   Update a form field from the library lookup results
*
*   @param  string  value   Value to set in field
*   @param  string  elem_id Form element ID
*/
function LIBR_updateText(value, elem_id)
{
    if (typeof(value) != "undefined")
        document.getElementById(elem_id).innerHTML = value;
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

function LIBR_showIcon(icon_name)
{
    if (icon_name == "working") {
        document.getElementById("workingicon").style.display= "";
        document.getElementById("searchicon").style.display = "none";
    } else {
        document.getElementById("workingicon").style.display = "none";
        document.getElementById("searchicon").style.display = "";
    }
}
