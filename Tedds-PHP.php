
	<?php
	 ini_set('max_execution_time',300);//to pervent time error//changed to account for huge processing rates of the new files
	// Function that checks whether the data are the on-screen text.
	// It works in the following way:
	// an array arrfailAt stores the control words for the current state of the stack, which show that
	// input data are something else than plain text.
	// For example, there may be a description of font or color palette etc. 
	function rtf_isPlainText($s) {
		$arrfailAt = array("*", "fonttbl", "colortbl", "datastore", "themedata");
		for ($i = 0; $i < count($arrfailAt); $i++)
			if (!empty($s[$arrfailAt[$i]]))
				return false;
		return true;
	}
	 
	function rtf2text($filename) {
		//echo "<h1>" . $filename . "</h1>";
		// Read the data from the input file.
		$text = file_get_contents($filename);
		if (!strlen($text))
			return "";
		return rtfStr2text($text);
	}
	 
	function rtfStr2text($text) {
		// Create empty stack array.
		$document = "";
		$stack = array();
		$j = -1;
		// Read the data character-by- character…
		for ($i = 0, $len = strlen($text); $i < $len; $i++) {
			$c = $text[$i];
	 
			// Depending on current character select the further actions.
			switch ($c) {
				// the most important key word backslash
				case "\\":
					// read next character
					$nc = $text[$i + 1];
	 
					// If it is another backslash or nonbreaking space or hyphen,
					// then the character is plain text and add it to the output stream.
					if ($nc == '\\' && rtf_isPlainText($stack[$j]))
						$document .= '\\';
					elseif ($nc == '~' && rtf_isPlainText($stack[$j]))
						$document .= ' ';
					elseif ($nc == '_' && rtf_isPlainText($stack[$j]))
						$document .= '-';
					// If it is an asterisk mark, add it to the stack.
					elseif ($nc == '*')
						$stack[$j]["*"] = true;
					// If it is a single quote, read next two characters that are the hexadecimal notation
					// of a character we should add to the output stream.
					elseif ($nc == "'") {
						$hex = substr($text, $i + 2, 2);
						if (rtf_isPlainText($stack[$j]))
							$document .= html_entity_decode("&#" . hexdec($hex) . ";");
						//Shift the pointer.
						$i += 2;
						// Since, we’ve found the alphabetic character, the next characters are control word
						// and, possibly, some digit parameter.
					} elseif ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
						$word = "";
						$param = null;
	 
						// Start reading characters after the backslash.
						for ($k = $i + 1, $m = 0; $k < strlen($text); $k++, $m++) {
							$nc = $text[$k];
							// If the current character is a letter and there were no digits before it,
							// then we’re still reading the control word. If there were digits, we should stop
							// since we reach the end of the control word.
							if ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
								if (empty($param))
									$word .= $nc;
								else
									break;
								// If it is a digit, store the parameter.
							} elseif ($nc >= '0' && $nc <= '9')
								$param .= $nc;
							// Since minus sign may occur only before a digit parameter, check whether
							// $param is empty. Otherwise, we reach the end of the control word.
							elseif ($nc == '-') {
								if (empty($param))
									$param .= $nc;
								else
									break;
							}
							else
								break;
						}
						// Shift the pointer on the number of read characters.
						$i += $m - 1;
	 
						// Start analyzing what we’ve read. We are interested mostly in control words.
						$toText = "";
	 
						switch (strtolower($word)) {
							// If the control word is "u", then its parameter is the decimal notation of the
							// Unicode character that should be added to the output stream.
							// We need to check whether the stack contains \ucN control word. If it does,
							// we should remove the N characters from the output stream.
							case "u":
								$toText .= html_entity_decode("&#x" . dechex($param) . ";");
								$ucDelta = @$stack[$j]["uc"];
								if ($ucDelta > 0)
									$i += $ucDelta;
								break;
							// Select line feeds, spaces and tabs.
							case "par": case "page": case "column": case "line": case "lbr":
								$toText .= "\n";
								break;
							case "emspace": case "enspace": case "qmspace":
								$toText .= " ";
								break;
							case "tab": $toText .= "\t";
								break;
							// Add current date and time instead of corresponding labels.
							case "chdate": $toText .= date("m.d.Y");
								break;
							case "chdpl": $toText .= date("l, j F Y");
								break;
							case "chdpa": $toText .= date("D, j M Y");
								break;
							case "chtime": $toText .= date("H:i:s");
								break;
							// Replace some reserved characters to their html analogs.
							case "emdash": $toText .= html_entity_decode("&mdash;");
								break;
							case "endash": $toText .= html_entity_decode("&ndash;");
								break;
							case "bullet": $toText .= html_entity_decode("&#149;");
								break;
							case "lquote": $toText .= html_entity_decode("&lsquo;");
								break;
							case "rquote": $toText .= html_entity_decode("&rsquo;");
								break;
							case "ldblquote": $toText .= html_entity_decode("&laquo;");
								break;
							case "rdblquote": $toText .= html_entity_decode("&raquo;");
								break;
							// Add all other to the control words stack. If a control word
							// does not include parameters, set &param to true.
							default:
								$stack[$j][strtolower($word)] = empty($param) ? true : $param;
								break;
						}
						// Add data to the output stream if required.
						$stackTest = "cf1";
						if (rtf_isPlainText($stack[$j])) {
							$document .= $toText;
						}
					}
	 
					$i++;
					break;
				// If we read the opening brace {, then new subgroup starts and we add
				// new array stack element and write the data from previous stack element to it.
				case "{":
					array_push($stack, $stack[$j++]);
					break;
				// If we read the closing brace }, then we reach the end of subgroup and should remove 
				// the last stack element.
				case "}":
					array_pop($stack);
					$j--;
					break;
				// Skip “trash”.
				case '\0': case '\r': case '\f': case '\n': break;
				// Add other data to the output stream if required.
				default:
					if (rtf_isPlainText($stack[$j]))
						$document .= $c;
					break;
			}
		}
		// Return result.
		return $document;
	}
	 
	function msWord2Text($userDoc) {
		$iLineTeller = 0;
		$sPreviousLine = "";
	 
		$line = file_get_contents($userDoc);
		$lines = explode(chr(0x0D), $line);
		$outtext = "";
	 
		foreach ($lines as $thisline) {
			$pos = strpos($thisline, chr(0x00));
			$stringlengte = strlen($thisline);
			if (($pos !== FALSE) || ($stringlengte == 0)) {
				//print("$thisline\n"); 
			} else {
				//first line bug... 
				if ($iLineTeller == 0) {
					$lastpos = strrpos($sPreviousLine, chr(0x00));
					$sTekst = substr($sPreviousLine, $lastpos, strlen($sPreviousLine) - $lastpos);
					$outtext .= $sTekst . "\n";
				}
				$outtext .= $thisline . "\n";
				$iLineTeller++;
			}
			if ($stringlengte != 0)
				$sPreviousLine = $thisline;
		}
	 
		$outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\é\è\ç\ë\à\'\:\t@\/\_\(\)]/", "", $outtext);
	 
		return $outtext;
	}
	 
	function process_file_ajax($file) {
		/*
		 * Begin set of variables to match.
		 */
		$structure_type = "";
		$var_db_02_beam_material_type = "";
		$var_db_03_beam_num_spans = 0;
		$var_db_04_beam_length = 0;
		$var_db_04b_beam_moment = 0;
		$var_db_05_beam_reaction = 0;
		$var_db_06_beam_width = 0;
		$var_db_07_beam_depth = 0;
		$var_db_08_beam_weigth = 0;
		//beam
			  $var_db_03_beam_num_spans = 0;
		$var_db_04_beam_length = array();;//array
		$var_db_04b_beam_moment = array();
		$var_db_05_beam_reaction = array();
		$var_db_06_beam_width = 0;
		$var_db_07_beam_depth = 0;
		$var_db_08_beam_weigth = 0;
		//pad
		$structure_type=0;
	$pad_number_of_columns=0;
	$pad_allowable_bearing_pressure=0;
	$pad_axial_load_permanent=array();
	$pad_axial_load_varable=array();
	$padLength=0;
	$padWidth=0;
	$padDepth=0;
	$padWeight=0;
	$padCod=0;
	//Reatingf wall
	$structure_type=0;
$retaining_wall_material_type=0;
$retaining_wall_surcharge=0;
$retaining_wall_allowable_bearing_pressure=0;
$retaining_wall_height_water=0;
$retaining_wall_height_soil=0;
$base_thickness=0;
$base_length=0;
$wall_thickness=0;
$retaining_wall_code_of_practice=0;

		$var_db_09_calc_sigmav = "";
		$var_db_10_calc_sigmah = "";
		$var_db_11_calc_num_reactions = 0;
		$var_db_12_material_name = "";
		$var_db_13_mod_elasticity = 0;
		$var_db_14_area = 0;
		$var_db_15_volume = 0;
		$var_db_16_density = 0;
		$var_db_17_weight = 0;
		$var_db_18_compression_strength = 0;
		$var_db_19_strength_class = "";
		$var_db_20_material2_name = "";
		$var_db_21_area_r = 0;
		$var_db_22_area_s = 0;
		$var_db_23_area_sr = 0;
		$var_db_24_area_material_2 = 0;
		$var_db_25_volume_material_2 = 0;
		$var_db_26_density_material_2 = 0;
		$var_db_27_weight_material_2 = 0;
		$var_db_28_yield_strength_material_2 = 0;
		$var_db_29_ratio = 0;
		$var06_sigmav_dead = "";
		$var07_sigmav_live = "";
		
		/*
		 * End set of variables to match.
		 */
	 
		$plainTextDocExtra = msWord2Text($file);
		$plainTextDoc = rtf2text($file);
	  //echo $plainTextDoc;
		echo "got here";//Tedds calcualtion version not in retaining walls $plain text doc???"?
		//AC1027
		
		if (strpos($plainTextDoc, 'Tedds calculation version') !== false ||strpos($plainTextDoc, 'TEDDS calculation version') !== false||strpos($plainTextDoc, 'New Member Unique Data') !== false ||strpos($plainTextDoc, 'TEDDS calculation version') !== false || 
	strpos($plainTextDoc, 'Retaining walls') !== false )  {//written differnetly for retaining wall
			//echo 'Number of lines: ' . substr_count($plainTextDoc, "\n") . '<br/>';
			$lines = explode("\n", $plainTextDoc);
			$linesExtra = explode("\n", $plainTextDocExtra);//trying to get it working with master 
	 echo"got here 2";
	 echo $lines;
	 //echo $linesExtra;
			$linesCount = count($lines);
			$linesExtraCount = count($linesExtra);
			$currLineIndex = 0;
			echo $linesCount;
			echo $LinesExtraCount;
			if ($currLineIndex < $linesCount) {
	 echo "got here 3";// gets here in 
				$tempVar02 = "";
				while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'RC ') == false)) {
					$currLineIndex++;
				}
				if ($currLineIndex < $linesCount) {
					while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'TEDDS calculation version') == false)) {
						if (strpos($lines[$currLineIndex], 'TEDDS calculation version') == false) {
							$tempVar02 = $tempVar02 . $lines[$currLineIndex];
						}
						$currLineIndex++;
					}
					
					if ($currLineIndex < $linesCount) {
						$var_db_01_file_type = $lines[$currLineIndex];
					}
				}
				$tempVar02a = substr($tempVar02, strpos($tempVar02, "RC ", 0));
				$structure_type = substr(substr($tempVar02a, 0, strpos($tempVar02a, "analysis", 0)), 2);
				$test="DDF";
				$test=$structure_type;
				echo $structure_type;
				$currLineIndex=0;
				//choseing what code to run beam pad wall etc
			
				$currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'foundation') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'foundation') == true){
				echo"its working";
				 $decider=2;//why does it only work up here????
				 }
				 echo "this the desider <br>";
				 echo $decider;
				 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Stem type') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				
				if(strpos($lines[$currLineIndex], 'Stem type') == true){
				echo"its working";
				 $decider=3;//why does it only work up here????
				 }
				 echo "this the desider <br>";
				 echo "this the desider <br>";
				 echo $decider;
				 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Depth of cover') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				
				if(strpos($lines[$currLineIndex], 'Depth of cover') == true){
				echo"its working";
				 $decider=9;//why does it only work up here????
				 }
				 echo "this the desider <br>";
				///start here 10/09/2013
				//for a twoway slab
				
				 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Outer sagging steel') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				
				if(strpos($lines[$currLineIndex], 'Outer sagging steel') == true){
				echo"its working";
				 $decider=4;//why does it only work up here????//for the one way slab
				 }
				 //one way slab
				 echo "as";
			
				
				  $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Span of slab in x') == false)&&($currLineIndex < $linesCount))  {//come back to this need to get differance between one way and two way
						$currLineIndex++;
				}
				
				if(strpos($lines[$currLineIndex], 'Span of slab in x') == true){
				echo"its working";
				 $decider=6;//why does it only work up here????
				 }
				  
				  $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Column width') == false)&&($currLineIndex < $linesCount))  {//come back to this need to get differance between one way and two way
						$currLineIndex++;//do this tomoro for a mason column ec 01/10/2013
				}
				echo $currLineIndex;
				echo $linesCount;
				if(strpos($lines[$currLineIndex], 'Column width') == true){//might not w
				echo"Mason column bc";//need o change this for the other columns
				 $decider=7;//why does it only work up here????
				 echo "inside";
				 }
				   $currLineIndex=0;
				 while((strpos($lines[$currLineIndex], 'Width of column') == false)&&($currLineIndex < $linesCount))  {//come back to this need to get differance between one way and two way
						$currLineIndex++;//for ec
				}
				echo $currLineIndex;
				echo $linesCount;//start here tomoro
				if(strpos($lines[$currLineIndex], 'Width of column') == true){//might not w
				echo"Mason column ec";//need o change this for the other columns
				 $decider=10;//why does it only work up here????
				 echo "inside";
				 }
				   $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Column section') == false)&&($currLineIndex < $linesCount))  {//come back to this need to get differance between one way and two way
						$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesCount;
				if(strpos($lines[$currLineIndex], 'Column section') == true){//might not w
				echo"steel column";//need o change this for the other columns
				 $decider=8;//why does it only work up here????
				 echo "inside";
				 }
				 $currLineIndex=0;//delect one at top later
				while((strpos($lines[$currLineIndex], 'foundation') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'foundation') == true){
				echo"its working";
				 $decider=2;//why does it only work up here????
				 }
				  $currLineIndex=0;//delect one at top later
				while((strpos($lines[$currLineIndex], 'Length of pad') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Length of pad') == true){
				echo"Pad foundation bs";
				 $decider=11;//why does it only work up here????
				 }
				 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Section type') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Section type') == true){
				echo "hanged";
				 $decider=12;
				 }
				  $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Design moment') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Design moment') == true){
				echo "its in here";
				 $decider=13;
				 }
				   $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Number of piles') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Number of piles') == true){
				echo "its in here";
				 $decider=14;
				 }
				   $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Joist breadth') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Joist breadth') == true){
				echo "its in here";
				 $decider=15;
				 }
				
				   $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Depth to tension steel') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Depth to tension steel') == true){//Might need to change this//wont scan brakets??
				echo "its in here";
					 $decider=16;
				 }
					$currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Number of piles') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Number of piles') == true){
				echo "its in here";
				 $decider=14;
				 }
					$currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Length of shorter side of slab') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Length of shorter side of slab') == true){//Might need to change this//wont scan brakets??
				echo "its in here";
					 $decider=17;
				 }
					 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Category of manufacturing ') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Category of manufacturing ') == true){//Might need to change this//wont scan brakets??
				echo "Category of manufacturing ";
					 $decider=18;
				 }
					  $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Stud breadth') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex], 'Stud breadth') == true){//Might need to change this//wont scan brakets??
				echo "Stud design timber ";
					 $decider=19;
				 }
					 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Panel height') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				
				if(strpos($lines[$currLineIndex], 'Panel height') == true){//need to edit this
				echo"its working";
				 $decider=20;//why does it only work up here????//for the one way slab
				 }
				 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'E factor') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				
				if(strpos($lines[$currLineIndex], 'E factor') == true){//may have to change
				echo"its working";
				 $decider=20;//why does it only work up here????//for the one way slab
				 }
				  $currLineIndex=0;
				 while((strpos($lines[$currLineIndex], 'cover to top') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				if(strpos($lines[$currLineIndex],'cover to top') == true){// IF BECOMES PROBLEM MAKE ONE FOR BEAM THEN MAKE SUB SEARHERS FOR MATERIAL MAYBE?
				echo "its in here";
				 $decider=1;
				 }
				 $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Stem type') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}
				
				if(strpos($lines[$currLineIndex], 'Stem type') == true){
				echo"its working";
				 $decider=3;//why does it only work up here????
				 }//NB change tomoro make sure it reads into this one for ec retaing wall and bs for the other one
				   $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'First support') == false)&&($currLineIndex < $linesCount))  {//come back to this need to get differance between one way and two way
						$currLineIndex++;
				}//this is for one way
				
				if(strpos($lines[$currLineIndex], 'First support') == true){
				echo"its working";
				 $decider=5;//why does it only work up here????
				 }
				  $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Outer sagging steel') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}//for two way spanni
				
				if(strpos($lines[$currLineIndex], 'Outer sagging steel') == true){
				echo"its working";//for the two 
				 $decider=4;//why does it only work up here????//for the one way slab
				 }
				  $currLineIndex=0;
				while((strpos($lines[$currLineIndex], 'Type of slab') == false)&&($currLineIndex < $linesCount))  {
						$currLineIndex++;
				}//for two way spanni
				
				if(strpos($lines[$currLineIndex], 'Type of slab') == true){
				echo"its working";//for the two 
				 $decider=25;//why does it only work up here????//for the one way slab//ec version
				 }
				 echo "this the desider <br>";
			 echo $decider;
				// echo "here we are";
				 if($decider=="1"){
				echo "Material type=Concrete <br>
				Structural type= beam<br>";
				 $var_db_01_structure_type = "Beam";
				 $var_db_02_beam_material_type = "Concrete";
				while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Support conditions') == false)) {
					$currLineIndex++;
				}
				//this is a comment
				if ($currLineIndex < $linesCount) {
				
					while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Maximum') == false)) {
						$currLineIndex++;
						
						if ((strpos($lines[$currLineIndex], 'Support A') == true) ||
								(strpos($lines[$currLineIndex], 'Support B') == true) ||
								(strpos($lines[$currLineIndex], 'Support C') == true) ||
								(strpos($lines[$currLineIndex], 'Support D') == true) ||
								(strpos($lines[$currLineIndex], 'Support E') == true) ||
								(strpos($lines[$currLineIndex], 'Support F') == true)) {
							$var_db_11_calc_num_reactions++;
							
							}
						
					}
				}
				//beam
				  $var_db_03_beam_num_spans = 0;
		$var_db_04_beam_length = array();;//array
		$var_db_04b_beam_moment = array();
		$var_db_05_beam_reaction = array();
		$var_db_06_beam_width = 0;
		$var_db_07_beam_depth = 0;
		$var_db_08_beam_weigth = 0;
	
		$structure_type=0;
		//pad
	$pad_number_of_columns=0;
	$pad_allowable_bearing_pressure=0;
	$pad_axial_load_permanent=array();
	$pad_axial_load_varable=array();
	$padLength=0;
	$padWidth=0;
	$padDepth=0;
	$padWeight=0;
	$padCod=0;
	//column
	$columnMaterialType=0;
	$columnLength=0;
	$column_axial_load_permanant=0;
	$column_axial_load_varable=0;
	$columnWidth=0;
	$columnDepth=0;
	$columnWeight=0;
	$column_cod=0;
	//retaining wall
	 $retaining_wall_material_type=0;
             $retaining_wall_surcharge=0;
           $retaining_wall_allowable_bearing_pressure=0;
       $retaining_wall_height_water=0;
          $retaining_wall_height_soil=0;
     $base_thickness=0;
         $base_length=0;
            $wall_thickness=0;
           $retaining_wall_code_of_practice=0;	
	
				
		//		echo "This the correct number of spans<br>";
				//	echo "noOfSpans";
						//	$noOfSpans=$var_db_11_calc_num_reactions-1;
						//	echo $noOfSpans;
						//	$counter=($var_db_11_calc_num_reactions*2)-1;
							//wont work for an over hang willl give 11 for b and a anyway
				 $currLineIndex = 0;
				$tempWidth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Maximum moment ') == false)) {//try 3 equals
					$currLineIndex++;
					
				}
			//	echo"tester <br>";
				//echo $currLineIndex;
				//echo "<br> be here";
			//	echo $linesExtraCount;
			//	echo "<br>";
				if ($currLineIndex < $linesExtraCount) {
			
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
						$currLineIndex++;
						echo "2";
					}
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					if (strpos( $tempWidth, 'Support Asectd') == true)      //had to write them like this                    
							  {
							$var_db_11_calc_num_reactions++;
							//echo "get in there a";
							
							}
						if(strpos( $tempWidth, 'Support Bsectd') == true){
					$var_db_11_calc_num_reactions++;
						//	echo "get in there b";
					}	
					if(strpos( $tempWidth, 'Support Csectd') == true){
					$var_db_11_calc_num_reactions++;
						//	echo "get in there c";
					}
					if(strpos( $tempWidth, 'Support Dsectd') == true){
					$var_db_11_calc_num_reactions++;
						//	echo "get in there d";
					}
					if(strpos( $tempWidth, 'Support Esectd') == true){
					$var_db_11_calc_num_reactions++;
						//	echo "get in there e";
					}
					if(strpos( $tempWidth, 'Support Fsectd') == true){
					$var_db_11_calc_num_reactions++;
						//	echo "get in there f";
					}
				}
				   echo "<br>No of Reactions =";
							echo $var_db_11_calc_num_reactions;
							echo "<br>noOfSpans =";
							$noOfSpans=$var_db_11_calc_num_reactions-1;
							echo $noOfSpans;
							  $var_db_03_beam_num_spans = $noOfSpans;
							$counter=($var_db_11_calc_num_reactions*2)-1;
				//FOR THE MOMENTS MAX
				echo"tester <br>";
				
				echo "<br>";
				
				echo "<br>";
										
				$pos1=strpos($tempWidth, "Maximum moment", 0);
				$tempWidth = substr($tempWidth, $pos1);
				 $i=1;
		

				for($x=0;$x<$counter;$x++){
				$pos2=strpos($tempWidth, "Maximum moment",$pos1+strlen("Maximum moment"));
				$tempWidth2 = substr($tempWidth, $pos2);
			$pos1=$pos2;
			echo "<br>";
		
				//echo $pos2;
				echo"Moments $i=";
				
				 $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " k", 0)), ' '));
				 echo $var_db_03_width1;
				 //add to array for beam array_unshift($a,"blue");
				// $var_db_04b_beam_moment;
				// array_unshift( $var_db_04b_beam_moment,$var_db_03_width1);
				array_push($var_db_04b_beam_moment, $var_db_03_width1);
				$i=$i+1;
			}
			echo "<br> This is the arrayBeam<br>";
		//	implode ( ‘,’, array_values($var_db_04b_beam_moment));
		print_r($var_db_04b_beam_moment);
		//	echo $var_db_04b_beam_moment;
		//works
			//$pos2=strpos($tempWidth, "Maximum moment",$pos1+strlen("Maximum moment"));
			//	$pos3=strpos($tempWidth, "Maximum moment",$pos2+strlen("Maximum moment"));			
			 //   $tempWidth2 = substr($tempWidth, $pos1);
			 // $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " k", 0)), ' '));
				echo "<br>";			
				
		

	 echo"stop";
			  
				$currLineIndex = 0;//here
				$tempWidth = "hello";
				
				while (($currLineIndex < $linesExtraCount) && (stristr($linesExtra[$currLineIndex], 'reaction at support a') === false)) {//Wont work with a???
					$currLineIndex++;
					
				}
			//	echo $currLineIndex;
			//	echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
				
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], " k") == false)) {
						
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
						$currLineIndex++;
						
					}
					echo "Space";
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					
				}
				$pos1=strpos($tempWidth, "Maximum reaction at support ", 0);
				$tempWidth = substr($tempWidth, $pos1);
				$var_db_03_width1 = intval(strrchr(substr($tempWidth, 0, strpos($tempWidth2, " k", 0)), ' '));
				 echo $var_db_03_width1;
			//	 array_push($var_db_05_beam_reaction, $var_db_03_width1);
				// $var_db_05_beam_reaction
				 $i=1;
				for($x=0;$x<$var_db_11_calc_num_reactions;$x++){
				$pos2=strpos($tempWidth, "Maximum reaction at support ",$pos1+strlen("Maximum reaction at support "));
				$tempWidth2 = substr($tempWidth, $pos2);
				$pos1=$pos2;
				 echo "<br>";
				// echo"flkhggfdf";
			//	echo $pos2;
				echo"Reaction $i=";
				 $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " k", 0)), ' '));
				 echo $var_db_03_width1;
				  array_push($var_db_05_beam_reaction, $var_db_03_width1);
				 $i=$i+1;
				echo "<br>";
				}	
					print_r($var_db_05_beam_reaction);
				
					  //     echo $var06_sigmav_dead;
				$currLineIndex = 0;
				while ($currLineIndex < $linesCount) {
					while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Unfactored live load reaction at support') == false)) {
						$currLineIndex++;
					}
					if ($currLineIndex < $linesCount) {
						$currLineIndex++;
						while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Unfactored live load reaction at support') == false) && (strpos($lines[$currLineIndex], 'Rectangular section details') == false)) {
							if ((strpos($lines[$currLineIndex], 'Unfactored live load reaction at support') == false) && (strpos($lines[$currLineIndex], 'Rectangular section details') == false)) {
								$var07_sigmav_live = $var07_sigmav_live . $lines[$currLineIndex];
							}
							$currLineIndex++;
						}
					}
				}
				//in here
				
	 //between here
				$currLineIndex = 0;
				$tempWidth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'ection width') == false)) {
					$currLineIndex++;
					
				}
				
				if ($currLineIndex < $linesExtraCount) {
		   //		echo "1";
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
						$currLineIndex++;
					//	echo "2";
					}
					
				//	echo $currLineIndex;
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					
				}
				
				$tempWidth2 = substr($tempWidth, strpos($tempWidth, "ection width", 0));
				$var_db_03_width = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " mm", 0)), ' '));
	Echo "Width is = ";
	 echo $var_db_03_width;
	 //echo"stop";
			  
				
				

				$currLineIndex = 0;
				$tempDepth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'ection depth') == false)) {
					$currLineIndex++;
				}
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempDepth = $tempDepth . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempDepth = $tempDepth . $linesExtra[$currLineIndex];
				}
				
				$tempDepth2 = substr($tempDepth, strpos($tempDepth, "ection depth", 0));
				$var_db_04_depth = intval(strrchr(substr($tempDepth2, 0, strpos($tempDepth2, " mm", 0)), ' '));
	 echo "Depth is equal to = ";
	 echo $var_db_04_depth;
			   $var_db_07_beam_depth= $var_db_04_depth;
	 // here for d start here on monday
				$currLineIndex = 0;
				$tempDepth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'midspan moment') == false)) {
					$currLineIndex++;
				}
				
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempDepth = $tempDepth . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempDepth = $tempDepth . $linesExtra[$currLineIndex];
				}
			
				$pos1=strpos($tempDepth, "midspan moment",0);
				$pos2 =strpos($tempDepth, " h - csub",0);
						$pos3=strpos($tempDepth, "Lsub s1",0);
				$tempDepth2 = substr($tempDepth, $pos1);//gets 
				$tempDepth3 = substr($tempDepth, $pos2);//gets depth 1
				$tempDepth4=substr($tempDepth, $pos3);//wont read tempdepth 3!!!!
			//	echo $currLineIndex;
			//	echo $linesExtraCount;
			//	echo"Starts here";
			//	echo "here we are <br>";
				$var_db_04_depth123 = intval(strrchr(substr($tempDepth2, 0, strpos($tempDepth2, " mm", 0)), ' '));
				echo "<br>This is the depth to tension reinforcement <br>";
				echo $var_db_04_depth123;//got it///d1
					//below works!	
				//	echo $tempDepth3;
				//echo $tempDepth4;
				$var_db_04_depth = floatval(strrchr(substr($tempDepth4, 0, strpos($tempDepth4, "par pardplain ltrpars62qr li357ri0sl319slmult1", 0)), ' '));
				echo "<br>This is the allowable span/depth<br>";
				
				echo $var_db_04_depth;//go
				$Lenghtone=$var_db_04_depth*$var_db_04_depth123*.001;//round to 3 decimal places its off by .3 of a mm 
				echo "<br> Length 1 = ";
				echo $Lenghtone;
				array_push($var_db_04_beam_length, $Lenghtone);
				//$var_db_04_beam_length;
				$noOfSpans;//start here do loop??
				//getting d2 for the second length//01/09/2013//199
				$i=2;
				echo "<br>";
				echo  $currLineIndex;
				for($x=0;$x<$noOfSpans-1;$x++)
				{
				$tempDepth="";	
			
				$currLineIndex=$currLineIndex+4;//search thing is really buggy
				//echo $currLineIndex;
			//	echo $linesExtraCount;
			
				$tempDepth = $tempDepth . $linesExtra[$currLineIndex];
				$pos1=strpos($tempDepth, "midspan moment",0);
				$pos2 =strpos($tempDepth, " h - csub",0);
						$pos3=strpos($tempDepth, "Lsub s",0);
				$tempDepth2 = substr($tempDepth, $pos1);//gets 
				$tempDepth3 = substr($tempDepth, $pos2);//gets depth 1
				$tempDepth4=substr($tempDepth, $pos3);
				//echo $tempDepth4;
			   // echo "<br> Check 2 <br>"; 
				//echo $tempDepth;
				 $var_db_04_depth123 = intval(strrchr(substr($tempDepth, 0, strpos($tempDepth, " mm", 0)), ' '));
				 echo " This is the depth to reinforcement<br>";
				 echo $var_db_04_depth123;
				 echo "<br>";
				 
				$var_db_04_depth12 = floatval(strrchr(substr($tempDepth4, 0, strpos($tempDepth4, "par pardplain ltrpars62qr li357ri0sl319slmult1", 0)), ' '));//that space makes it work
				echo "<br>This allowable span/depth<br>";
				echo $var_db_04_depth12;
				$Lenght2=$var_db_04_depth12*$var_db_04_depth123*.001;
				echo"<br>Length $i =";
				echo $Lenght2;
				 array_push($var_db_04_beam_length, $Lenght2);
				echo "<br>";
				$i=$i+1;
				}
	 // over here
	 print_r($var_db_04_beam_length);
				$currLineIndex = 0;
				$tempFlangeWidth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'ection depth') == false)) {
					$currLineIndex++;
				}
			   if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempDepth = $tempDepth . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempDepth = $tempDepth . $linesExtra[$currLineIndex];
				}
						  
	 $pos1=strpos($tempDepth, "ection depth",0);
				$pos2 =strpos($tempDepth, "ection width",0);
						
				$tempDepth2 = substr($tempDepth, $pos1);//gets 
				$tempDepth3 = substr($tempDepth, $pos2);//gets depth 1
	 
					 
			   $var_db_04_depth1234 = intval(strrchr(substr($tempDepth2, 0, strpos($tempDepth2, " mm", 0)), ' '));
				 echo "This is the depth <br>";
				 echo $var_db_04_depth1234;
				 echo "<br>";
	  $var_db_04_depth123 = intval(strrchr(substr($tempDepth3, 0, strpos($tempDepth3, " mm", 0)), ' '));
				 echo "This is the Width <br>";
				 echo $var_db_04_depth123;
				 echo "<br>";
				 $var_db_06_beam_width=$var_db_04_depth123;
			  echo "this is the weight in Kn/m";
			$weight=(($var_db_04_depth1234*$var_db_04_depth123)/1000)*24;
			echo $weight;
			$var_beam_weight=$weight;
			echo "<br>";
		//this is the one 
		}
		
		
		///Big break end of concrete beam
		
		
		
		
		
		
		
		if($decider=="12"){
		//why isnt it reading it???//why do I need it up here??
	// echo "got here";
	 
				echo "Material= Steel<br>Sturtural Type=Beam<br>";
				echo "<br>";
				$var_db_02_beam_material_type="Steel";
						  $var_db_03_beam_num_spans = 0;
		//$var_db_04_beam_length = array();;//array
		$MaxMoments=array();
	//	$var_db_04b_beam_moment = array();
	//	$var_db_05_beam_reaction = array();
	$MinMoments=array(); //start here tomoro03/02/2014
		$var_db_06_beam_width = 0;
		$var_db_07_beam_depth = 0;
		$var_db_08_beam_weigth = 0;
	
		$structure_type="Steel Beam";
				//going in here
				while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Support conditions') == false)) {
					$currLineIndex++;
				}
				
				//this is a comment
				if ($currLineIndex < $linesCount) {
				
					while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Maximum') == false)) {
						$currLineIndex++;
						
						if ((strpos($lines[$currLineIndex], 'Support A') == true) ||
								(strpos($lines[$currLineIndex], 'Support B') == true) ||
								(strpos($lines[$currLineIndex], 'Support C') == true) ||
								(strpos($lines[$currLineIndex], 'Support D') == true) ||
								(strpos($lines[$currLineIndex], 'Support E') == true) ||
								(strpos($lines[$currLineIndex], 'Support F') == true)) {
							$var_db_11_calc_num_reactions++;//doesnt work in tedds for some reason
							
							}
						
					}
				}
				
				//	echo "noOfSpans";//gets no of spans as no of reactions minis one 
							$var_db_03_beam_num_spans=$var_db_11_calc_num_reactions-1;
						//	echo "<br>";
						//	echo $noOfSpans;
						//	echo "<br>";//works for bs
							$counter=($var_db_11_calc_num_reactions*2)-1;
							//wont work for an over hang willl give 11 for b and a anyway
				 $currLineIndex = 0;
				$tempWidth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Maximum moment ') == false)) {//try 3 equals
					$currLineIndex++;
					
				}
			//	echo"tester <br>";
			//	echo $currLineIndex;
			//	echo "<br> be here";
			//	echo $linesExtraCount;
			//	echo "<br>";
				if ($currLineIndex < $linesExtraCount) {
			
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
						$currLineIndex++;
						//echo "2";
					}
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					if (strpos( $tempWidth, 'Support Asectd') == true)      //had to write them like this                    
							  {
							$var_db_11_calc_num_reactions++;
							echo "get in there a";
							
							}
						if(strpos( $tempWidth, 'Support Bsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there b";
					}	
					if(strpos( $tempWidth, 'Support Csectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there c";
					}
					if(strpos( $tempWidth, 'Support Dsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there d";
					}
					if(strpos( $tempWidth, 'Support Esectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there e";
					}
					if(strpos( $tempWidth, 'Support Fsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there f";
					}
				}
				   //actually no of spans calculated here
					   //   echo "<br>";
						//	echo $var_db_11_calc_num_reactions;
							echo "noOfSpans<br>";
							$var_db_03_beam_num_spans=$var_db_11_calc_num_reactions-1;
							echo $var_db_03_beam_num_spans;
							$counter=($var_db_11_calc_num_reactions*2)-1;
				//FOR THE MOMENTS MAX
				echo "<br>";
				echo"tester <br>";
				
				echo "<br>";
				
				echo "<br>";
				
									
				$pos1=strpos($tempWidth, "Msub s1_maxsectd", 0);
				
				$tempWidth = substr($tempWidth, $pos1);
				//test
				// a trail with this one 
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));//works need space at start!
				//Redo this tomoro
				echo "Right here";
				  echo $var_db_03_width1;
				  echo "<br>";
				//  echo $pos1;
				  array_push($MaxMoments, $var_db_03_width1);
						  echo $MaxMoments;
			echo "Getting maximun moments <br>";
				for($x=0;$x<$counter;$x++){
				
				$pos2=strpos($tempWidth, " Msub s{$x}_maxsectd",$pos1+strlen("Msub s1_maxsectd"));//come back here maybe do them sperately??
				//in the steel makes it go up by one each time
				//works !
				//does the frist one twice come back to this
				//echo $pos2;
				echo "<br>";
				
				echo "Moments max";
				$tempWidth2 = substr($tempWidth, $pos2);
				
			$pos1=$pos2;
			
			echo "<br>";
		//	echo $pos2;
				echo"this one of them";
				 $var_db_03_width1 = floatval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));
				  echo $var_db_03_width1;
				  echo "<br>";
				  array_push($MaxMoments, $var_db_03_width1);
						}
						echo $MaxMoments;
						$pos1=strpos($tempWidth, "Msub s1_minsectd", 0);
				
				$tempWidth = substr($tempWidth, $pos1);
				
				//test
				// a trail with this one 
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));//works need space at start!
				//Redo this tomoro
				echo "Right here";
				  echo $var_db_03_width1;
				  echo "<br>";
			//	  echo $pos1;
				  echo "getting min moments";
						for($x=0;$x<$counter;$x++){
				
				$pos2=strpos($tempWidth, " Msub s{$x}_minsectd",$pos1+strlen("Msub s1_minsectd"));//come back here maybe do them sperately??
				//in the steel makes it go up by one each time
				//works !
				//does the frist one twice come back to this
			//	echo $pos2;
				echo "<br>";
				
				echo "Min moments";
				$tempWidth2 = substr($tempWidth, $pos2);
				
			$pos1=$pos2;
			
			echo "<br>";
		//	echo $pos2;
				echo"this one of the mins them";
				 $var_db_03_width1 = floatval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));
				  echo $var_db_03_width1;
				  echo "<br>";
				  	  array_push($MinMoments, $var_db_03_width1);
						}
						echo $MinMoments;
		
			//$pos2=strpos($tempWidth, "Maximum moment",$pos1+strlen("Maximum moment"));
			//	$pos3=strpos($tempWidth, "Maximum moment",$pos2+strlen("Maximum moment"));
				
			 //   $tempWidth2 = substr($tempWidth, $pos1);
				
				
			   // $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " k", 0)), ' '));
				echo "<br>";
								 
				$currLineIndex = 0;//here
				$tempWidth = "hello";
				
				while (($currLineIndex < $linesExtraCount) && (stristr($linesExtra[$currLineIndex], 'reaction at support a') === false)) {//Wont work with a???
					$currLineIndex++;
					
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
				
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], " k") == false)) {
						
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];//this varables used because of the arry bug
						$currLineIndex++;
						echo "in here 2";
					}
					echo "Space";
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					
				}
			
				$pos1=strpos($tempWidth, " Maximum reaction at support Asectd", 0);
				$tempWidth = substr($tempWidth, $pos1);
				
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 k", 0)), ' '));
				echo "frist reaction";
				 echo $var_db_03_width1;
					for($x=0;$x<$var_db_11_calc_num_reactions;$x++){
				$pos2=strpos($tempWidth, "Maximum reaction at support ",$pos1+strlen("Maximum reaction at support "));
				$tempWidth2 = substr($tempWidth, $pos2);
				$pos1=$pos2;
				 echo "<br>";
				//echo $pos2;
				echo"Reaction";
				 $var_db_03_width1 = floatval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 k", 0)), ' '));
				 echo $var_db_03_width1;
				
				
				}
				  $currLineIndex = 0;
					$tempWidth = "";
				//getting lengths??? try thid
			
				echo"right here it us";
				
			
			
					   
				//be hard to get the others since It wont read A there values are in temp width 2
				echo " pause ";
								
			   
			   
				//in here

							
						 
	 // here for d start here on monday
			   //may have delected some thing imporant here          
		
	 }elseif($decider=="13")//how comes has to be up here??
	 {
	echo "Material= timber 
	Structural type=beam ec and bs code included";
	 
				echo "its a beam";
				echo "<br>";
				//going in here
				while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Load combinations') == false)) {
					$currLineIndex++;
				}
				
				//this is a comment
				if ($currLineIndex < $linesCount) {
				
					while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Support') == false)) {
						$currLineIndex++;
						
						if ((strpos($lines[$currLineIndex], 'Support A') == true) ||
								(strpos($lines[$currLineIndex], 'Support B') == true) ||
								(strpos($lines[$currLineIndex], 'Support C') == true) ||
								(strpos($lines[$currLineIndex], 'Support D') == true) ||
								(strpos($lines[$currLineIndex], 'Support E') == true) ||
								(strpos($lines[$currLineIndex], 'Support F') == true)) {
							$var_db_11_calc_num_reactions++;//doesnt work in tedds for some reason
							
							}
						
					}
				}
				
				//	echo "noOfSpans";//gets no of spans as no of reactions minis one 
				//			$noOfSpans=$var_db_11_calc_num_reactions-1;
					///		echo "<br>";
					//		echo $noOfSpans;
					//		echo "<br>";//works for bs
							$counter=($var_db_11_calc_num_reactions*2)-1;
							//wont work for an over hang willl give 11 for b and a anyway
				 $currLineIndex = 0;
				$tempWidth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'combinations') == false)) {//try 3 equals
					$currLineIndex++;
					
				}
			//	echo"tester <br>";
				//echo $currLineIndex;
			//	echo "<br> be here";
			//	echo $linesExtraCount;
			//	echo "<br>";
				if ($currLineIndex < $linesExtraCount) {
			
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
						$currLineIndex++;
						//echo "2";
					}
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					if (strpos( $tempWidth, 'Support Asectd') == true)      //had to write them like this                    
							  {
							$var_db_11_calc_num_reactions++;
							echo "get in there a";
							
							}
						if(strpos( $tempWidth, 'Support Bsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there b";
					}	
					if(strpos( $tempWidth, 'Support Csectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there c";
					}
					if(strpos( $tempWidth, 'Support Dsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there d";
					}
					if(strpos( $tempWidth, 'Support Esectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there e";
					}
					if(strpos( $tempWidth, 'Support Fsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there f";
					}
				}//works now
				   //actually no of spans calculated here
				   echo "<br>";
					//		echo $var_db_11_calc_num_reactions;
							echo "noOfSpans";
							$noOfSpans=$var_db_11_calc_num_reactions-1;
							echo $noOfSpans;
							$counter=($var_db_11_calc_num_reactions*2)-1;
				//FOR THE MOMENTS MAX
		//		echo "<br>";
		//		echo"tester <br>";
				
			//	echo "<br>";
				
				echo "<br>";
										
				$pos1=strpos($tempWidth, "Maximum moment", 0);
				$tempWidth = substr($tempWidth, $pos1);
			//do a trail with this one 
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " k", 0)), ' '));
				//Redo this tomoro
				echo "Maximun moment";
				  echo $var_db_03_width1;
				 // echo $tempWidth;
		$pos1=strpos($tempWidth, "min", 0);
				$tempWidth = substr($tempWidth, $pos1);
			//do a trail with this one 
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " kNmpar", 0)), ' '));
				//Redo this tomoro
				echo "<br> Min Moment<br>";
				echo $var_db_03_width1 ;
				echo "<br>Min moment";
			
						 while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'reactions at support') == false)) {
						$currLineIndex++;
					}
					if ($currLineIndex < $linesCount) {
						while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'kNtab') == false)) {
							$tempWidth = $tempWidth . $lines[$currLineIndex];
							$currLineIndex++;
						}
						$tempWidth = $tempWidth . $lines[$currLineIndex];
						echo "<br> fom the reactio code tester123";
								 
								 }	
			  $pos1=strpos($tempWidth, "reaction at support ", 0);
			  
				$tempWidth = substr($tempWidth, $pos1);
			//	echo $tempWidth;
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth2, " kNtab", 0)), ' '));
				 echo $var_db_03_width1;
				for($x=0;$x<$var_db_11_calc_num_reactions;$x++){
				$pos2=strpos($tempWidth, "reaction at support ",$pos1+strlen("reaction at support "));
				$tempWidth2 = substr($tempWidth, $pos2);
				$pos1=$pos2;
				 echo "<br>";
			//	echo $pos2;
				echo"Reaction";
				 $var_db_03_width1 = floatval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " kNtab", 0)), ' '));
				 echo $var_db_03_width1;
				}
			//$pos2=strpos($tempWidth, "Maximum moment",$pos1+strlen("Maximum moment"));
			//	$pos3=strpos($tempWidth, "Maximum moment",$pos2+strlen("Maximum moment"));
				
			 //   $tempWidth2 = substr($tempWidth, $pos1);
				
				
			   // $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " k", 0)), ' '));
				echo "<br>";
					
			
	//Geting reactions//lot of cleaning up to do in this code
				$currLineIndex = 0;//here
				$tempWidth = "hello";
				
				while (($currLineIndex < $linesExtraCount) && (stristr($linesExtra[$currLineIndex], 'timber sections') === false)) {//Wont work with a???
					$currLineIndex++;
					
				}
				echo $currLineIndex;
				echo "<br>";
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
				
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], " k") == false)) {
						
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];//this varables used because of the arry bug
						$currLineIndex++;
						//echo "in here 2";
					}
					echo "Space";
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					
				}
			//	 echo "getting breaths of timber and dempth";
				$pos1=strpos($tempWidth, "timber sections", 0);
			
				$tempWidth = substr($tempWidth, $pos1);
			
				$var_db_03_width1 = intval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " mmpar", 0)), ' '));
				echo "breath of timber <br> ";
				 echo $var_db_03_width1;
				 
				 $currLineIndex = 0;//here
				$tempWidth = "hello";
				
				while (($currLineIndex < $linesExtraCount) && (stristr($linesExtra[$currLineIndex], 'Depth of timber sections') === false)) {//Do seperate doesnt work when thee together
					$currLineIndex++;
					
				}
				//echo $currLineIndex;
				echo "<br>";
			//	echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
				
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], " m") == false)) {
						
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];//this varables used because of the arry bug
						$currLineIndex++;
						echo "in here 2";
					}
					echo "Space";
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					
				}
				 echo "getting breaths of timber and depth";
				$pos1=strpos($tempWidth, "Depth of timber sections", 0);
			
				$tempWidth = substr($tempWidth, $pos1);
			
				$var_db_03_width1 = intval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " mmpar", 0)), ' '));
				 echo "Depth of timber  ";
				 echo $var_db_03_width1;//working got em both
				 echo "this is the weight<br>";
				 $currLineIndex = 0;//here
				$tempWidth = "hello";//THIS IS FOR BS CODE TESTING Will need to edit later
				echo "<br> testing bs code" ;
				while (($currLineIndex < $linesExtraCount) && (stristr($linesExtra[$currLineIndex], 'Breadth of sections') === false)) {//Do seperate doesnt work when thee together
					$currLineIndex++;
					
				}
				echo "checker <br>";
			//	echo $currLineIndex;
				echo "<br>";
			//	echo $linesExtraCount;
				if ($currLineIndex < $linesCount){
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], " m") == false)) {
						
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];//this varables used because of the arry bug
						$currLineIndex++;
						echo "in here 2";
					}
					   $tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					   }
				//	echo $tempWidth;
				 echo "getting breaths of timber and depth";
				$pos1=strpos($tempWidth, "Breadth of sections", 0);
				$tempWidth = substr($tempWidth, $pos1);
				$var_db_03_width1 = intval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " mm", 0)), ' '));
				echo "Breath of timber  bs <br> ";
				 echo $var_db_03_width1;//working got em both
				 $currLineIndex = 0;//here
				$tempWidth = "hello";
				 $currLineIndex = 0;//here
				$tempWidth = "hello";//THIS IS FOR BS CODE TESTING Will need to edit later
				echo "<br> testing bs code" ;
				while (($currLineIndex < $linesExtraCount) && (stristr($linesExtra[$currLineIndex], 'Depth of sections') === false)) {//Do seperate doesnt work when thee together
					$currLineIndex++;
					
				}
				
				if ($currLineIndex < $linesCount){
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], " m") == false)) {
						
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];//this varables used because of the arry bug
						$currLineIndex++;
					
					}
					   $tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					   }
				//	echo $tempWidth;
				 echo "<br>getting Depth of the bs is <br>";
				$pos1=strpos($tempWidth, "Depth of sections", 0);
				$tempWidth = substr($tempWidth, $pos1);
				$var_db_03_width1 = intval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " mm", 0)), ' '));
				echo "<br>Depth of timber  bs <br> ";
				 echo $var_db_03_width1;//working got em both
												  
		//this is the one 
		}
		
		
		///PAD!!!!
		
		
		

		elseif($decider=="2"){//change this
		
	 echo "Structural type= pad ec";
			   echo "right here";
			  $structure_type='pad';
			  $padCod='ec';
				 //here it is for the next discision makeing thing//make true look at temp sting when you come back
				 $currLineIndex = 0;
				$tempLength = "";
			$noOfColumns=1;
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Length of foundationtab') == false)) {
					$currLineIndex++;
				}
			//	echo $currLineIndex;
			//	echo $linesExtraCount;
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				echo "<br>";
				
				$pos1=strpos($tempLength, "Length of foundationtab", 0);
				$pos2=strpos($tempLength, "Width of foundationtab",$pos1+strlen("Length of foundationtab"));//kept on reading lenght of foundation so did this
				$pos3=strpos($tempLength, "Depth of foundationtab",$pos2+strlen("Width of foundationtab"));
						
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
				$tempLength4 = substr($tempLength, $pos3);
				
		
				
			   $padLength = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo"length of foundation <br>";
				echo $padLength;
				 $padWidth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo"Width of foundation <br>"	;		
			   echo $padWidth;
				$padDepth = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " mm", 0)), ' '));
				echo"Depth of foundation <br>"	;		
			   echo $padDepth;
			 $padWeight= ((($padLength*$padWidth*$padDepth)/1000000000)*2400)/9.81;
			   echo "Weight is in Kn";
			   echo $padWeight;
				//Allowable bearing pressure of foundation
			  $currLineIndex = 0;
				$tempLength = "";
				$noOfColumns=2;
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Permanent load') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'K') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				echo "<br>";
				$pos1=strpos($tempLength, "Permanent load", 0);
				$pos2=strpos($tempLength, "Permanent load", $pos1+strlen("Permanent load"));
				$pos3=strpos($tempLength, "Permanent load", $pos2+strlen("Permanent load"));
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
				$tempLength4 = substr($tempLength, $pos3);
				
				
				
				$var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " k", 0)), ' '));
				echo "Permanebt loads";
				
				echo $var_db_04_depth;
				array_push($pad_axial_load_permanent,$var_db_04_depth);
				echo "<br>";
				$var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " k", 0)), ' '));
				echo $var_db_04_depth;
				echo "<br>";
				array_push($pad_axial_load_permanent,$var_db_04_depth);
				$var_db_04_depth = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " k", 0)), ' '));
				echo $var_db_04_depth;
				array_push($pad_axial_load_permanent,$var_db_04_depth);
				echo "<br>";
				print_r($pad_axial_load_permanent);
				echo"<br>;";
				$pos1=strpos($tempLength, "Variable load", 0);
				$pos2=strpos($tempLength, "Variable load", $pos1+strlen("Variable load"));
				$pos3=strpos($tempLength, "Variable load", $pos2+strlen("Variable load"));
				
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
				$tempLength4 = substr($tempLength, $pos3);
				$pos4=strpos($tempLength4, "Net ultimate bearing capacityrtlchfcs1",0);
				$tempLength5=substr ($tempLength4, $pos4);
				
				$var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " k", 0)), ' '));
				echo "Permanebt loads";
				echo $var_db_04_depth;
				echo "<br>";
				array_push($pad_axial_load_varable,$var_db_04_depth);
				$var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " k", 0)), ' '));
				echo $var_db_04_depth;
				echo "<br>";
				array_push($pad_axial_load_varable,$var_db_04_depth);
				$var_db_04_depth = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " k", 0)), ' '));
				echo $var_db_04_depth;
				array_push($pad_axial_load_varable,$var_db_04_depth);
				echo "<br>";
				$var_db_04_depth = floatval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " kN/mrtlchfcs1", 0)), ' '));
				echo "bearing pressure";
				echo "<br>";
			 $var_db_04_depth=$pad_allowable_bearing_pressure;
				echo "<br>";
				
				//Pad ends here
				$currLineIndex = 0;
				$tempLength = "";
			
				 while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Length of column') == false)) {
					$currLineIndex++;
				}
				
				
				echo "<br>";
				 if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			   
				$pos4=strpos($tempLength, "Length of column",0);//trying this to detect column
				$pos5=strpos($tempLength, "Length of column",$pos4+strlen("Length of column"));//trying this to detect extra coloumn
				
			if ($pos5 === false) {
		echo "The string '' was not found in the string";
		$noOfColumns=1;
	} else {
		echo "The string  was found in the string ";
		echo " and exists at position $pos5";
	}			
				echo "No of columns";
				echo $noOfColumns;//Works Finally!!!!!
				$pad_number_of_columns=$noOfColumns;
				//could alter this is more coloumns are needed to be accounted for theory is soon 
		
	 }
	 
	 
	 //Retaining wall
	 
	 
	 
	 
	 
	 
	 
	 
	 elseif($decider =="3"){
	 echo "its a Retaining wall ec";
	 	$retaining_wall_material_type="Concrete;";
             $retaining_wall_surcharge=0;
           $retaining_wall_allowable_bearing_pressure=0;
       $retaining_wall_height_water=0;
          $retaining_wall_height_soil=0;
     $base_thickness=0;
         $base_length=0;
            $wall_thickness=0;
           $retaining_wall_code_of_practice="ec";	
			
				$currLineIndex = 0;
				$tempLength = "";
				
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Retaining wall details') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				echo "<br>";
				$pos1=strpos($tempLength, "Stem thickness", 0);
				$pos2=strpos($tempLength, "Base thickness",0);
				$pos3=strpos($tempLength, "Height of retained soil",0);
				$pos4=strpos($tempLength, "Height of water",0);//kept on reading lenght of foundation so did this
				   $tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
					$tempLength4 = substr($tempLength, $pos3);			
				$tempLength5=substr($tempLength, $pos4);
			   $wall_thickness = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
			   echo "wall Thickness <br>";
				echo $wall_thickness;
				  $base_thickness = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo"Base thickness <br>"	;		
			   echo  $base_thickness;
				$retaining_wall_height_soil = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " mm", 0)), ' '));
				echo"Height of soil <br>"	;		
			   echo $retaining_wall_height_soil;
						echo"Height of water <br>"	;		
			  $retaining_wall_height_water=intval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " mm", 0)), ' '));
				  echo$retaining_wall_height_water;
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Calculate retaining wall') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				$pos1=strpos($tempLength, "Base lengthsectd", 0);
				 $pos2=strpos($tempLength, "Moist soil", 0);
				$pos3=strpos($tempLength, "Variable surcharge loadsectd ", 0);
				$tempLength2 = substr($tempLength, $pos1);
						$tempLength3 = substr($tempLength, $pos2);
						
				$tempLength4= substr($tempLength, $pos3);//here
				
				$base_length = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "Base Length <br>";
				echo $base_length;
				$var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo "Moist soil height <br>";
				echo $var_db_04_depth;
				
				$retaining_wall_surcharge = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " k", 0)), ' '));
				echo "Surcharge <br>";
				echo $retaining_wall_surcharge;
				
				
	 }elseif($decider=="4"){
	 echo "this is for the two way slab bs";
	 //get lx and ly load
		$currLineIndex = 0;
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Maximum Design Moments') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos1=strpos($tempLength, "	 Length of shorter side of slabtab", 0);
				 $pos2=strpos($tempLength, "Length of longer side of slabtab", 0);
				$pos3=strpos($tempLength, "Design ultimate load per unit areatab", 0);
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4= substr($tempLength, $pos3);//here
				
				$var_db_04_depth = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " m", 0)), ' '));
				echo "Short side <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " m", 0)), ' '));
				echo "Long side <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " k", 0)), ' '));//dont foget to put in space before k
				echo "Load in kn/m2 <br>";
				echo $var_db_04_depth;
				// Length of shorter side of slabtab
				  //Length of longer side of slabtab
				  //Design ultimate load per unit areatab
	 }elseif($decider=="25"){
	 echo "this is for the two way slab ec";
	 //get lx and ly load
		$currLineIndex = 0;
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'slab depth') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos1=strpos($tempLength, "slab depth", 0);
				 $pos2=strpos($tempLength, "span of panel", 0);
				$pos3=strpos($tempLength, "span of panel", $pos2+strlen("span of panel"));
				   $pos4=strpos($tempLength, "Design ultimate load",0);
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4= substr($tempLength, $pos3);//here
				 $tempLength5= substr($tempLength, $pos4);//here
				$var_db_04_depth = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " m", 0)), ' '));
				echo "slab depth <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " m", 0)), ' '));
				echo "short side <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " m", 0)), ' '));//dont foget to put in space before k
				echo "long side <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " kN", 0)), ' '));//dont foget to put in space before k
				echo "Design ultimate load<br>";
				echo $var_db_04_depth;
				// Length of shorter side of slabtab
				  //Length of longer side of slabtab
				  //Design ultimate load per unit areatab
	 }elseif($decider=="5")
	 {
	 echo "one way slab ec";

	 //get lx and ly load
		$currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Overall slab depth') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos4=strpos($tempLength, "Overall slab depth", 0);
				$pos1=strpos($tempLength, "Number of spans", 0);
				 $pos2=strpos($tempLength, "Characteristic", 0);
				$pos3=strpos($tempLength, "Characteristic", $pos2+strlen("Characteristic"));
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4= substr($tempLength, $pos3);//here
				$tempLength5= substr($tempLength, $pos4);//here
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, "par First", 0)), ' '));
				echo "Number of spans   <br>";
				echo $No;//it works
				$var_db_04_depth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " kN", 0)), ' '));
				echo "Permenant action <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " kN", 0)), ' '));//dont foget to put in space before k
				echo "varable action <br>";
				echo $var_db_04_depth;
					$var_db_04_depth = Floatval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " mm", 0)), ' '));//dont foget to put in space before k
				echo "Slab depth <br>";
				echo $var_db_04_depth;
				$pos1=strpos($tempLength, "Length of span", 0);
				$tempWidth2 = substr($tempLength, $pos1);
				$var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " m", 0)), ' '));
				echo "Length";
				 echo $var_db_03_width1;
				for($i=0;$i<$No;$i++)
				{
				$pos2=strpos($tempLength, "Length of span",$pos1+strlen("Length of span"));
				$tempWidth2 = substr($tempLength, $pos2);
				$pos1=$pos2;
				 echo "Length <br>";
			//one way slab ec working
				echo"br";
				 $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " m", 0)), ' '));
				 echo $var_db_03_width1;
				}//reads on over 
				// Length of shorter side of slabtab
				  //Length of longer side of slabtab
				  //Design ultimate load per unit areatab
	 }//one way here
	 elseif($decider=="6"){
	 echo "flat slab its in here";
	 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Span of slab') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos1=strpos($tempLength, "Span of slab", 0);
				 $pos2=strpos($tempLength," Span of slab in y-directiontab ", 0);
				
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				
				
				$var_db_04_depth = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " m", 0)), ' '));
				echo "X  kn/m2 <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " m", 0)), ' '));
				echo "Y <br>";
				echo $var_db_04_depth;
				  while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Total ultimate load') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				 if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			$pos1=strpos($tempLength, " fieldfldlockfldinst CSCf3fldrsltfs18bf1cf1", 0);
			$tempLength2 = substr($tempLength, $pos1);
			$var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " k", 0)), ' '));
				echo "UDL <br>";
				echo $var_db_04_depth;
	 }elseif($decider=="7"){
	 echo "Mason column bs";
	 	$structure_type="column";
	 $columnMaterialType="Mason";;
	$columnLength=0;
	$column_axial_load_permanant=0;
	$column_axial_load_varable="N/A";
	$columnWidth="n/a";
	$columnDepth="n/a";
	$columnWeight="N/A";
	$column_cod="bs";
	 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Column width') == false)) {
					$currLineIndex++;
				}
			
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos1=strpos($tempLength, "widthtab ", 0);
				 $pos2=strpos($tempLength,"thicknesstab ", 0);
				  $pos3=strpos($tempLength,"heighttab ", 0);
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4 = substr($tempLength, $pos3);	
				
				
				$columnWidth = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " m", 0)), ' '));
				echo "width mm <br>";
				
				echo $columnWidth;
				$columnDepth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " m", 0)), ' '));
				echo "thickness mm <br>";
				echo $columnDepth;
				
				$columnLength = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " m", 0)), ' '));
				echo "Heigth mm <br>";
			 echo $columnLength;
						  while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Total ultimate load') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				 if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			$pos1=strpos($tempLength, " fieldfldlockfldinst CSCf3fldrsltfs18bf1cf1", 0);
			$tempLength2 = substr($tempLength, $pos1);
			$column_axial_load_permanant = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " k", 0)), ' '));
				echo "UDL <br>";
				
				echo $column_axial_load_permanant;
	 }
	 elseif($decider=="8"){
	 echo "steel column eurocode ";
	 	$structure_type="column";
	 $columnMaterialType="steel";;
	$columnLength=0;
	$column_axial_load_permanant=0;
	$column_axial_load_varable="N/A";
	$columnWidth="n/a";
	$columnDepth="n/a";
	$columnWeight="N/A";
	$column_cod="ec";
	 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Column section') == false)) {
					$currLineIndex++;
				}
			
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				$pos1=strpos($tempLength, "buckling about ", 0);
				 $pos2=strpos($tempLength,"Buckling lengthtab ", $pos1+strlen("buckling about"));
				  $pos3=strpos($tempLength," Buckling lengthtab ", $pos2+strlen(" Buckling lengthtab "));
				  $pos4=strpos($tempLength," Axial loadtab ", 0);
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4 = substr($tempLength, $pos3);	
				$tempLength5 = substr($tempLength, $pos4);	
				
					$columnLength = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " sectd", 0)), ' '));
				echo "buckling about z mm <br>";
						
			 echo $columnLength;
				$columnLength = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mmpar", 0)), ' '));
				echo "buckling about zmm <br>";
				//double check this		
				//ask 
				  echo $columnLength;
				  
				$column_axial_load_permanant= Floatval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " k", 0)), ' '));
				echo "Axial load kn/n2<br>";
				echo $column_axial_load_permanant;
				$columnLength= Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " mmpar", 0)), ' '));
				echo "buckling about z mm Final <br>";
		echo $columnLength;
		
			//down works column 
				
				 
	 }elseif($decider =="9"){
	 echo "its a Retaining wall Bs";
				$currLineIndex = 0;
				$tempLength = "";
				
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Retaining wall ') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				echo "<br>";
				$pos1=strpos($tempLength, "wall stem", 0);
				$pos2=strpos($tempLength, "wall stem",$pos1+strlen("wall stem"));
				$pos3=strpos($tempLength, "Thickness of downstand",0);
				$pos4=strpos($tempLength, "ground water",0);//kept on reading lenght of foundation so did this
				   $tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);//retaining wall done
					$tempLength4 = substr($tempLength, $pos3);			
				$tempLength5=substr($tempLength, $pos4);
			   $var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
			   echo "Height of soil<br>";//check and ask if this is right number to get 
				echo $var_db_04_depth;
				 $var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo"wall thickness <br>"	;		
			   echo $var_db_04_depth;
				$var_db_04_depth = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " mm", 0)), ' '));
				echo"thickness of base <br>"	;		
			   echo $var_db_04_depth;
						echo"Height of water <br>"	;		
			   echo $var_db_04_depth=intval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " mm", 0)), ' '));
				  echo $var_db_04_depth;
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Allowable bearing pressure') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				$pos1=strpos($tempLength, "Allowable", 0);
				 $pos2=strpos($tempLength, "Surcharge load", 0);
				$pos3=strpos($tempLength, "Variable surcharge loadsectd ", 0);
				$tempLength2 = substr($tempLength, $pos1);
						$tempLength3 = substr($tempLength, $pos2);
						
				$tempLength4= substr($tempLength, $pos3);//here
				
				$var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " k", 0)), ' '));
				echo "Allowable bearing pressure Kn/m2 <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " k", 0)), ' '));
				echo "Surcharge load Kn/m2 <br>";
				echo $var_db_04_depth;
				
				$var_db_04_depth = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " k", 0)), ' '));
				echo "Surcharge <br>";
				echo $var_db_04_depth;
				
				
	 }elseif($decider =="10"){
	 echo "mason wall ec";
	 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Width of column') == false)) {
					$currLineIndex++;
				}
			
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				echo $currLineIndex;
				echo "<br>";
				echo $linesExtraCount;
				
				$pos1=strpos($tempLength, "Width of columntab", 0);
				 $pos2=strpos($tempLength,"Thickness of columntab", 0);
				  $pos3=strpos($tempLength,"Height of columntab", 0);
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4 = substr($tempLength, $pos3);	
				
				
				$var_db_04_depth = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " m", 0)), ' '));
				echo "width mm <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " m", 0)), ' '));
				echo "thickness mm <br>";
				echo $var_db_04_depth;
				$var_db_04_depth = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " m", 0)), ' '));
				echo "Heigth mm <br>";
				echo $var_db_04_depth;
				  while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'dead load') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				 if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
			$pos1=strpos($tempLength, "dead loadtab", 0);
			$pos2=strpos($tempLength, "Vertical live loadtab", $pos1+strlen("deadloadtab"));
			$tempLength2 = substr($tempLength, $pos1);
			$tempLength3 = substr($tempLength, $pos2);
			$var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " kN", 0)), ' '));
				echo "Dead load <br>";
				echo $var_db_04_depth;
					$var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " kN", 0)), ' '));
				echo "Live load <br>";
				echo $var_db_04_depth;
		//mason done!		
		  }elseif($decider=="12"){ //why isnt it reading it???//why do I need it up here??
	 echo "got here";
	 
				echo "its a beam Steel";
				echo "<br>";
				//going in here
				while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Support conditions') == false)) {
					$currLineIndex++;
				}
				
				//this is a comment
				if ($currLineIndex < $linesCount) {
				
					while (($currLineIndex < $linesCount) && (strpos($lines[$currLineIndex], 'Maximum') == false)) {
						$currLineIndex++;
						
						if ((strpos($lines[$currLineIndex], 'Support A') == true) ||
								(strpos($lines[$currLineIndex], 'Support B') == true) ||
								(strpos($lines[$currLineIndex], 'Support C') == true) ||
								(strpos($lines[$currLineIndex], 'Support D') == true) ||
								(strpos($lines[$currLineIndex], 'Support E') == true) ||
								(strpos($lines[$currLineIndex], 'Support F') == true)) {
							$var_db_11_calc_num_reactions++;//doesnt work in tedds for some reason
							
							}
						
					}
				}
				
					echo "noOfSpans";//gets no of spans as no of reactions minis one 
							$noOfSpans=$var_db_11_calc_num_reactions-1;
							echo "<br>";
							echo $noOfSpans;
							echo "<br>";//works for bs
							$counter=($var_db_11_calc_num_reactions*2)-1;
							//wont work for an over hang willl give 11 for b and a anyway
				 $currLineIndex = 0;
				$tempWidth = "";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Maximum moment ') == false)) {//try 3 equals
					$currLineIndex++;
					
				}
				echo"tester <br>";
				echo $currLineIndex;
				echo "<br> be here";
				echo $linesExtraCount;
				echo "<br>";
				if ($currLineIndex < $linesExtraCount) {
			
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
						$currLineIndex++;
						echo "2";
					}
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					if (strpos( $tempWidth, 'Support Asectd') == true)      //had to write them like this                    
							  {
							$var_db_11_calc_num_reactions++;
							echo "get in there a";
							
							}
						if(strpos( $tempWidth, 'Support Bsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there b";
					}	
					if(strpos( $tempWidth, 'Support Csectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there c";
					}
					if(strpos( $tempWidth, 'Support Dsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there d";
					}
					if(strpos( $tempWidth, 'Support Esectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there e";
					}
					if(strpos( $tempWidth, 'Support Fsectd') == true){
					$var_db_11_calc_num_reactions++;
							echo "get in there f";
					}
				}
				   //actually no of spans calculated here
				   echo "<br>";
							echo $var_db_11_calc_num_reactions;
							echo "noOfSpans";
							$noOfSpans=$var_db_11_calc_num_reactions-1;
							echo $noOfSpans;
							$counter=($var_db_11_calc_num_reactions*2)-1;
				//FOR THE MOMENTS MAX
				echo "<br>";
				echo"tester <br>";
				
				echo "<br>";
				
				echo "<br>";
				
									
				$pos1=strpos($tempWidth, "Msub s1_maxsectd", 0);
				
				$tempWidth = substr($tempWidth, $pos1);
				
				//test
				// a trail with this one 
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));//works need space at start!
				//Redo this tomoro
				echo "Right here";
				  echo $var_db_03_width1;
				  echo "<br>";
				  echo $pos1;
				  
						  
			echo "Getting maximun moments <br>";
				for($x=0;$x<$counter;$x++){
				
				$pos2=strpos($tempWidth, " Msub s{$x}_maxsectd",$pos1+strlen("Msub s1_maxsectd"));//come back here maybe do them sperately??
				//in the steel makes it go up by one each time
				//works !
				//does the frist one twice come back to this
				echo $pos2;
				echo "<br>";
				
				echo "<br>";
				$tempWidth2 = substr($tempWidth, $pos2);
				
			$pos1=$pos2;
			
			echo "<br>";
			echo $pos2;
				echo"this one of them";
				 $var_db_03_width1 = floatval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));
				  echo $var_db_03_width1;
				  echo "<br>";
						}
						$pos1=strpos($tempWidth, "Msub s1_minsectd", 0);
				
				$tempWidth = substr($tempWidth, $pos1);
				
				//test
				// a trail with this one 
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));//works need space at start!
				//Redo this tomoro
				echo "Right here";
				  echo $var_db_03_width1;
				  echo "<br>";
				  echo $pos1;
				  echo "getting min moments";
						for($x=0;$x<$counter;$x++){
				
				$pos2=strpos($tempWidth, " Msub s{$x}_minsectd",$pos1+strlen("Msub s1_minsectd"));//come back here maybe do them sperately??
				//in the steel makes it go up by one each time
				//works !
				//does the frist one twice come back to this
				echo $pos2;
				echo "<br>";
				
				echo "<br>";
				$tempWidth2 = substr($tempWidth, $pos2);
				
			$pos1=$pos2;
			
			echo "<br>";
			echo $pos2;
				echo"this one of the mins them";
				 $var_db_03_width1 = floatval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 kNmsectd linex0", 0)), ' '));
				  echo $var_db_03_width1;
				  echo "<br>";
						}
		
			//$pos2=strpos($tempWidth, "Maximum moment",$pos1+strlen("Maximum moment"));
			//	$pos3=strpos($tempWidth, "Maximum moment",$pos2+strlen("Maximum moment"));
				
			 //   $tempWidth2 = substr($tempWidth, $pos1);
				
				
			   // $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " k", 0)), ' '));
				echo "<br>";			
				
				
		
			

	 
				$currLineIndex = 0;//here
				$tempWidth = "hello";
				
				while (($currLineIndex < $linesExtraCount) && (stristr($linesExtra[$currLineIndex], 'reaction at support a') === false)) {//Wont work with a???
					$currLineIndex++;
					
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
				
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], " k") == false)) {
						
						$tempWidth = $tempWidth . $linesExtra[$currLineIndex];//this varables used because of the arry bug
						$currLineIndex++;
						echo "in here 2";
					}
					echo "Space";
					
					
					$tempWidth = $tempWidth . $linesExtra[$currLineIndex];
					
				}
			
				$pos1=strpos($tempWidth, " Maximum reaction at support Asectd", 0);
				$tempWidth = substr($tempWidth, $pos1);
				
				$var_db_03_width1 = floatval(strrchr(substr($tempWidth, 0, strpos($tempWidth, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 k", 0)), ' '));
				echo "frist reaction";
				 echo $var_db_03_width1;
					for($x=0;$x<$var_db_11_calc_num_reactions;$x++){
				$pos2=strpos($tempWidth, "Maximum reaction at support ",$pos1+strlen("Maximum reaction at support "));
				$tempWidth2 = substr($tempWidth, $pos2);
				$pos1=$pos2;
				 echo "<br>";
				echo $pos2;
				echo"br";
				 $var_db_03_width1 = floatval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " rtlchfcs1 af1 ltrchfcs0 insrsid16666886 k", 0)), ' '));
				 echo $var_db_03_width1;
				
				}
				  $currLineIndex = 0;
					$tempWidth = "";
				//getting lengths??? try thid
			//weights harder to get
				echo"right here it us";
				
			
			
					   
				//be hard to get the others since It wont read A there values are in temp width 2
				echo " pause ";
										   
						//in here	          			           
				// here for d start here on monday
			   //may have delected some thing imporant here          
		
	 }elseif($decider==11){
	 echo "Pad bs this one 11";
		$currLineIndex = 0;
				$tempLength = "";
				
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Length of pad') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				echo "<br>";
				$pos1=strpos($tempLength, "Length of pad", 0);
				$pos2=strpos($tempLength, "Width of pad",0);
				$pos3=strpos($tempLength, "Depth of pad",0);
				
				   $tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
					$tempLength4 = substr($tempLength, $pos3);			
				
			   $var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
			   echo "Length of foundation<br>";
				echo $var_db_04_depth;
				 $var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo"Width of foundation <br>"	;		
			   echo $var_db_04_depth;
				$var_db_04_depth = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " mm", 0)), ' '));
				echo"Depth of pad <br>"	;		
			   echo $var_db_04_depth;
				
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Total base reaction') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'kN') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				$pos1=strpos($tempLength, "Total base reactiontab", 0);
				
				$tempLength2 = substr($tempLength, $pos1);
						//need to modify for no of coloumns
				$var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " kN", 0)), ' '));
				echo "Total base reaction kn/m2 <br>";//ask if this is ok
				echo $var_db_04_depth;
				//got pad bs working
	 //$var_db_29_ratio = $var_db_27_weight_material_2 / $var_db_15_volume;
		$currLineIndex = 0;
				$tempLength = "";
			$noOfColumns=2;
				 while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Ultimate horizontal load in x') == false)) {
					$currLineIndex++;
				}
				
				
				echo "<br>";
				 if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			   
				$pos4=strpos($tempLength, "Ultimate horizontal load in x",0);//trying this to detect column
				$pos5=strpos($tempLength, "Ultimate horizontal load in x",$pos4+strlen("Ultimate horizontal load in x"));//trying this to detect extra coloumn
				//come back to this on monday
			if ($pos5 === false) {
		echo "The string '' was not found in the string";
		$noOfColumns=1;
	} else {
		echo "The string  was found in the string ";
		echo " and exists at position $pos5";
	}			
				echo "No of columns";
				echo $noOfColumns;//Works Finally!!!!!
	 }elseif($decider=="14"){
	 echo "Pile bc";
	  $currLineIndex = 0;
				$tempLength = "";
				
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Number of piles') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'kn') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				echo "<br>";
				
				$pos1=strpos($tempLength, "Number of piles", 0);
				$pos2=strpos($tempLength, "Uls axial load",0);
				$pos3=strpos($tempLength, "Characteristic axial load",0);
				$pos4=strpos($tempLength, "Pile diameter",0);
				$pos5=strpos($tempLength, "Overall length of pile",0);
					$pos6=strpos($tempLength, "Overall width of pile",0);
					$pos7=strpos($tempLength, "Overall height of pile",0);
				$pos8=strpos($tempLength, "x of",0);
					$pos9=strpos($tempLength, "y of",0);
				   $tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
					$tempLength4 = substr($tempLength, $pos3);			
				$tempLength5 = substr($tempLength, $pos4);	
				$tempLength6 = substr($tempLength, $pos5);	
				$tempLength7 = substr($tempLength, $pos6);
				$tempLength8 = substr($tempLength, $pos7);
				$tempLength9 = substr($tempLength, $pos8);
				$tempLength10 = substr($tempLength, $pos9);
			   $var_db_04_depth = intval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " par", 0)), ' '));
			   echo "Number of piles<br>";
				echo $var_db_04_depth;
				 $var_db_04_depth = intval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " kN", 0)), ' '));
				echo"Uls axial load <br>"	;		
			   echo $var_db_04_depth;
				$var_db_04_depth = intval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " kN", 0)), ' '));
				echo"charactistic axial load <br>"	;		
			   echo $var_db_04_depth;
				 $var_db_04_depth = intval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " mm", 0)), ' '));
				echo"Pile diameter<br>"	;		
			   echo $var_db_04_depth;
			   $var_db_04_depth = intval(strrchr(substr($tempLength6, 0, strpos($tempLength6, " mm", 0)), ' '));
				echo"Length<br>"	;		
			   echo $var_db_04_depth; 
				 $var_db_04_depth = intval(strrchr(substr($tempLength7, 0, strpos($tempLength7, " mm", 0)), ' '));
				echo"Width<br>"	;		
			   echo $var_db_04_depth; 
				$var_db_04_depth = intval(strrchr(substr($tempLength8, 0, strpos($tempLength8, " mm", 0)), ' '));
				echo"Height<br>"	;		
			   echo $var_db_04_depth; 
				 $var_db_04_depth = intval(strrchr(substr($tempLength9, 0, strpos($tempLength9, " mm", 0)), ' '));
				echo"Dimension width<br>"	;		
			   echo $var_db_04_depth; 
			   $var_db_04_depth = intval(strrchr(substr($tempLength10, 0, strpos($tempLength10, " mm", 0)), ' '));
				echo"Dimension height<br>"	;		
			   echo $var_db_04_depth; //pile cap done
			 
			}elseif($decider=="15")
			{
			 echo "one way timber joist bs";//need more things out???ask

	 //get lx and ly load
					 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Number of spans') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos1=strpos($tempLength, "Number of spanstab", 0);
				 $pos2=strpos($tempLength, "Effective length of span", 0);
			 
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				
		
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, "insrsid539052charrsid5793942", 0)), ' '));
				echo "Number of spans   <br>";
				echo $No;//it works
				$var_db_04_depth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " kN", 0)), ' '));
				echo "Length <br>";
				echo $var_db_04_depth;
				for($i=0;$i<$No;$i++)
				{
				$pos2=strpos($tempLength, "length of span",$pos1+strlen("length of span"));
				$tempWidth2 = substr($tempLength, $pos2);
				$pos1=$pos2;
				 echo "Length <br>";
				echo $pos2;//one way slab ec working
				echo"br";
				 $var_db_03_width1 = intval(strrchr(substr($tempWidth2, 0, strpos($tempWidth2, " m", 0)), ' '));
				 echo $var_db_03_width1;
				}
				//ask about loading have a lot of factors to take into account do it later at testing
				// Length of shorter side of slabtab
				  //Length of longer side of slabtab
				  //Design ultimate load per unit areatab
	 }    elseif($decider=="16")
			{
			 echo "One way spanning concrete bs ";//need more things out???ask

	 //get lx and ly load
					 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'depth of slab') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			
				$pos1=strpos($tempLength, "Depth of slab", 0);
			 
			 
				$tempLength2 = substr($tempLength, $pos1);
				
				
		
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "Depth of slab  <br>";
				echo $No;//it works
				
				//ask about loading have a lot of factors to take into account do it later at testing
				// Length of shorter side of slabtab
				  //Length of longer side of slabtab
				  //Design ultimate load per unit areatab
				   $currLineIndex = 0;
				   while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Design sagging moment') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'kn') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos1=strpos($tempLength, "Design sagging moment", 0);
				$tempLength2 = substr($tempLength, $pos1);
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " kN", 0)), ' '));
				echo "Design sagging moment  <br>";//double check
				echo $No;//it works
				
				 while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Design hogging moment') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'kn') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
					$pos1=strpos($tempLength, "Design hogging moment", 0);
				$tempLength2 = substr($tempLength, $pos1);
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " kN", 0)), ' '));
				echo "Design hogging moment  <br>";//double check
				echo $No;//it works
							 while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Slab span length') == false)) {
					$currLineIndex++;
				}
			
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'm ') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
					$pos1=strpos($tempLength, "span lengthrtlchfcs1", 0);
				$tempLength2 = substr($tempLength, $pos1);
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " m", 0)), ' '));
				echo "Length lx  <br>";//double check
				echo $No;//it works
			
	 }elseif($decider=="17")
			{
			 echo "Two way spanning concrete bs ";//need more things out???ask

	 //get lx and ly load
					 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'depth of slab') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			
				$pos1=strpos($tempLength, "Depth of slab", 0);
			 
			 
				$tempLength2 = substr($tempLength, $pos1);
				
				
		
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "Depth of slab  <br>";
				echo $No;//it works
			//here we are
				   $currLineIndex = 0;
				   while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Length of shorter side of slabtab') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				$pos1=strpos($tempLength, "Length of shorter side of slabtab", 0);
				$pos2=strpos($tempLength, "Length of longer side of slabtab", $pos1+strlen("Length of longer side of slabtab"));
				$pos3=strpos($tempLength, "Design ultimate ", $pos1+strlen("Length of longer side of slabtab"));
				$tempLength2 = substr($tempLength, $pos1);
					$tempLength3 = substr($tempLength, $pos2);
					$tempLength4=substr($tempLength,$pos3);
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " m", 0)), ' '));
				echo "Length of shorter side  <br>";//double check
				echo $No;//it works
				$No = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " m", 0)), ' '));
				echo "Length of Longer side  <br>";//double check
				echo $No;//it works
				$No = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " kN", 0)), ' '));
				echo "Load of unit area <br>";//double check
				echo $No;//it works				 
	 }elseif($decider=="18"){//fixed
	 echo "Mason coloumn ec";
	 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
	$structure_type="column";
	 $columnMaterialType="Mason";
	$columnLength=0;
	$column_axial_load_permanant=0;
	$column_axial_load_varable="N/A";
	$columnWidth="n/a";
	$columnDepth="n/a";
	$columnWeight="N/A";
	$column_cod="ec";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Width of column') == false)) {
					$currLineIndex++;
				}
			
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				$pos1=strpos($tempLength, "Width of column", 0);
				 $pos2=strpos($tempLength,"Thickness of column", $pos1+strlen("Width of column"));
				  $pos3=strpos($tempLength,"Height of column", $pos2+strlen("Thickness of column"));
			
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4 = substr($tempLength, $pos3);	
				
				
					$columnWidth = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "Width of coloum mm <br>";
				echo  	$columnWidth;
				
				$columnDepth = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo "Thickness of coloumn mm <br>";
				echo $columnDepth;
				$columnLength = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " mm", 0)), ' '));
				echo "Height at coloumn mm <br>";
				echo $columnLength;
				
			 while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Vertical dead load') == false)) {
					$currLineIndex++;
				}
			
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'kN') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
					$pos1=strpos($tempLength, "Vertical dead load", 0);
				 $pos2=strpos($tempLength,"Vertical live load", $pos1+strlen("Vertical dead load"));
				  $pos3=strpos($tempLength,"Vertical Wind load", $pos2+strlen("Thickness of column"));
				
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4 = substr($tempLength, $pos3);	
				$column_axial_load_permanant = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " kN", 0)), ' '));
				echo "Vertical dead load <br>";
				echo $column_axial_load_permanant;
				$column_axial_load_varable = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " kN", 0)), ' '));
				echo "Vertical live load <br>";
				//double check this	
				echo $column_axial_load_varable;
				
				$var_db_04_depth = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " kN", 0)), ' '));
				echo "Vertical Wind load <br>";
				echo $var_db_04_depth;
	 }elseif($decider=="19")
			{
			 echo "Timeber stud design ";//need more things out???ask

	 //get lx and ly load
					 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Stud breadth') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			
				$pos1=strpos($tempLength, "Stud breadth", 0);
			 $pos2=strpos($tempLength, "Stud depth", $pos1+strlen("Stud breadth"));
			
			 
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				
				
		
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "stud breath  <br>";
				echo $No;//it works
			//here we are
			$No = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo "stan depth <br>";
				echo $No;//it works
				$currLineIndex = 0;
				   while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Panel height') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				$pos1=strpos($tempLength, "Panel height", 0);
			 $pos2=strpos($tempLength, "Stud length", $pos1+strlen("Stud length"));
			
			 
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "panal height <br>";
				echo $No;//it works
			//here we are
			$No = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo "stud length <br>";
				echo $No;//it works
								 
	 }elseif($decider=="20")
			{
			 echo "Masonary wall bs";//need more things out???ask

	 //get lx and ly load
					 $currLineIndex = 0;//come back to this one 
				$tempLength = "";
				echo "<br>";
				while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Panel length') == false)) {
					$currLineIndex++;
				}
				echo $currLineIndex;
				echo $linesExtraCount;
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
			
				$pos1=strpos($tempLength, "Panal length", 0);
			 $pos2=strpos($tempLength, "Panel height", $pos1+strlen("Panel length"));
			$pos3=strpos($tempLength, "SupportDescriptionrtlchfcs1", 0);
			 $pos4=strpos($tempLength, "SupportDescriptionrtlchfcs1", $pos3+strlen("SupportDescriptionrtlchfcs1"));
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);	
				$tempLength4 = substr($tempLength, $pos3);	
				$tempLength5 = substr($tempLength, $pos4);	
				//Get this working tomoro !16/10/2013
				
				
				
		
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "Panal length  <br>";
				echo $No;//it works
			//here we are
			$No = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo "Panel height <br>";
				echo $No;//it works
				
				//getting string between to sub strings//nb this code may need it again
				//move this to top maybe
				function get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if ($ini == 0) return "";
		$ini += strlen($start);
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}
	$parsed = get_string_between($tempLength4, ",", "srtlchfcs1 af1 ltrchfcs0");
	echo "Suppot condition one <br>";
	echo $parsed;
	$parsed = get_string_between($tempLength5, ",", "srtlchfcs1 af1 ltrchfcs0");
	echo "Suppot condition two <br>";
	echo $parsed;


				
				$currLineIndex = 0;
				   while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Outer leaf thickness') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], ' mm') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
				$pos1=strpos($tempLength, "Outer leaf thickness", 0);
			 $pos2=strpos($tempLength, "Inner leaf thickness", $pos1+strlen("outer leaf thickness"));
			
			 
				$tempLength2 = substr($tempLength, $pos1);
				$tempLength3 = substr($tempLength, $pos2);
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " mm", 0)), ' '));
				echo "<br> outer leaf thickness <br>";
				echo $No;//it works
			//here we are
			$No = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " mm", 0)), ' '));
				echo "inner leaf thickness<br>";
				echo $No;//it works
			$currLineIndex = 0;
				   while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'wind load') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], ' k') == false)) {
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
							
				$pos1=strpos($tempLength, "wind load", 0);
				 $pos2=strpos($tempLength, "Dead load", 0);
			 $pos3=strpos($tempLength, "Dead load", $pos2+strlen("Dead load"));
			 $pos4=strpos($tempLength, "Imposed load", 0);
			  $pos5=strpos($tempLength, "Imposed load", $pos4+strlen("Imposed load"));
				$tempLength2 = substr($tempLength, $pos1);
			$tempLength3 = substr($tempLength, $pos2);
				$tempLength4 = substr($tempLength, $pos3);
				$tempLength5 = substr($tempLength, $pos4);
				$tempLength6 = substr($tempLength, $pos5);
				$No = Floatval(strrchr(substr($tempLength2, 0, strpos($tempLength2, " kN/mrtlchfcs1", 0)), ' '));//needed to change k to this 
				echo "wind load <br>";
				echo $No;//it works
				$No = Floatval(strrchr(substr($tempLength3, 0, strpos($tempLength3, " kN/mrtlchfcs1", 0)), ' '));
				echo "dead load outer <br>";
				echo $No;//it works
				$No = Floatval(strrchr(substr($tempLength4, 0, strpos($tempLength4, " kN/mrtlchfcs1", 0)), ' '));
				echo "dead load inner<br>";
				echo $No;//it works
				$No = Floatval(strrchr(substr($tempLength5, 0, strpos($tempLength5, " kN/mrtlchfcs1", 0)), ' '));
				echo "imposed load outer<br>";
				echo $No;//it works
				$No = Floatval(strrchr(substr($tempLength6, 0, strpos($tempLength6, " kN/mrtlchfcs1", 0)), ' '));
				echo "imposed load inner<br>";
				echo $No;//it works
				$currLineIndex = 0;
				   while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], 'Masonry type') == false)) {
					$currLineIndex++;
				}
				
				if ($currLineIndex < $linesExtraCount) {
					while (($currLineIndex < $linesExtraCount) && (strpos($linesExtra[$currLineIndex], ' mm') == false)) {//get the two lines
						$tempLength = $tempLength . $linesExtra[$currLineIndex];
						$currLineIndex++;
					}
					$tempLength = $tempLength . $linesExtra[$currLineIndex];
				}
				
			$pos1=strpos($tempLength, "Masonry typetab", 0);
			$tempLength2 = substr($tempLength, $pos1);
			
			$parsed = get_string_between($tempLength2, "fldrslt fs18bf1cf1", "having");//may need to change this haveing may not always be present try and testing
	echo "Masonary type <br>";
	echo $parsed;
	 }//for the lines count all code between here
	 //for the lines count all code between here
	 }
	 }

	 
		$retData = array(
			//beamConcrete
			'structure_type'=>$structure_type,
			'beam_material_type' =>  $var_db_02_beam_material_type,
			'beam_num_spans' =>  $var_db_03_beam_num_spans,
			'beam_width' => $var_db_06_beam_width,
			'beam_depth' =>  $var_db_04_depth,
			'beam_moment'=>$var_db_04b_beam_moment,//lengths this is an array
			'beam reactions'=>$var_db_05_beam_reaction,
			'beam_length'=>$var_db_04_beam_length,
			'beam_weight'=>$var_beam_weight,
			'beam_code_of_practice'=>$code,
			//pad
	          'structure_type'=> $structure_type,
               	'pad_number_of_columns'=>$pad_number_of_columns,
            	'pad_allowable_bearing_pressure'=>$pad_allowable_bearing_pressure,
	          'pad_axial_load_permanent'=>$pad_axial_load_permanent=array(),
	   'pad_permanentload_varable'=>$pad_axial_load_varable=array(),
	'pad_length'=>$padLength,
 	'pad_width'=>$padWidth,
	'pad_depth'=>$padDepth,
	'pad_weight'=>$padWeight,
	'pad_code_of_practice'=>$padCod,
//column
	
	 'column_material_type'=>$columnMaterialType,
	'column_Length'=>$columnLength,
	'column_axial_load_permanant'=>$column_axial_load_permanant,
	'column_axial_load_varable'=>$column_axial_load_varable,
	'column_width'=>$columnWidth,
	'column_depth'=>$columnDepth,
	'column_weight'=>$columnWeight,
	'column_code_0f_pactise'=>$column_cod,
	//retaining wall
	
	    'retaining_wall_material_type'=> $retaining_wall_material_type,
         'reatining_wall_surcharge'=> $retaining_wall_surcharge,
           'retaining_wall_allowable_bearing_pressure'=>   $retaining_wall_allowable_bearing_pressure,
         'retaining_wall_height_water'=>$retaining_wall_height_water,
      'retaining_wall_height_soil'=>   $retaining_wall_height_soil,
    'base_thickness'=>$base_thickness,
     'base_length'=>$base_length,
    'wall_thickness'=>$wall_thickness,
   'retaining_wall_code_of_practice '=>$retaining_wall_code_of_practice,
			
		);
		return $retData;

	}


	 
	$procesedData = process_file_ajax('steel bs.ted');
	//fix one was spanning frist thing tomoro ec 22/10/2013 //files wont work???put in .rtf at end for rich text files//Wall not working with wall change bit at top
	//note wont read end of file for rtf does for .ted
	//use .ted at end when I have teds installed to get it to work without teds just frist part of file is enough
	//.ted gives a much higher number of offset errors
	//do pad bs
	//one way slab length not give in document 
	echo "Entry";
	echo $procesedData['file_type'] . "<br/>";
	echo $procesedData['structure_type'] . "<br/>";
	echo $procesedData['width'] . "<br/>";
	echo $procesedData['depth'] . "<br/>";
	echo $procesedData['beam_length'] . "<br/>";
	echo $procesedData['beam_reactions'] . "<br/>";
	echo $procesedData['beam_Moment'] . "<br/>";
	echo $procesedData['beam_weight'] . "<br/>";
	echo $procesedData['structure_type'] . "<br/>";
	
	//pad
	echo $procesedData['pad_number_of_columns'] . "<br/>";
	echo $procesedData['pad_allowable_bearing_pressure'] . "<br/>";
	echo $procesedData['pad_axial_load_permanent'] . "<br/>";
	echo $procesedData['pad_permanentload_varable'] . "<br/>";
 echo $procesedData['pad_length'] . "<br/>";
 echo $procesedData['pad_width'] . "<br/>";
 echo $procesedData['pad_depth'] . "<br/>";
 echo $procesedData['pad_weight'] . "<br/>";
 echo $procesedData['pad_code_of_practice'] . "<br/>";
 //column
 
echo $procesedData['column_material_type'] . "<br/>";
	echo $procesedData['column_Length'] . "<br/>";
	echo $procesedData['column_axial_load_permanant'] . "<br/>";
	echo $procesedData['column_axial_load_varable'] . "<br/>";
 echo $procesedData['column_width'] . "<br/>";
 echo $procesedData['column_depth'] . "<br/>";
 echo $procesedData['column_weight'] . "<br/>";
 echo $procesedData['column_code_0f_pactisepad_weight'] . "<br/>";



	?>
