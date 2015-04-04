<?
/**
 * nf_pp
 *
 * The class is designed to emulate function "print_r" with some additional
 *   features, such as:
 *     - highlight data types;
 *     - highlight properties scope;
 *     - visualize values of the boolean and NULL variables;
 *     - show resource type;
 *     - trim long strings;
 *     - fold nodes in arrays and objects;
 *     - fold whole tree or unfold tree to a certain key;
 *     - print elapsed time between function calls;
 *     - search in keys and values.
 *
 * @author MAYDOKIN Aleksey
 * @version 2.2.0
 */
class nf_pp {

	public
		$trimString    = 1000,
		$autoCollapsed = FALSE,
		$autoOpen      = array();

	protected
		$arRecursion = array();

	protected static
		$jsFuncDisp   = FALSE,
		$cssDisp      = FALSE,
		$callCntr     = 0,
		$lastCallTime = 0;

	const TRACE_DEPTH = 0;  //  how many wrap functions has pp-method ( except pp-function )


	function __construct(){

		$options = func_get_args();
		$options = call_user_func_array( array( __CLASS__, 'parseOptions' ), $options );

		if( isset( $options['trimString'] ) )
			$this->trimString = intval( $options['trimString'] );

		if( isset( $options['autoCollapsed'] ) )
			$this->autoCollapsed = $options['autoCollapsed'];

		if( isset( $options['autoOpen'] ) ){

			$options['autoOpen'] = (array)$options['autoOpen'];

			$this->autoOpen      = $options['autoOpen'];
			$this->autoCollapsed = TRUE;

		}

	}


	/**
	 *  Guesses the options
	 */
	function parseOptions(){

		$options = func_get_args();

		if( sizeof( $options ) == 0 )  //  default
			return $options;

		if( is_array( $options[0] ) )  //  trivial options
			return $options[0];

		$newOptions = array();

		foreach( $options as $opt ){

			switch( gettype( $opt ) ){

				case 'boolean':
					$newOptions['autoCollapsed'] = $opt;
					break;

				case 'integer':
					$newOptions['trimString'] = $opt;
					break;

				case 'string':
				case 'array':
					$newOptions['autoOpen'] = $opt;
					break;

			}

		}

		return $newOptions;

	}

	function pp( $val, $curLevel = 0, $key = NULL, $isLast = true ){

		if( $curLevel == 0 ){
			$this->arRecursion = array();  //  drop recursion cache between top-level funciton calls
			$domId = 'pp_' . ++self::$callCntr;
			echo '<div class="pp_wrap" id="'.$domId.'">';
			$this->backtrace();
			$this->timestamp();
			echo '<div><input type="search" class="pp_search" placeholder="Search"><span class="pp_found"></span></div>';
			echo '<div><a href="javascript:;" class="pp_top">on top</a></div>';
			echo '<ul class="pp_container">';
		}

		//  determine type of the variable
		$valType = gettype( $val );

		//  classes for the node
		$arClasses = array( 'pp_node' );

		if( $curLevel == 0 )
			$arClasses[] = 'pp_isRoot';

		if( $isLast )
			$arClasses[] = 'pp_isLast';

		if( $valType == 'array' || $valType == 'object' )
			$arClasses[] = 'pp_expandOpen';
		else
			$arClasses[] = 'pp_expandLeaf';

		echo '<li class="'.implode( ' ', $arClasses ).'">';
		echo '<div class="pp_expand"></div>';
		echo '<div class="pp_content">';

		if( $key !== NULL )
			echo '<span class="pp_key">['.$key.']</span> => ';


		switch( $valType ){

			case 'boolean':
				$this->p_bool( $val );
				break;

			case 'NULL':
				$this->p_null( $val );
				break;

			case 'integer':
			case 'double':
			case 'float':
				$this->p_basic( $val );
				break;

			case 'string':
				$this->p_string( $val );
				break;

			case 'array':
				$this->p_array( $val, $curLevel );
				break;

			case 'object':
				$this->p_object( $val, $curLevel );
				break;

			case 'resource':
				$this->p_res( $val );
				break;

			default:
				$this->p_unknown( $val );

		}

		echo '</li>';

		if( $curLevel == 0 ){
			echo '</ul>';
			$this->p_css();
			$this->p_jsfunc();
			$this->p_jsinit( $domId );
			echo '</div>';
		}

	}


	protected function p_bool( $val ){

		echo '<span class="pp_bool pp_value">'.strtoupper( var_export( $val, TRUE ) ).'</span></div>';

	}

	protected function p_null( $val ){

		echo '<span class="pp_null pp_value">'.strtoupper( var_export( $val, TRUE ) ).'</span></div>';

	}

	protected function p_basic( $val ){

		echo '<span class="pp_num pp_value">'.$val.'</span></div>';

	}

	protected function p_string( $val ){

		$val = htmlspecialchars( $val );
		if( $this->trimString > 0 && strlen( $val ) > $this->trimString ){

			if( $this->trimString > 3 )
				$val = substr( $val, 0, $this->trimString - 3 ).'...';
			else
				$val = substr( $val, 0, $this->trimString );

		}

		echo '<span class="pp_string pp_value">'.$val.'</span></div>';

	}

	protected function p_array( $val, $curLevel ){

		$size = sizeof( $val );

		echo '<span class="pp_array pp_value">Array</span><i class="pp_ctrl pp_ctrlCollapseCh" title="Fold/unfold children">('.$size.')</i></div>';
		echo '<ul class="pp_container">';

		if( $size ){

			$c = 1;
			foreach( $val as $k => $v )
				echo $this->pp( $v, $curLevel + 1, htmlspecialchars( $k ), $c++ == $size );

		}
		else{

			echo '<li class="pp_node pp_expandLeaf pp_isLast"><div class="pp_expand"></div><div class="pp_content"><span class="pp_empty">EMPTY</span></div></li>';

		}
		echo '</ul>';

	}

	protected function p_object( $val, $curLevel ){

		$className = get_class( $val );
		$val = (array)$val;
		$size = sizeof( $val );

		echo '<span class="pp_object pp_value">Object &lt;'.$className.'&gt;</span><i class="pp_ctrl pp_ctrlCollapseCh" title="Fold/unfold children">('.$size.')</i></div>';
		echo '<ul class="pp_container">';

		if( ! in_array( $val, $this->arRecursion, true ) ){  //  check for recursion

			if( $size ){

				$this->arRecursion[] = $val;

				$c = 1;
				foreach( $val as $k => $v ){

					if( strpos( $k, chr(0).$className.chr(0) ) === 0 ){
						$k = str_replace( chr(0).$className.chr(0), '', $k );
						$k = htmlspecialchars( $k ).':<span class="pp_scope pp_scope_private">private</span>';
					}
					elseif( strpos( $k, chr(0).'*'.chr(0) ) === 0 ){
						$k = str_replace( chr(0).'*'.chr(0), '', $k );
						$k = htmlspecialchars( $k ).':<span class="pp_scope pp_scope_protected">protected</span>';
					}
					else{
						$k = htmlspecialchars( $k ).':<span class="pp_scope pp_scope_public">public</span>';
					}

					echo $this->pp( $v, $curLevel + 1, $k, $c++ == $size );

				}

			}
			else{

				echo '<li class="pp_node pp_expandLeaf pp_isLast"><div class="pp_expand"></div><div class="pp_content"><span class="pp_empty">EMPTY</span></div></li>';

			}

		}
		else {

			echo '<li class="pp_node pp_expandLeaf pp_isLast"><div class="pp_expand"></div><div class="pp_content"><span class="pp_empty">RECURSION</span></div></li>';

		}

		echo '</ul>';

	}

	protected function p_res( $val ){

		echo '<span class="pp_resource pp_value">'.$val.' &lt;'.get_resource_type( $val ).'&gt;</span></div>';

	}

	protected function p_unknown( $val ){

		echo '<span class="pp_unknown pp_value">"'.$val.'"</span></div>';

	}

	/**
	 *  Prints a mark before the tree
	 */
	protected function backtrace(){

		$backtrace = debug_backtrace();

		if( $backtrace[2]['function'] == 'pp' )
			$arToPrint = $backtrace[2 + self::TRACE_DEPTH];  //  run as a function
		else
			$arToPrint = $backtrace[1 + self::TRACE_DEPTH];  //  run as a method

		echo '<div class="pp_mark">'.$arToPrint['file'].' <span title="Line number">'.$arToPrint['line'].'</span></div>';

	}


	/**
	 *  Prints elapsed time between function calls.
	 */
	protected function timestamp(){

		$curTime = microtime( TRUE );

		echo '<div class="pp_mark" title="Elapsed time between function calls">';

		if( self::$lastCallTime > 0 )
			echo ( $curTime - self::$lastCallTime ).' sec.';
		else
			echo 'first call';

		echo '</div>';

		self::$lastCallTime = $curTime;

	}

	protected function p_jsfunc(){

		if( self::$jsFuncDisp )
			return;
		else
			self::$jsFuncDisp = TRUE;

		echo '<script type="text/javascript">

function nf_pp_init(m,x,u){function q(a){n(a,"pp_expandOpen");l(a,"pp_expandClosed")}function r(a){n(a,"pp_expandClosed");l(a,"pp_expandOpen")}function t(a){r(a);g(a,"pp_isRoot")||t(a.parentNode.parentNode)}function y(a,b){for(var d=a.getElementsByTagName("SPAN"),c=[],f=d.length-1;0<=f;f--){var e=d[f];if(g(e,"pp_key"))for(var h=b.length-1;0<=h;h--)if(-1!=e.innerHTML.search(new RegExp("\\\\["+b[h]+"(:<span[^<]*</span>)?\\\\]","i"))){c.push(e.parentNode.parentNode);break}}for(f=c.length-1;0<=f;f--)t(c[f])}
function z(a){a=a.getElementsByTagName("LI");for(var b=a.length-1;0<=b;b--){var d=a[b];g(d,"pp_node")&&q(d)}}function v(){var a=w(this.parentNode.nextSibling.nextSibling,this.value.toLowerCase());this.parentNode.getElementsByTagName("SPAN")[0].innerHTML="found: "+a.length;this.found=a;this.foundCurIdx=-1}function w(a,b){var d=[];if("SPAN"==a.tagName&&g(a,"pp_key")||g(a,"pp_value")){if(a.getElementsByTagName("EM").length)for(var c=null,f=0;f<a.childNodes.length;){var e=a.childNodes[f];3==e.nodeType?
c?(c.nodeValue+=e.nodeValue,a.removeChild(e)):(c=e,++f):1==e.nodeType&&(g(e,"pp_foundInText")?(c||(c=document.createTextNode(""),a.insertBefore(c,e),++f),c.nodeValue+=e.innerText||e.textContent,a.removeChild(e)):(c=null,++f))}if(b)for(var f=b.length,h=a.childNodes[0],c=-1;-1!=(c=h.nodeValue.toLowerCase().indexOf(b,c+1));){var k=h.nodeValue.substring(c,c+f),e=document.createElement("EM");l(e,"pp_foundInText");e.appendChild(document.createTextNode(k));h=h.splitText(c);h.nodeValue=h.nodeValue.substring(f);
a.insertBefore(e,h);d.push(e)}}else if(a.children.length)for(c=a.children,f=0,e=c.length;f<e;++f)d=d.concat(w(c[f],b));return d}function g(a,b){return 0<=(" "+a.className+" ").indexOf(" "+b+" ")}function l(a,b){g(a,b)||(a.className+=(a.className?" ":"")+b)}function n(a,b){a.className=a.className.split(b).join("").replace(/^\s+|\s+$/g,"")}function p(a,b,d){d=d||"click";a.addEventListener?a.addEventListener(d,b,!1):a.attachEvent("on"+d,function(){b.call(a)})}var k=document.getElementById(m);m=k.children[2].firstChild;
x&&z(k);u.length&&y(k,u);p(k,function(a){a=a||window.event;a=a.target||a.srcElement;if(g(a,"pp_expand")){var b=a.parentNode;g(b,"pp_expandOpen")?q(b):g(b,"pp_expandClosed")&&r(b)}else if(g(a,"pp_ctrlCollapseCh")){b=a.parentNode.parentNode;a=!1;for(var d=null,c=b.children.length-1;0<=c;c--)if("UL"==b.children[c].nodeName){d=b.children[c];break}b=null;d&&(b=d.children);for(c=b.length-1;0<=c;c--)if(g(b[c],"pp_expandOpen")){a=!0;break}if(a)for(c=b.length-1;0<=c;c--)q(b[c]);else for(c=b.length-1;0<=c;c--)r(b[c])}else if(g(a,
"pp_top"))if(g(k,"pp_fixed"))n(k,"pp_fixed");else{a=document.getElementsByTagName("DIV");for(d=a.length-1;0<=d;d--)g(a[d],"pp_fixed")&&n(a[d],"pp_fixed");l(k,"pp_fixed")}else g(a,"pp_value")&&(b=a.parentNode,g(b,"pp_noTrim")?n(b,"pp_noTrim"):l(b,"pp_noTrim"))});p(m,v,"input");p(m,function(){"value"==event.propertyName&&v.apply(this)},"propertychange");p(m,function(a){a=a||window.event;if(13==a.keyCode&&this.found&&this.found.length){a.shiftKey?0>=this.foundCurIdx?this.foundCurIdx=this.found.length-
1:--this.foundCurIdx:this.foundCurIdx==this.found.length-1?this.foundCurIdx=0:++this.foundCurIdx;a=this.found[this.foundCurIdx];var b=a.parentNode;l(b.parentNode,"pp_noTrim");t(b);a.scrollIntoView()}},"keyup")};
</script>';

	}


	protected function p_jsinit( $id ){

		echo '<script type="text/javascript">
			nf_pp_init( "'.$id.'", '.( $this->autoCollapsed ? 'true': 'false' ).', '.json_encode( $this->autoOpen ).' );
		</script>';

	}


	protected function p_css(){

		if( self::$cssDisp )
			return;
		else
			self::$cssDisp = TRUE;

		echo '<style type="text/css">

.pp_container{padding:0;margin:0}.pp_container li{list-style:none}.pp_node{background:url(data:image/gif;base64,R0lGODlhEgASAIABAHJycv///yH5BAEAAAEALAAAAAASABIAAAIejB+Ay6YNU4RvrmoPzpJr/4EduGWldU5ptFLi6LUFADs=) repeat-y;margin:0 0 0 18px;zoom:1}.pp_isLast{background:url(data:image/gif;base64,R0lGODlhEgASAIABAHJycv///yH5BAEAAAEALAAAAAASABIAAAIYjB+Ay6YNU4RvrmoPzpJr/4HiSJbmiaYFADs=) no-repeat}.pp_isRoot{margin-left:0;background:0 0}.pp_expandOpen .pp_expand{background:url(data:image/gif;base64,R0lGODlhEgASAMZFAAAAAHmWwURERDk5OYGcxfz8/Pr6+uLm7P///v7+/e7w84GdxfP09u/x9PX29+ns8Pz8+/79/crT4Pn6+uXo7ufq77rG183V4fDy9MXP3ezu8vHz9e30/tbc5vf3+Ovu8fb3+Obq7+zv8sTO3Ojr79vg6O3v8uPn7cTN3P7+/+Xp7n2axN3i6vj5+YCcxd7j6tzh6ff4+PLz9YGcxPHy9NTb5d/k6/v7+/n5+dLZ5P3+/77J2dXb5b/J2evt8czU4dPa5PT19uTo7cvU4P7+/v////n5+vT199rf6Ort8f///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////yH5BAEAAH8ALAAAAAASABIAAAeFgH+Cg4SFhoeIiYqLiAGOj5ABhwEWPRkXHTYqPg0zkzsjPzwvFEkNQQSTKEM1LEIPCgwxqYYBEkAwJ7AMHga0hQE5JQPEAwIGBQsCy8uCAUgHJCYyIBMFRAuTBxUiGw5GBQlFv4QBIRo0DjgQCQg6LpMfGEctNxEIKRwrk5GRjP8AAxYKBAA7)}.pp_expandClosed .pp_expand{background:url(data:image/gif;base64,R0lGODlhEgASAMZDAAAAAHmWwURERDk5OYGcxfz8/Pr6+v///uLm7P7+/YGdxfP09u/x9O7w88rT4P79/fHz9brG1/n6+vz8++fq7/Dy9MXP3czU4c3V4e30/tbc5uzu8uXo7vf3+Ovu8fb3+Obq7+zv8sTO3Ojr79vg6O3v8uPn7cTN3P7+/+Xp7n2axICcxYGcxPn5+fj5+ff4+N7j6t3i6tLZ5N/k6/X29+ns8NTb5f3+//v7+/Hy9NXb5b/J2fT1977J2dPa5PT19uvt8drf6P7+/v///9zh6cvU4Ort8fn5+v///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////yH5BAEAAH8ALAAAAAASABIAAAeFgH+Cg4SFhoeIiYqLiAGOj5ABhwEROxYYGjMpQAwskz0iFzowHEYMPwSTJ0U2MQM1DQsvqYYBDj5EJgOxHQa0hQEyJAPEAwIGBQoCy8uCAUEIIyUDHxIFQgqTCBQhEAJHBQlDv4QBIBs5NC0TCQc3K5MeFTwuOA8HKBkqk5GRjP8AAxYKBAA7)}.pp_expandLeaf .pp_expand{background:url(data:image/gif;base64,R0lGODlhEgASAKECAAAAAHJycv///////yH5BAEAAAAALAAAAAASABIAAAIVhI+py+0Po5xUhjuv3lr1CobiSFYFADs=)}.pp_content{min-height:18px;margin-left:18px;line-height:1.4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.pp_content.pp_noTrim{white-space:normal}* html .pp_content{height:18px}.pp_expand{width:18px;height:18px;float:left}.pp_expandOpen .pp_container{display:block}.pp_expandClosed .pp_container{display:none}.pp_expandClosed .pp_expand,.pp_expandOpen .pp_expand{cursor:pointer}.pp_expandLeaf .pp_expand{cursor:auto}.pp_wrap{font:400 14.3px/1.4 monospace;margin:0 0 10px;padding:5px 10px;border:1px solid #000}.pp_fixed{height:480px;width:640px;overflow:auto;margin:-240px 0 0 -320px;position:fixed;left:50%;top:50%;z-index:9999;background:#FFF}.pp_mark{padding:0;font-weight:700}.pp_ctrl{font-style:normal;cursor:pointer}.pp_ctrlCollapseCh{padding:0 0 0 .1em}.pp_key{font-weight:700}.pp_scope{font-style:italic}.pp_scope_public{color:#724ADC}.pp_scope_private{color:red}.pp_scope_protected{color:gray}.pp_bool,.pp_null{font-style:italic;color:#b5b326}.pp_empty{font-style:italic;color:#aaa}.pp_num{color:#19869e}.pp_string{color:#555}.pp_resource{color:#74169b}.pp_object{color:#cc121a}.pp_array{color:#121acc}.pp_search{margin-right:1em}.pp_foundInText{background:#38d878;font:inherit;color:#000}</style>';

	}

}


function pp(){

	$options = func_get_args();
	$val = array_shift( $options );  //  trim first argument

	//  crazy thing to call constructor with variable arguments number
	$reflection = new ReflectionClass( 'nf_pp' );
	$pp = $reflection->newInstanceArgs( $options );

	$pp->pp( $val );
	unset( $pp, $reflection, $val, $options );

}