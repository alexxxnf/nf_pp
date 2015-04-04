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
		classOnTop      = 'pp_fixed',
		classFoundInText = 'pp_foundInText',
		classNoTrim     = 'pp_noTrim';


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
		else if ( hasClass( clickedElem, classValue ) ) {

			var node = clickedElem.parentNode;

			if( hasClass( node, classNoTrim ) )
				enableTrim( node );
			else
				disableTrim( node );

		}

	}


	function closeNode( node ){

		removeClass( node, classOpened );
		addClass( node, classClosed );

	}


	function openNode( node ){

		removeClass( node, classClosed );
		addClass( node, classOpened );

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


	function disableTrim( node ) {

		addClass( node, classNoTrim );

	}


	function enableTrim( node ) {

		removeClass( node, classNoTrim );

	}


	function onTop( wrap ){

		if( hasClass( wrap, classOnTop ) ){
			removeClass( wrap, classOnTop );
		}
		else {

			var divs = document.getElementsByTagName( 'DIV' );
			for( var c = divs.length - 1; c >= 0; c-- ){
				if( hasClass( divs[c], classOnTop ) ){
					removeClass( divs[c], classOnTop );
				}
			}

			addClass( wrap, classOnTop );

		}

	}


	function searchHdlr(){

		var
			searchString = this.value,
			found = recSearch( this.parentNode.nextSibling.nextSibling, this.value.toLowerCase() );

		this.parentNode.getElementsByTagName('SPAN')[0].innerHTML = 'found: ' + found.length;
		this.found = found;
		this.foundCurIdx = -1;

	}


	function searchKeyHdlr( event ){

		event = event || window.event;

		//  hit ENTER
		if( event.keyCode == 13 && this.found && this.found.length ){

			if( event.shiftKey )
				moveFoundIndexBackward( this );
			else
				moveFoundIndexForward( this );

			gotoFoundNode( this.found[this.foundCurIdx] );

		}

	}


	function moveFoundIndexForward( searchInput ){

		if( searchInput.foundCurIdx == searchInput.found.length - 1)
			searchInput.foundCurIdx = 0;
		else
			++searchInput.foundCurIdx;

	}


	function moveFoundIndexBackward( searchInput ){

		if( searchInput.foundCurIdx <= 0)
			searchInput.foundCurIdx = searchInput.found.length - 1;
		else
			--searchInput.foundCurIdx;

	}


	function gotoFoundNode( gotoNode ){

		var valueNode = gotoNode.parentNode;

		disableTrim( valueNode.parentNode );
		openNodeUpWard( valueNode );
		gotoNode.scrollIntoView();

	}


	function recSearch( node, text ){

		var found = [];

		if ( node.tagName == 'SPAN' && hasClass( node, classKey ) || hasClass( node, classValue ) ) {

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
					startPos = -1;

				while ((startPos = textNode.nodeValue.toLowerCase().indexOf( text, startPos + 1 )) != -1) {

					var
						foundText = textNode.nodeValue.substring(
							startPos,
							startPos + textLength
						),
						mark = document.createElement( 'EM' );

					addClass( mark, classFoundInText);
					mark.appendChild( document.createTextNode( foundText ) );

					var tail = textNode.splitText( startPos );
					tail.nodeValue = tail.nodeValue.substring( textLength );
					node.insertBefore( mark, tail );

					found.push( mark );

					textNode = tail;

				}

			}

		}
		else if( node.children.length ){

			var children = node.children;
			for( var i = 0, l = children.length; i < l; ++i ){
				found = found.concat( recSearch( children[i], text ) );
			}

		}

		return found;

	}


	function hasClass( elem, className ){

		return ( ' ' + elem.className + ' ' ).indexOf( ' ' + className + ' ' ) >= 0;

	}


	function addClass( elem, className ){

		if ( ! hasClass( elem, className ) ) {
			elem.className += ( elem.className ? ' ' : '' ) + className;
		}

	}


	function removeClass( elem, className ){

		elem.className = elem.className.split( className ).join( '' ).replace( /^\s+|\s+$/g, '' );

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
