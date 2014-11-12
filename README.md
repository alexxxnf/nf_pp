nf_pp
=====

`nf_pp` is a class designed to emulate PHP function "print_r" with some additional features.

![illustration](http://blog.alexxxnf.ru/img/article/print_r/compare.png)

Features
--------

* print scalar variables, arrays, object, resources;
* highlight data types;
* highlight properties scope;
* visualize values of the boolean and NULL variables;
* show resource type;
* trim long strings;
* print tree-like view for arrays and objects;
* fold nodes in arrays and objects;
* fold whole tree or unfold tree to a certain key;
* display file and line where the function was called;
* print elapsed time between function calls;
* search in keys and values.

Usage
-----

Include `nf_pp.php` file, create an object and pass options to it.

    include 'nf_pp.php';
    $pp = new nf_pp( array( 'trimString' => 0 ) );

And use it.

    $pp->pp( $val1 );
    $pp->pp( $val2 );

If you don't like to create an object, you can use a shortcut function.

    pp( $val, array( 'trimString' => 0 ) );

### Options

`trimString` — Default value is 100 simbols. 0 — disable trimming.
`autoCollapsed`	— Fold tree. Default value is `FALSE`.
`autoOpen` — Array of keys or a single key which will be used to unfold the tree.

You can pass options to the function as an array or one by one in any order. For example:

    pp( $val, 300, 'key' );

or

    pp( $val, 'key', 0 );

or

    pp( $val, 'key' );

Options are determined by types. If number is passed, then it is `trimString` option. If boolean is passed, then it is `autoCollapsed` option. If string is passed, then it is `autoOpen` option.

### Examples

See `demo.php`.
Online demo can be found here [http://blog.alexxxnf.ru/demo/print_r/](http://blog.alexxxnf.ru/demo/print_r/).

Print an array

    pp( $val );

Print folded array

    pp( $val, TRUE );

Print folded array and unfold it to keys "c" and "subarray"

    pp( $val, array( 'autoOpen' => array( 'c', 'subarray' ) ) );

Print folded array and unfold it to key "c"

    pp( $val, array( 'autoOpen' => array( 'c' ) ) );

or

    pp( $val, 'c' );