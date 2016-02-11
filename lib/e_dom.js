/*
 * translator ready
 * addnews ready
 * mail ready
 */

//Javascript Generic DOM
//By Eric Stevens
//
function fetchDOM(filename){
	var xmldom;
	if (document.implementation &&
			document.implementation.createDocument){
		//Mozilla style browsers
		xmldom = document.implementation.createDocument("", "", null);
	} else if (window.ActiveXObject) {
		//IE style browsers
		xmldom = new ActiveXObject("Microsoft.XMLDOM");
	}

	xmldom.async=false;
	try {
		xmldom.load(filename);
	} catch(e){
		xmldom.parseXML("<b>Failed to load "+filename+"</b>");
	}
	return xmldom;
}
if (document.implementation && document.implementation.createDocument){
	var dom = document.implementation.createDocument("","",null);
}else{
	var dom = new ActiveXObject("Microsoft.XMLDOM");
}
function fetchDOMasync(filename,args,theCode){
	var xmldom;
	try {
		xmldom = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmldom = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmldom = false;
		}
	}
	if (!xmldom && typeof XMLHttpRequest!='undefined') {
		xmldom = new XMLHttpRequest();
	}
	xmldom.onreadystatechange = function(){
		if (xmldom.readyState == 4) {
			theCode();
		}
	};
	xmldom.open("POST",filename,true);
	xmldom.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
	xmldom.send(args);
	return xmldom;
}
function createXML(node){
	if (!node) return "<b>You cannot pass null to createXML</b>";
	if (node.xml)
		return node.xml;
	var out = "";
	if (node.nodeType==1){
		var x=0;
		out = "<" + node.nodeName;
		for (x=0; x < node.attributes.length; x++){
			out = out + " " + node.attributes[x].name + "=\"" + HTMLencode(node.attributes[x].nodeValue) + "\"";
		}
		out = out + ">";
		for (x=0; x < node.childNodes.length; x++){
			out = out + createXML(node.childNodes[x]);
		}
		out = out + "</" + node.nodeName + ">";
	}else if(node.nodeType==3){
		out = out + HTMLencode(node.nodeValue);
	}
	return out;
}
function selectSingleNode(node,name){
	var nextName = "";
	if (name.indexOf("/") > 0){
		nextName = name.substring(name.indexOf("/")+1);
		name = name.substring(0,name.indexOf("/"));
	}
	for (var x=0; x<node.childNodes.length; x++){
		if (node.childNodes[x].nodeName == name) {
			if (nextName == ""){
				return node.childNodes[x];
			}else{
				return selectSingleNode(node.childNodes[x],nextName);
			}
		}
	}
}
function nodeText(node){
	var out="";
	for (y=0; y<node.childNodes.length; y++){
		if (node.childNodes[y].nodeType==3){
			out+=node.childNodes[y].nodeValue;
		}else if(node.childNodes[y].nodeType==1){
			out += nodeText(node.childNodes[y]);
		}
	}
	return out;
}
function parseRSS(xml,htmlescape){
	var rss = selectSingleNode(xml,"rss");
	var channel = selectSingleNode(rss,"channel");

	var feed = new Array();
	//collect rss headers
	feed["title"] = HTMLencode(nodeText(selectSingleNode(channel,"title")),htmlescape);
	feed["link"] = HTMLencode(nodeText(selectSingleNode(channel,"link")),htmlescape);
	feed["description"] = HTMLencode(nodeText(selectSingleNode(channel,"description")),htmlescape);
	var image = selectSingleNode(channel,"image");
	feed["image"] = new Array();
	feed["image"]["title"] = HTMLencode(nodeText(selectSingleNode(image,"title")),htmlescape);
	feed["image"]["url"] = HTMLencode(nodeText(selectSingleNode(image,"url")),htmlescape);
	feed["image"]["link"] = HTMLencode(nodeText(selectSingleNode(image,"link")),htmlescape);
	feed["items"] = new Array();
	//collect rss items
	var node;
	var y=0;
	for (var x=0; x<channel.childNodes.length; x++){
		node = channel.childNodes[x];
		if (node.nodeType==1){ //standard element
			if (node.nodeName == "item"){
				feed['items'][y] = new Array();
				feed['items'][y]['title'] = HTMLencode(nodeText(selectSingleNode(node,"title")),htmlescape);
				feed['items'][y]['link'] = HTMLencode(nodeText(selectSingleNode(node,"link")),htmlescape);
				feed['items'][y]['description'] = HTMLencode(nodeText(selectSingleNode(node,"description")),htmlescape);
				feed['items'][y]['pubdate'] = HTMLencode(nodeText(selectSingleNode(node,"pubDate")),htmlescape);
				y=y+1;
			}
		}
	}
	return feed;
}
function HTMLencode(input){
	if (input == null){
		return "";
	}else{
		return input.replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
	}
}
function HTMLdecode(input){
	if (input == null){
		return "";
	}else{
		return input.replace(/&gt;/g,">").replace(/&lt;/g,"<").replace(/&quot;/g,'"').replace(/&amp;/g,"&");
	}
}
