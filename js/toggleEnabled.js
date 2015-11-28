/*  $Id: toggleEnabled.js 8 2015-11-28 18:06:57Z root $
 *  Updates submission form fields based on changes in the category
 *  dropdown.
 */
var xmlHttp;
function LIBRARY_toggle(ckbox, id, type, component, base_url)
{
  xmlHttp=LIBRARYgetXmlHttpObject();
  if (xmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  // value is reversed since we send the oldvalue to ajax
  var oldval = ckbox.checked == true ? 0 : 1;
  var url=base_url + "/ajax.php?action=toggle";

  url=url+"&id="+id;
  url=url+"&type="+type;
  url=url+"&component="+component;
  url=url+"&oldval="+oldval;
  url=url+"&sid="+Math.random();
  xmlHttp.onreadystatechange=LIBRARYstateChanged;
  xmlHttp.open("GET",url,true);
  xmlHttp.send(null);
}

function LIBRARYstateChanged()
{
  var newstate;

  if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
  {
    xmlDoc=xmlHttp.responseXML;
    id = xmlDoc.getElementsByTagName("id")[0].childNodes[0].nodeValue;
    //imgurl = xmlDoc.getElementsByTagName("imgurl")[0].childNodes[0].nodeValue;
    baseurl = xmlDoc.getElementsByTagName("baseurl")[0].childNodes[0].nodeValue;
    type = xmlDoc.getElementsByTagName("type")[0].childNodes[0].nodeValue;
    component = xmlDoc.getElementsByTagName("component")[0].childNodes[0].nodeValue;
    if (xmlDoc.getElementsByTagName("newval")[0].childNodes[0].nodeValue == 1) {
        newval = 1;
        document.getElementById("tog"+type+id).checked = true;
    } else {
        newval = 0;
        document.getElementById("tog"+type+id).checked = false;
    }
    /*newHTML = 
        "<img src=\""+imgurl+"\" " +
        "width=\"16\" height=\"16\" " +
        "onclick='LIB_toggle("+newval+", \""+id+"\", \""+type+"\", \""+component+"\", \""+baseurl+"\");" +
        "'>";
    document.getElementById("tog"+type+id).innerHTML = newHTML;*/
  }

}

function LIBRARYgetXmlHttpObject()
{
  var objXMLHttp=null
  if (window.XMLHttpRequest)
  {
    objXMLHttp=new XMLHttpRequest()
  }
  else if (window.ActiveXObject)
  {
    objXMLHttp=new ActiveXObject("Microsoft.XMLHTTP")
  }
  return objXMLHttp
}

