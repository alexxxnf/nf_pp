<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Pretty print_r</title>
</head>
<body>
<a href="https://github.com/alexxxnf/nf_pp/"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"></a>
<?
include 'nf_pp.php';

class testClass {

	public $a = 'a';
	private $b = 'b';
	private $кириллица = 'свойство класса, названное по-русски';  //  property named in russian
	protected $c = 'c';

}

class emptyTestClass {

}

$a = new testClass;
$b = new emptyTestClass;


$res = fopen(__FILE__, 'r');

$val = array(
	'string' => 'string',
	'int' => 123,
	'float' => 123.3,
	'subarray' => array(
		'a' => 'Lorem ipsum dolor sit amet, neptune reddens pater unica suae in fuerat construeret in fuerat accidens inquit merui litore. Opto cum magna aliter refundens domum sum in lucem exempli paupers coniunx in lucem exitum atque bona delata iuvenis. Eo debeas ait regem ut sua confusus eos, viam iube es ego esse ait est in. Musis nihilominus admonendus tu mihi quidditas. Tum vero rex ut diem obiecti ad te finis puellam effari ergo accipiet si. Vituperia ad suis caelo in rei exultant deo hanc! Taliarchum in fuerat est se sed, nuptui tradiditque corpus multis miraculum manibus dimittit in! Nuntiatur quae ait Cumque hoc ait regem consolatus dum veniens Theophilum vinum dolor Jesus Circumdat flante vestibus mundo anima. Lycoridem Apollonio vidit pater beneficiorum universos civitatem auri tecum ad suis ut libertatem accipies. Redde pariter necandum loco in deinde vero rex in modo. Acceptis codicello de his domino nostrud exercitu necessitate sit dolor ad per dicis ubi diu desideriis meo.',
		'b' => 'Quattuordecim anulum in modo invenit',
		'subsubarray' => array(
			'Lorem ipsum dolor sit amet',
			'deducitur potest meum festinus',
			'pervenissem filia omnes deo',
			'hanc nec caecatus dum animae',
		),
	),
	'false' => FALSE,
	'true' => TRUE,
	'null' => NULL,
	'object' => $a,
	'resource' => $res,
	'emptyArray' => array(),
	'emptyObject' => $b
);
?>
<div style="overflow: hidden">
	<div style="width: 50%; float: left;"><h2>pp( $val )</h2><?pp( $val );?></div>
	<div style="width: 50%; float: left;"><h2>print_r( $val ) + &lt;pre&gt;</h2><pre><?print_r( $val );?></pre></div>
</div>
<h2 id="autoCollapsed">Folded array</h2>
<?pp( $val, TRUE );?>
<h2 id="autoOpened">Array unfolded to keys "c" and "subarray"</h2>
<?pp( $val, array( 'autoOpen' => array( 'c', 'subarray' ) ) );?>
<h2 id="autoOpened2">Array unfolded to key "c"</h2>
<?pp( $val, 'c' );?>
<?fclose($res);?>
</body>
</html>