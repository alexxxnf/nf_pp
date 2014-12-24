function nf_pp_init( id, autoCollapsed, autoOpen ){

	var
		classCtrl       = 'pp_expand',
		classOpened     = 'pp_expandOpen',
		classClosed     = 'pp_expandClosed',
		classCollapseCh = 'pp_ctrlCollapseCh',
		classKey        = 'pp_key',
		classValue      = 'pp_value',
		classNode       = 'pp_node',
		classRoot       = 'pp_isRoot',
		classFoundInText = 'pp_found_in_text',
		re              =  new RegExp( '(^|\\s)('+classOpened+'|'+classClosed+')(\\s|$)' );


	var
		wrap = document.getElementById( id ),
		searchInput = wrap.children[2].firstChild;

	if( autoCollapsed )
		autoCollapseTree( wrap );

	if( autoOpen.length )
		autoOpenTree( wrap, autoOpen );

	applyHdlr( wrap, tree_toggle );
	applyHdlr( searchInput, searchHdlr, 'input' );
	applyHdlr( searchInput, function(){ if (event.propertyName == 'value') searchHdlr.apply( this ); }, 'propertychange' );
	applyHdlr( searchInput, searchKeyHdlr, 'keyup' );


	function tree_toggle( event ){

		event = event || window.event;
		var clickedElem = event.target || event.srcElement;

		if( hasClass( clickedElem, classCtrl ) ){

			var node = clickedElem.parentNode;

			if( hasClass( node, classOpened ) )
				closeNode( node );
			else if( hasClass( node, classClosed ) )
				openNode( node );

		}
		else if( hasClass( clickedElem, 'pp_ctrlCollapseCh' ) ) {

			var node = clickedElem.parentNode.parentNode;
			collapseChildren(node);

		}
		else if( hasClass( clickedElem, 'pp_top' ) ) {

			onTop( wrap );

		}

	}


	function closeNode( node ){

		node.className = node.className.replace( re, '$1'+classClosed+'$3' );

	}


	function openNode( node ){

		node.className = node.className.replace( re, '$1'+classOpened+'$3' );

	}


	function openNodeUpWard( node ){

		openNode( node );          //  open current node

		if( ! hasClass( node, classRoot ) ){
			var parent = node.parentNode.parentNode;
			openNodeUpWard( parent );  //  open parent node
		}

	}


	function autoOpenTree( node, autoOpen ){

		var
			arSpan = node.getElementsByTagName( 'SPAN' ),
			arToOpen = [];

		for( var c = arSpan.length - 1; c >= 0; c-- ){

			var curSpan = arSpan[c];

			if( ! hasClass( curSpan, classKey ) )
				continue;

			for( var q = autoOpen.length - 1; q >= 0; q-- ){

				var rx = new RegExp( '\\['+autoOpen[q]+'(:<span[^<]*</span>)?\\]', 'i' );

				if( curSpan.innerHTML.search( rx ) != -1 ){
					arToOpen.push( curSpan.parentNode.parentNode );
					break;
				}

			}

		}

		for( var c = arToOpen.length - 1; c >= 0; c-- )
			openNodeUpWard( arToOpen[c] );

	}


	function autoCollapseTree( node ){

		var arLi = node.getElementsByTagName( 'LI' );

		for( var c = arLi.length - 1; c >= 0; c-- ){

			var curli = arLi[c];
			if( hasClass( curli, classNode ) )
				closeNode( curli );

		}

	}


	function collapseChildren( node ){

		var collapse = false;

		//  get children UL
		var ul = null;
		for( var c = node.children.length - 1; c >= 0; c-- ){

			if( node.children[c].nodeName == 'UL' ){
				ul = node.children[c];
				break;
			}

		}

		//  get children LIs
		var arLi = null;
		if( ul )
			arLi = ul.children;

		//  determine if there is opened nodes
		for( var c = arLi.length - 1; c >= 0; c-- ){

			if( hasClass( arLi[c], classOpened ) ){
				collapse = true;
				break;
			}

		}

		if( collapse )  //  collapse
			for( var c = arLi.length - 1; c >= 0; c-- )
				closeNode( arLi[c] );
		else            //  open
			for( var c = arLi.length - 1; c >= 0; c-- )
				openNode( arLi[c] );

	}


	function onTop( wrap ){

		if( hasClass( wrap, 'pp_fixed' ) ){
			wrap.className = wrap.className.replace( ' pp_fixed', '' );
		}
		else {

			var divs = document.getElementsByTagName( 'DIV' );
			for( var c = divs.length - 1; c >= 0; c-- ){
				if( hasClass( divs[c], 'pp_fixed' ) ){
					divs[c].className = divs[c].className.replace( ' pp_fixed', '' );
				}
			}

			wrap.className += ' pp_fixed';

		}

	}


	function searchHdlr(){

		var
			searchString = this.value,
			found = recSearch( this.parentNode.nextSibling.nextSibling, this.value.toLowerCase() );

		this.nextSibling.innerHTML = 'found: ' + found.length;
		this.found = found;

	}


	function searchKeyHdlr( event ){

		event = event || window.event;

		//  hit ENTER
		if( event.keyCode == 13 && this.found && this.found.length ){

			var firstFound = this.found.pop();

			openNodeUpWard( firstFound );
			firstFound.scrollIntoView();

			this.found.unshift( firstFound );

		}

	}


	function recSearch( node, text ){

		var found = [];

		if ( node.tagName == 'SPAN' && hasClass( node, classKey+'|'+classValue ) ) {

			//  remove old marks
			if( node.getElementsByTagName( 'EM' ).length ){

				var prevNode = null;
				var i = 0;

				while( i < node.childNodes.length ){

					var curNode = node.childNodes[i];

					if( curNode.nodeType == 3 ){

						if( prevNode ){
							prevNode.nodeValue += curNode.nodeValue;
							node.removeChild( curNode );
						}
						else{
							prevNode = curNode;
							++i;
						}

					}
					else if(curNode.nodeType == 1 ){

						if( hasClass( curNode, classFoundInText ) ){
							if( ! prevNode ){
								prevNode = document.createTextNode( '' );
								node.insertBefore( prevNode, curNode );
								++i;
							}
							prevNode.nodeValue += curNode.innerText || curNode.textContent;
							node.removeChild( curNode );
						}
						else{
							prevNode = null;
							++i;
						}

					}

				}

			}


			if( text ){

				var
					textLength = text.length,
					textNode = node.childNodes[0],
					startPos = textNode.nodeValue.toLowerCase().indexOf( text );

				if( startPos > -1 ){

					var
						foundText = textNode.nodeValue.substring(
							startPos,
							startPos + textLength
						),
						mark = document.createElement( 'EM' );

					mark.className = classFoundInText;
					mark.appendChild( document.createTextNode( foundText ) );

					var tail = textNode.splitText( startPos );
					tail.nodeValue = tail.nodeValue.substring( textLength );
					node.insertBefore( mark, tail );

					found.push( node );

				}

			}

		}
		else if( node.children.length ){

			var children = node.children;
			for( var i = children.length - 1; i >= 0; i-- ){
				found = found.concat( recSearch( children[i], text ) );
			}

		}

		return found;

	}


	function hasClass( elem, className ){

		return new RegExp( '(^|\\s)'+className+'(\\s|$)' ).test( elem.className );

	}


	/**
	 *  Applies handler to control
	 */
	function applyHdlr( target, handler, type ){

		type = type || 'click';

		if( target.addEventListener )
			target.addEventListener( type, handler, false );
		else
			target.attachEvent( 'on' + type, function(){ handler.call( target ) } );

	}

}
