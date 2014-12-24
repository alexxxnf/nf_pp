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
 * @version 2.1.0
 */
class nf_pp {

	public
		$trimString    = 100,
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
			echo '<div><input type="search" class="pp_search"><span class="pp_found"></span></div>';
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
{{nf_pp.js}}
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
{{nf_pp.css}}
</style>';

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