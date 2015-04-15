<?php

function printResult(){
	$numbers = [1, 5, 6, 11, 14, 20];
	$sum     = 22;
	$result  = getIndexes($numbers, $sum);
	if($result !== false){
		$b = [];
		$i = 0;
		foreach($numbers as $k=>$v) $b[$k] = isset($result[$k]) ? 1 : 0;
		$formula   = [];
		$check_sum = 0;
		foreach($b as $k=>$v){
			$formula[]  = "$numbers[$k] * $v";
			$check_sum += $numbers[$k] * $v;
		}
		echo implode(' + ', $formula).' = <strong>'.$check_sum.'</strong>';
	}
	return false;
}
function getIndexes($numbers, $sum) {
	$cnt 		  = count($numbers);
	$combinations = pow(2,$cnt)-1;
	for ($i=1; $i <= $combinations; $i++) {
		$bits 	  	  = decbin($i);
		$full_bits 	  = str_pad($bits, $cnt, "0", STR_PAD_LEFT);
		$reverse_bits = strrev($full_bits);
		$bits_array   = array();
		for($j=0; $j < $cnt; $j++) {
			$bits_array[] = $reverse_bits[$j];
		}
		$l   = 0; 
		$arr = [];
		foreach ($bits_array as $k=>$v) {
			if ($v == 1) {$arr[$l] .= $numbers[$l];}
			$l++;
		}
		if(array_sum($arr) == $sum) return $arr;
	}
	return false;
}

printResult();
