/*
Facelift Image Replacement v2.0 beta 2
Facelift was written and is maintained by Cory Mawhorter.  
It is available from http://facelift.mawhorter.net/

===

This file is part of Facelife Image Replacement ("FLIR").

FLIR is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

FLIR is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Facelift Image Replacement.  If not, see <http://www.gnu.org/licenses/>.
*/

var FLIR = {
	version: '2.0b2'
	
	,options: {
		path: './'

		,defaultStyle:			null
		,ignoredEls: 			'BR,HR,IMG,INPUT,SELECT'

		// if you are replacing the background image, the element must be display:block otherwise it won't work
		// setting this option to true will check to see if an element is block, and if not, make it so
		// this is off by default because it slows down replacement
		,bkgCheckForBlock:	false

		,onreplacing: 			null
		,onreplaced: 			null
		,onreplacingchild: 	null
		,onreplacedchild: 	null
	}

	,findEmbededFonts: 	false
	,dpi: 96
	
	,flirElements: {}
	,flirPlugins: 	[]
	
	,isIE6: 			true
	,isIE: 			true
	
	,hoverEnabled: false
	
	// when designing a site with facelift, IE will sometimes cache your mistakes which will cause weird 
	// stretched images.  turn this on to keep that from happening and back off when 
	// you get everything how you want it
	,debug: 			false
	
	,init: function(options) {
		if(typeof options != 'undefined')
			for(var i in options)
				this.options[i] = options[i];

		if(this.options.defaultStyle == null)
			this.options.defaultStyle = new FLIRStyle();

		this.detectBrowser();
		this.calcDPI();

		if((this.findEmbededFonts = (typeof FLIR.discoverEmbededFonts == 'function')))
			this.discoverEmbededFonts();
			
		this.hoverEnabled = (typeof this.addHover == 'function');
		
		FLIR.pcall('init', arguments);
	}
    
	,install: function(plugin) {
		this.flirPlugins.push(plugin);
	}
	
	,pcall: function(func, call) {
		var ret = call;
		for(var i=0; i<this.flirPlugins.length; i++) {
			if(typeof this.flirPlugins[i][func] == 'function') {
				var pluginret = this.flirPlugins[i][func](ret);

				if(typeof pluginret == 'undefined') {
					continue;
				}
				if(typeof pluginret == 'boolean' && pluginret == false) {
					return false;
				}
				if(typeof pluginret != 'boolean') // passes changes on
					ret = call;

			}
		}
		
		var ret = typeof ret != 'object' ? [ret] : ret;
		if(ret.length && ret[0] && ret[0].callee)
			return ret[0];
		else
			return ret;
	}
	
	,prepare: function(n, bTrim) {
		if(!(args = FLIR.pcall('prepare', arguments))) return;
		n = args[0];

		if(n && n.hasChildNodes() && n.childNodes.length > 1) {
			for(var i =0; i < n.childNodes.length; i++) {
				var node = n.childNodes[i];
				if(node && node.nodeType == 3) {
					if(bTrim) {
						trimreg = i==0 ? /^\s+/g : /\s+$/g;
						node.innerHTML = node.innerHTML.replace(trimreg, '');
					}
					
					var span = document.createElement('SPAN');
					span.style.margin = span.style.padding = span.style.border = '0';
					span.className = 'flir-span';
					span.flirSpan = true;
					if(node.nodeValue.match(/^[\n\r]+$/)) continue; // skip new lines
					var txt = node.nodeValue.replace(/[\t\n\r]/g, ' ').replace(/\s\s+/g, ' ');
					span.innerHTML = !FLIR.isIE ? txt : node.nodeValue.replace(/^\s+|\s+$/g,'&nbsp;');
					n.replaceChild(span, node);
				}
			}
		}
	}
   
	,replace: function(o, FStyle) { // o can be a selector, element, or array of elements
		if(!(args = FLIR.pcall('replace', arguments))) return;
		o 		= args[0];
		FStyle 	= args[1];

		if(!o || o.flirReplaced) return; // bad element or already replaced
		if(!this.isFStyle(FStyle) && typeof FStyle == 'object') // object lit passed, setup FLIRStyle
			FStyle = new FLIRStyle(FStyle);
		else if(!this.isFStyle(FStyle)) // something else/nothing passed, use default
			FStyle = this.options.defaultStyle; // no FStyle specified, use default

		if(typeof o == 'string') o = this.getElements(o);

		if(typeof o.length != 'undefined') {
			if(o.length == 0) return; // not found
			
			for(var i=0; i< o.length; i++)
				this.replace(o[i], FStyle);
			
			return; // finished replacing list of elements, exit
		}
		
		o.flirStyle = FStyle;
				
		if(typeof FLIR.options.onreplacing == 'function') o = FLIR.options.onreplacing(o, FStyle);
		
		o.flirMainObj = true;
		this.saveObject(o);
		
		if(this.findEmbededFonts && typeof this.embededFonts[FStyle.getFont(o, FLIR.getStyle(o, 'font-family'))] != 'undefined') return; // font embedded, skip
		
		FLIR.prepare(o);        
		this._replace_tree(o, FStyle);
		
		if(typeof FLIR.options.onreplaced == 'function') FLIR.options.onreplaced(o, FStyle);
	}
    
	,_replace_tree: function(o, FStyle) {
		var objs = !o.hasChildNodes() || (o.hasChildNodes() && o.childNodes.length==1 && o.childNodes[0].nodeType==3) ? [o] : o.childNodes;
	
		var rep_obj;
		for(var i=0; i < objs.length; i++) {
			rep_obj = objs[i];        
			if(typeof FLIR.options.onreplacingchild == 'function') rep_obj = FLIR.options.onreplacingchild(rep_obj, FStyle);
			
			if(!rep_obj.innerHTML || rep_obj.nodeType != 1) continue;
			if(FLIR.isIgnoredEl(rep_obj)) continue;
			if(rep_obj.flirReplaced) continue;
			
			if(FLIR.hoverEnabled && rep_obj.nodeName == 'A' && !rep_obj.flirHasHover)
				FLIR.addHover(rep_obj);
			
			if(rep_obj.hasChildNodes() && (rep_obj.childNodes.length > 1 || rep_obj.childNodes[0].nodeType != 3)) {
				FLIR.prepare(rep_obj);
				FLIR._replace_tree(rep_obj, FStyle);
				continue;
			}
			
			if(rep_obj.innerHTML == '') continue; // skip empty tags, if they exist
			var op = FStyle.options.output;
			if(FLIR.isIE6 && (rep_obj.flirIE6PNG = (op == 'png' || (op =='auto' && FLIR.getStyle(rep_obj, 'background-color')=='transparent')))){ // force this method when a transparent png is needed
				FLIR._Rimg(rep_obj, FStyle, true);
			}else{
				if(FStyle.replaceBackground)
					FLIR._Rbkg(rep_obj, FStyle);
				else
					FLIR._Rimg(rep_obj, FStyle);
			}
			
			rep_obj.className += ' flir-replaced';
			rep_obj.flirReplaced = true;
			
			if(typeof FLIR.options.onreplacedchild == 'function') FLIR.options.onreplacedchild(o, FStyle);
		}
	}
    
	,_Rbkg: function(o, FStyle) { // replace text with background image
		if(!(args = FLIR.pcall('replaceBackground', arguments))) return;
		o 		= args[0];
		FStyle 	= args[1];

		var oid = this.saveObject(o);
		var url = FStyle.URL(o);
		
		if(FLIR.options.bkgCheckForBlock)
			if(FLIR.getStyle(o, 'display') != 'block')
				o.style.display='block';
		
		var tmp = new Image();
		tmp.onload = function() {
			FLIR.flirElements[oid].style.width=this.width+'px';
			FLIR.flirElements[oid].style.height=this.height+'px';
		
			if(FLIR.hoverEnabled && FStyle != FStyle.hoverStyle) {
				var h_img = new Image();
				o.flirHoverURL = h_img.src = FStyle.hoverStyle.URL(o);
			}
		};
		tmp.src = url;
		
		o.style.background = 'url("'+url.replace(/ /g, '%20')+'") no-repeat';
		o.flirOrig = url;
		
		o.oldTextIndent = o.style.textIndent;
		o.style.textIndent='-9999px';
	}

	,_Rimg: function(o, FStyle, bIE6Alpha) { // replace text with an image tag
		if(!(args = FLIR.pcall('replaceMethodOverlay', arguments))) return;
		o 		= args[0];
		FStyle 	= args[1];

		var oid = this.saveObject(o);
		var img = document.createElement('IMG');
		var url = FStyle.URL(o);
		img.alt = o.innerHTML;
		
		if(FLIR.hoverEnabled && FStyle != FStyle.hoverStyle) {
			img.onload = function() { // delay loading of the hover style
				var h_img = new Image();
				o.flirHoverURL = h_img.src = FStyle.hoverStyle.URL(o, img.alt);
			};
		}
		
		if(img.onerror) { // revert to text
			img.onerror = function() {
				var span = document.createElement('SPAN');
				span.innerHTML = img.alt;
				try {
					o.replaceChild(span,img)
				}catch(err) { }
			};
		}
		
		img.flirImage = true;
		img.className = 'flir-image';
		img.style.border='none';
		
		if(bIE6Alpha) {
			img.src = this.options.path+'spacer.png';
			if(o.offsetWidth) {
				img.style.width=o.offsetWidth+'px';
				img.style.height=o.offsetHeight+'px';
			}
			img.style.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="'+url+'", sizingMethod="image")';
			
			o.flirOrig = url;	
		}else {
			img.src = url;
			o.flirOrig = img.src;
		}
		
		o.innerHTML='';
		o.appendChild(img);
	}

	,saveObject: function(o) {
        if(typeof o.flirId == 'undefined') {
            o.flirId = this.generateUID();
				this.flirElements[o.flirId] = o;
        }
        
        return o.flirId;
	}
    
	,generateUID: function() {
		var prefix='flir-';
		if(typeof __flir_uid_count == 'undefined') __flir_uid_count = 0;
		else __flir_uid_count++;
		return prefix+__flir_uid_count;
	}
    
	,calcDPI: function() {
		if(screen.logicalXDPI) {
			var dpi = parseInt(screen.logicalXDPI);
		}else {
			var dpicook = document.cookie.match(/<dpi>(\d+)<\/dpi>/);
			if(dpicook) {
				this.dpi = dpicook[1];
				//console.info('DPI loaded from cookie');
				return;
			}
			
			var test = document.createElement('DIV');
			test.style.position='absolute';
			test.style.visibility='hidden';
			test.style.border=test.style.padding=test.style.margin='0';
			test.style.height=test.style.width='1in';
			document.body.appendChild(test);
			
			var dpi = parseInt(test.offsetHeight);
			document.body.removeChild(test);

			var future = new Date();
			future.setDate(new Date().getDate()+365);
			document.cookie = 'dpi=<dpi>'+this.dpi+'</dpi>;expires='+future.toGMTString()+';path=/';
		}
		
		if(dpi > 0)
			this.dpi = dpi;
	}
    
	,isIgnoredEl: function(el) { return ((','+this.options.ignoredEls+',').indexOf(','+el.nodeName+',')>-1); }
	,sanitizeHTML: function(html) { return html.replace(/<[^>]+>/g, ''); }
	
	,isFStyle: function(o) { if(!o) return false; return (typeof o.cssMap != 'undefined'); }
};


__flirstyle_instances=0;
function FLIRStyle(options, hoverStyle) {
	__flirstyle_instances++;
	this.uid						= __flirstyle_instances;
	this.replaceBackground	= false;
	this.hoverStyle			= hoverStyle && FLIR.isFStyle(hoverStyle) ? hoverStyle : this;
	
	// options are sent along with the query string
	this.options = {};
	//these are the default settings for FLIR. If you change a default here, be sure 
	//to also change it in generate.php, otherwise you may run into problems
	this.defaults = {
		 mode: 			'static' // static, wrap, progressive or name of a plugin
		,output:			'auto' // png, gif, auto
		,fixBaseline: 	false
		,hq:				false // use high quality rendering
		,css:				{}
	};
	
	// css vals that get passed and their corresponding value parser
	this.cssMap = {
		 'background-color'	: 'Background'
		,'color'					: 'Color'
		,'font-family'			: 'Font'
		,'font-size'			: 'FontSize'
		,'letter-spacing'		: 'Measurement'
		,'line-height'       : 'LineHeight'
		,'text-align'			: 'Default'
		,'font-stretch'		: 'Default'
		,'font-style'			: 'FontStyle'
		,'font-variant'		: 'Default'
		,'font-weight'       : 'Weight'
		,'opacity'           : 'Default'
		,'text-decoration'   : 'Default'
	};
	
	for(var i in this.defaults) // set defaults
		this.options[i] = this.defaults[i];

	if(options && typeof options.css == 'string')
		options.css = this.parse_css_string(options.css);
		
	this.loadopts(options);
}

FLIRStyle.prototype.loadopts = function(options) {
	for(var i in this.cssMap)
		this.options['css'][i] = options && options.css && typeof options.css[i] != 'undefined' ? options.css[i] : null;

	if(typeof this.loadopts_compat == 'function')
		options = this.loadopts_compat(options);

	if(typeof options != 'undefined') {
		for(var i in options) {
			if(options[i] == null) continue;
			if(typeof this[i] != 'undefined') {
				this[i] = options[i];
			}else {
				if(i=='css')
					for(var csi in options[i])
						this.options[i][csi] = options[i][csi];
				else
					this.options[i] = options[i];
			}
		}
	}
};

FLIRStyle.prototype.parse_css_string = function(css) {
	var props = css.split(';');
	var cssobj = {};
	var vals;
	for(var i=0; i < props.length; i++) {
		if(props[i].indexOf(':') < 0) continue;
		vals = props[i].split(':');
		cssobj[vals[0].replace(/^\s+|\s+$/, '')] = vals[1].replace(/^\s+|\s+$/, '');
	}
	
	return cssobj;
};

// generate a url based on an object
FLIRStyle.prototype.URL = function(o) { // [, text]
	var enc_text = (arguments[1]?arguments[1]:o.innerHTML);
	var transform = this.options.css['text-transform'];
	if(transform==null)
		transform = FLIR.getStyle(o, 'text-transform');
	
	switch(transform) {
		case 'capitalize':
			enc_text = enc_text.replace(/\w+/g, function(w){
				return w.charAt(0).toUpperCase()+w.substr(1).toLowerCase();
			});
			break;
		case 'lowercase':
			enc_text = enc_text.toLowerCase();
			break;
		case 'uppercase':
			enc_text = enc_text.toUpperCase().replace(/&[a-z0-9]+;/gi, function(m) { return m.toLowerCase(); }); // keep entities lowercase, numeric don't matter
			break;
	}

	enc_text = this.encodeText(enc_text, o.flirIE6PNG);
	
	var url = FLIR.options.path+'generate.php?t='+enc_text+'&h='+o.offsetHeight+'&w='+o.offsetWidth+'&c='+this.flattenCSS(o)+'&d='+FLIR.dpi+'&f='+this.serialize();
	
	if(FLIR.debug)
		url += '&rand='+(Math.random()*Math.random());
	
//	console.info(url);
	return url;
};

FLIRStyle.prototype.encodeText = function(str, bIE6Png) { 
	str = encodeURIComponent(str.replace(/&/g, '{*A}').replace(/\+/g, '{*P}').replace(/\(/g, '{*LP}').replace(/\)/g, '{*RP}')); 
	if(bIE6Png)
		str = escape(str);
	return str;
};

FLIRStyle.prototype.serialize = function() {
	var sdata = '';
	for(var i in this.options) {
		if(i=='css' || this.options[i] == this.defaults[i]) continue;
		sdata += ',"'+i+'":"'+this.options[i].toString().replace(/"/g, "'")+'"';
	}
	
	return encodeURIComponent('{'+sdata.substr(1)+'}');
};

FLIRStyle.prototype.flattenCSS = function(o) {
	var options = this.copyObject(this.options.css);
	
	for(var i in this.cssMap) {
		this.options.css[i] = this.get(o, i, this.cssMap[i]);
	}
	
	var sdata='';
	for(var i in this.options.css) {
		if(this.options.css[i] == null || typeof this.options.css[i] == 'undefined')
			this.options.css[i] = '';
		sdata += '|'+encodeURIComponent(this.options.css[i].toString().replace(/|/g, ''));
	}
	
	sdata = sdata.substr(1);
	this.options.css = options;
	
	return sdata;
};

FLIRStyle.prototype.get = function(o, css_property, flirstyle_name) {
	var func = 'get'+flirstyle_name;
	
	while(o.flirSpan && o != document.body)
		o = FLIR.getParentNode(o);
	
	var optprop = this.options.css[css_property];
	var val = !optprop || optprop == null ? FLIR.getStyle(o, css_property) : this.options.css[css_property];
	var ret = typeof this[func] == 'function' ? this[func](o, val) : val;
	return ret == 'normal' || ret == 'none' || ret == 'start' ? '' : ret;
};

FLIRStyle.prototype.getFontStyle = function(o, val) { 
	return (o.nodeName=='EM' || FLIR.getParentNode(o).nodeName=='EM' ? 'italic' : val) == 'italic' ? '1' : '';
};

FLIRStyle.prototype.getBackground = function(o, val) { 
	if(this.options.output=='gif' && val.search(/^(transparent|none)$/i) > -1) {
		var p = FLIR.getParentNode(o);
		var pstyle = FLIR.getStyle(p, 'background-color');
		
		if(typeof __flirstyle_root_obj == 'undefined')
			__flirstyle_root_obj = FLIR.getParentNode(document.body);

		while(pstyle.search(/^(transparent|none)$/i) > -1 && p != __flirstyle_root_obj) {
			p = FLIR.getParentNode(p);
			pstyle = FLIR.getStyle(p, 'background-color');
		}
		return this.getColor(o, pstyle);
	}else {
		return this.getColor(o, val);
	}
};

FLIRStyle.prototype.getWeight = function(o, val) { 
	var fontweight = o.nodeName=='STRONG' || FLIR.getParentNode(o).nodeName=='STRONG' ? 'bold' : val;
	
	switch(fontweight.toString()) {
		case '100': case '200': case '300': case 'lighter':	return '-1';
		case '400': case 'normal':										return '';
		case '500': case '600': case '700': case 'bold':		return '1';
		case '800': case '900': case 'bolder':						return '2';
	}
};

FLIRStyle.prototype.getLineHeight = function(o, val) { 
	var lh = this.getMeasurement(o, val)/o.flirFontSize;
	return Math.round((lh*100000))/100000;
};

FLIRStyle.prototype.getFont = function(o, val) { 
    if(val.indexOf(','))
        val = val.split(',')[0];

    return val.replace(/['"]/g, '').toLowerCase();
};

FLIRStyle.prototype.getColor = function(o, val) {
	switch(val) {
		case 'transparent': case 'none': return '';
		default:
			if(val.substr(0, 1)=='#')
				val = val.substr(1);
	
			return val.replace(/['"\(\) ]|rgba?/g, '').toLowerCase();
	}
};

FLIRStyle.prototype.getFontSize = function(o, val) {
	var px = this.getMeasurement(o, val, true);
	var prepx = px;
	// fix this... need to make it detect which property is being retrieved and change the final val based on the setting in.css
	if('*/+-'.indexOf(val[0])>-1) {
		try {
			px = Math.round(   (parseFloat(eval(px.toString().concat(val))))  *10000)/10000;
		}catch(err) { px = 16; }
	}
	
	o.flirFontSize = px;
	return px;
};

FLIRStyle.prototype.getMeasurement = function(o, val, bFontSize) {
	var px,em,prcnt;
	if(val == 'normal' || val == 'none') return '';

	if(val.indexOf('px') > -1) {
		px = Math.round(parseFloat(val));
	}else if(val.indexOf('pt') > -1) {
		var pts = parseFloat(val);
		px = pts/(72/FLIR.dpi);
	}else if((em = (val.indexOf('em') > -1)) || (prcnt = (val.indexOf('%') > -1))) {
		if(!o.flirFontSize) {
			var test = document.createElement('DIV');
			test.style.padding = test.style.border = '0';
			test.style.position='absolute';
			test.style.visibility='hidden';
			if(bFontSize)
				test.style.lineHeight = '100%';
			test.innerHTML = 'FlirTest';        
			o.appendChild(test);
			
			px = test.offsetHeight;
			o.removeChild(test);
		}else {
			px = o.flirFontSize;
		}
	}
	
	return px;
};

FLIRStyle.prototype.copyObject = function(obj) { 
    var copy = {};
    for(var i in obj) {
        copy[i] = obj[i];    
    }
    
    return copy;
};

FLIRStyle.prototype.toString = function() { return this.uid; };

// enable hover support
FLIR.addHover = function(obj) {
	if(!(args = FLIR.pcall('addHover', arguments))) return;
	obj	= args[0];

	obj.flirHasHover = true;
	
	if(obj.addEventListener) {
		obj.addEventListener( 'mouseover', FLIR.hover, false );
		obj.addEventListener( 'mouseout', FLIR.hover, false );
	}else if (obj.attachEvent) {
		obj.attachEvent( 'onmouseover', function() { FLIR.hover( window.event ); } );
		obj.attachEvent( 'onmouseout', function() { FLIR.hover( window.event ); } );
	}
};

// auto CSS hover detection.  IE takes a helluva lot of extra work to get the current style of an element being hovered
FLIR.flirIERepObj 	= [];
FLIR.flirIEHovEls 	= [];
FLIR.flirIEHovStyles = [];	
FLIR.hover = function(e) {
	//console.log('hover');
	var o=FLIR.evsrc(e);
	var targ=o;
	var targDescHover = o.flirHasHover;
	var hoverTree = o;
	
	var on = (e.type == 'mouseover');
	
	while(o != document.body && !o.flirMainObj) {
		o = FLIR.getParentNode(o);
		
		if(!targDescHover) {
				targDescHover = o.flirHasHover;
				hoverTree = o;
		}
	}
	
	if(o==document.body) return;
	
	var FStyle = o.flirStyle;
	if(on && FStyle != FStyle.hoverStyle)
		FStyle = FStyle.hoverStyle;
	
	if(!(args = FLIR.pcall('hover', [ on, targ, o, hoverTree ]))) return;
	on				= args[0];
	targ 			= args[1];
	o 				= args[2];
	hoverTree 	= args[3];

	var objs = FLIR.getChildren(hoverTree);
	if(objs.length == 0 || (objs.length == 1 && (objs[0].flirImage || objs[0].flirHasHover))) {
		objs = [hoverTree];
	}else if(objs.length == 1 && !FLIR.isIgnoredEl(objs[0])) {
		var subobjs = FLIR.getChildren(objs[0]);
		if(subobjs.length > 0)
			if((subobjs.length==1 && !subobjs[0].flirImage) || subobjs.length > 1)
				objs = subobjs;
	}

	var rep_obj;
	for(var i=0; i < objs.length; i++) {
		rep_obj = objs[i];
		if(rep_obj.nodeName == 'IMG') continue;
		if(!rep_obj.innerHTML) continue; // IE 

		if(FLIR.isIE) {
			var idx = FLIR.flirIEHovEls.length;
			FLIR.flirIERepObj[idx] = rep_obj;
			FLIR.flirIEHovStyles[idx] = FStyle;
			
			var op = FStyle.options.output;
			if(FLIR.isIE6 && (rep_obj.flirIE6PNG = (op == 'png' || (op =='auto' && FLIR.getStyle(rep_obj, 'background-color')=='transparent')))){ // transparent png
				FLIR.flirIEHovEls[idx] = rep_obj.flirImage ? rep_obj : FLIR.getChildren(rep_obj)[0];
				setTimeout('FLIR.flirIEHovEls['+idx+'].style.filter = \'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="\'+FLIR.flirIEHovStyles['+idx+'].URL(FLIR.flirIERepObj['+idx+'], FLIR.flirIEHovEls['+idx+'].alt)+\'", sizingMethod="image")\';  ', 0);
			}else {
				if(FStyle.replaceBackground && FLIR.getStyle(rep_obj, 'display') == 'block') {
					FLIR.flirIEHovEls[idx] = rep_obj;
					setTimeout('FLIR.flirIERepObj['+idx+'].style.background = "url("+('+on+' ? FLIR.flirIEHovStyles['+idx+'].URL(FLIR.flirIERepObj['+idx+']) : FLIR.flirIERepObj['+idx+'].flirOrig)+") no-repeat";', 0);
				}else {
					FLIR.flirIEHovEls[idx] = rep_obj.flirImage ? rep_obj : FLIR.getChildren(rep_obj)[0];
					if(!FLIR.flirIEHovEls[idx].flirOrigWidth) {
						FLIR.flirIEHovEls[idx].flirOrigWidth = FLIR.flirIEHovEls[idx].width;
						FLIR.flirIEHovEls[idx].flirOrigHeight = FLIR.flirIEHovEls[idx].height;
					}
					var ie_js = 'FLIR.flirIEHovEls['+idx+'].src = '+on+' ? FLIR.flirIEHovStyles['+idx+'].URL(FLIR.flirIERepObj['+idx+'], FLIR.flirIEHovEls['+idx+'].alt) : FLIR.flirIERepObj['+idx+'].flirOrig;'
					ie_js += 'FLIR.flirIEHovEls['+idx+'].onload = function() { ';
					if(on && !FLIR.flirIEHovEls[idx].flirHoverWidth) {
						ie_js += '		FLIR.flirIEHovEls['+idx+'].flirHoverWidth = this.width; ';
						ie_js += '		FLIR.flirIEHovEls['+idx+'].flirHoverHeight = this.height; ';
					}
					ie_js += '	this.style.width = FLIR.flirIEHovEls['+idx+'].'+(on?'flirHoverWidth':'flirOrigWidth')+'+"px"; ';
					ie_js += '	this.style.height = FLIR.flirIEHovEls['+idx+'].'+(on?'flirHoverHeight':'flirOrigHeight')+'+"px"; ';
					ie_js += '}; ';
					setTimeout(ie_js, 0);
				}
			}
		}else {
			if(FStyle.replaceBackground) {
				var hovURL = rep_obj.flirHoverURL ? rep_obj.flirHoverURL : FStyle.URL(rep_obj);
				rep_obj.style.background='url('+(on?hovURL:rep_obj.flirOrig)+') no-repeat';
			}else {
				var img = rep_obj.flirImage ? rep_obj : FLIR.getChildren(rep_obj)[0];
				var hovURL = rep_obj.flirHoverURL ? rep_obj.flirHoverURL : FStyle.URL(rep_obj, img.alt);
				img.src = on?hovURL:rep_obj.flirOrig;
			}
		}
	}
};

/**
	FLIR replacable functions
*/

// FLIR defaults
FLIR.detectBrowser = function() {
	FLIR.isIE  = (navigator.userAgent.toLowerCase().indexOf('msie')>-1 && !window.opera);
	FLIR.isIE6 = (typeof document.body.style.maxHeight=='undefined');
};

FLIR.getElements = function(tag) {
  var found = [];

  if(document.querySelectorAll) {
		var qsa = false;
		try{
			 found = document.querySelectorAll(tag);
			 qsa = true;
		}catch(err){ qsa=false; }

		if(qsa)
			 return found;
  }

  var objs,subels,cn,childs,tag,el,matches,subel,rep_el;

  el = tag;
  
  subel=false;
	if(el.indexOf(' ')>-1) {
		var parts = el.split(' ');
		el = parts[0];
		subel = parts[1];
	}else if(el.substr(0,1) == '#') {
		return document.getElementById(el.substr(1));
	}
  
  var grain_id=false;
  if(el.indexOf('#') > -1) {
		grain_id = el.split('#')[1];
		tag = el.split('#')[0];
  }

  var grain_cn=false;
  if(el.indexOf('.') > -1) {
		grain_cn = el.split('.')[1];
		tag = el.split('.')[0];
  }

  objs = document.getElementsByTagName(tag);
  for(var p=0; p<objs.length; p++) {
		if(objs[p].nodeType != 1) continue;
		matches = false;
		cn = objs[p].className?objs[p].className:'';
		
		if(grain_id && objs[p].id && objs[p].id == grain_id)
			 matches=true;
		if(grain_cn && FLIR.hasClass(objs[p], grain_cn))
			 matches=true;
		if(!grain_id && !grain_cn)
			 matches=true;
		
		if(!matches) continue;
		
		subels = false != subel ? objs[p].getElementsByTagName(subel) : [objs[p]];
		for(var pp=0; pp<subels.length; pp++) {
			 rep_el = subels[pp];
			 found.push(rep_el);
		}
  }
  
  return found;
}

FLIR.getStyle = function(el,prop) {
  if(el.currentStyle) {
		if(prop.indexOf('-') > -1)
			 prop = prop.split('-')[0]+prop.split('-')[1].substr(0, 1).toUpperCase()+prop.split('-')[1].substr(1);
		var y = el.currentStyle[prop];
  }else if(window.getComputedStyle) {
		var y = document.defaultView.getComputedStyle(el,'').getPropertyValue(prop);
  }
  return y;
};
  
FLIR.getChildren = function(n) {
  var children=[];
  if(n && n.hasChildNodes())
		for(var i in n.childNodes)
			 if(n.childNodes[i] && n.childNodes[i].nodeType == 1)
				  children[children.length]=n.childNodes[i];

  return children;
};

FLIR.getParentNode = function(n) {
  var o=n.parentNode;
  while(o != document && o.nodeType != 1)
		o=o.parentNode;

  return o;
};

FLIR.hasClass = function(o, cn) {
  return (o && o.className && o.className.indexOf(cn)>-1);
};

FLIR.evsrc = function(e) {
  var o;
  if (e.target) o = e.target;
  else if (e.srcElement) o = e.srcElement;
  if (o.nodeType == 3) // defeat Safari bug
		o = o.parentNode;    
		
  return o;
};

FLIRStyle.prototype.loadopts_compat = function(options) {
	if(!options) return;
	if(!options.css) options.css = {};
	options.fixedBaseline = options.realFontHeight ? true : false;
	
	var css_compat = {
		 cBackground	: 'background-color'
		,cColor			: 'color'
		,cFont			: 'font-family'
		,cSize			: 'font-size'
		,cSpacing		: 'letter-spacing'
		,cLine			: 'line-height'
		,cAlign			: 'text-align'
		,cTransform		: 'text-transform'
		,cStretch		: 'font-stretch'
		,cFontStyle		: 'font-style'
		,cVariant		: 'font-variant'
		,cWeight			: 'font-weight'
		,cOpacity		: 'opacity'
		,cDecoration	: 'text-decoration'
	};
	
	var modval;
	for(var i in css_compat) {
		if(typeof options[i] != 'undefined') {
			switch(i) {
				default:
					modval = options[i];
					break;
				case 'cSize':
					modval = options[i]+'px';
					break;
				case 'cColor':
				case 'cBackground':
					modval = '#'+options[i];
					break;
			}
			options.css[css_compat[i]] = modval;
			options[i] = null;
		}
	}
	
	return options;
};

FLIR.auto = function(els) { 
	FLIR.replace((!els ? ['h1','h2','h3','h4','h5']: (els.indexOf && els.indexOf(',')>-1?els.split(','):els) )); 
}