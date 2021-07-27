<?php

function wd_remove_accents($str, $charset = 'utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES | ENT_SUBSTITUTE, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        return $str;
    }

function createSlug($string)
    {
        return strtolower(preg_replace("/[^-\w]+/", "", preg_replace("/&([a-z])[a-z]+;/i", "$1", str_replace([" ", "'"], '-', wd_remove_accents($string)))));
    }



$fp = fopen('bagisto.csv', 'w');

$n =	1;


// Créer un tableau pour stocker les codeArticles
//$tabArts =[];

if(($handle		=	fopen("exportOkEcommerceRuck.csv", "r")) !== FALSE){

	while(($row	=	fgetcsv($handle,200000, ";")) !== FALSE){

		//Sauter la 1ere line
		if ($n !=  1) {

			/*---------------------------------------------------------------------------------------------------------*/
			// A garder au cas où y aurait un mauvais délire avec les type configurable descendus par AKENEO pour gérer la creation de prod CONFIGURABLE a la main
			/*---------------------------------------------------------------------------------------------------------*/
			// // Si c'est un nouveau code article alors il faut créer une ligne dans le CSV avec un type:  configurable 
			// if(!in_array($row[4], $tabArts)) {
		 //      echo "Créer une ligne avec ce codeProduit";

		 //      $lineConfig = array("root", $row[32] ,  $row[13] , $row[37] ,$row[4],  "","" , "" , $row[45] , ""  , "" ,  "","" , "1", $row[11],"0","0","1","1","configurable","Default","size",$row[39],$row[34],"default","", $row[45] ,"1","");

			//   fputcsv($fp,$lineConfig );
		 //    }

		 // //On rajoute le codeArt dans le tableau pour checker au prochain tour
		 // $tabArts[] = $row[4];
		 //echo($row[12]);
			/*---------------------------------------------------------------------------------------------------------*/



		 // On écrit la ligne qui sera donc de type : variant 
		if($row[43] =="simple") {
 
		 $lineVariant = array("", $row[32] , $row[13] , $row[37] , $row[0] , "","","",$row[45],"","","","","",$row[11],"","","","1","variant","Default","size",$row[39],$row[34],"default","", $row[45],"","");

		 fputcsv($fp,$lineVariant);

		}
		// Sinon c'est un configurable
		else{
			
			// Traitement des catégories
			$cates = $row[3];
			//var_dump($cates);
			$explodeCates = explode(";", $cates);
			$categories="";
			
			foreach ($explodeCates as $cate) {
				//var_dump($cate);
				$explodeCate = explode('/', $cate);
				//var_dump($explodeCate);
				$i = 0;
				$len = count($explodeCate);
				foreach ($explodeCate as $val ) {
					
					if($i ==$len - 1){
						if($val != ""){
							//var_dump($val);
							$categories .= $val."," ;
							
							//var_dump($categories);
						}
					
					}
					$n++;
				}
				
			}
			$categories = rtrim($categories, ", ");
			$categories = mb_strtolower( preg_replace('/\s+/', '-', $categories), 'UTF-8');

			// On laisse images vide pour le remplir avec l'autre script a cause de leur zip de merde..
			$images = $row[22] . ",". $row[23] . ",".$row[24]. ",". $row[25] . ",".$row[26]. ",". $row[27];

			// Creée l'url_key
			$urlKey = createSlug($row[32]);
			//var_dump($urlKey);
			

				if($categories != ""){

				 $lineConfig = array($categories, $row[32] ,  $row[13] , $row[37] ,$row[0],  "","" , "" , $row[45] , ""  , "" ,  "",$urlKey , "1", $row[11],"0","0","1","1","configurable","Default","","","","default","", "" ,"1","");

				}else{
					$lineConfig = array("root,homme", $row[32] ,  $row[13] , $row[37] ,$row[0],  "","" , "" , $row[45] , ""  , "" ,  "",$urlKey , "1", $row[11],"0","0","1","1","configurable","Default","","","","default","", "" ,"1","");
				}

			fputcsv($fp,$lineConfig );

		}

	}
	$n++;
	}

	fclose($handle);
	fclose($fp);

}


// AKENEO descend le produit simple PUIS son configurable. FAUT inverser les lignes BAGISTO veux le configurable PUIS son/ses variants

// Inverser le csv pour que les configurables soient AVANT leur variant de HAUT en BAS
function loadCSV($file) {
     $rows = array();

    if (($handle = fopen($file, "r")) !== FALSE) {

        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
           array_push($rows, $data);
        }
        fclose($handle);
        
    }

    return array_reverse($rows);
}



$data = loadCSV('bagisto.csv');

$fpReverse = fopen('bagistoReverse.csv', 'w');


$headersReverse = array('categories_slug','name' , 'description' , 'short_description', 'sku','special_price' , 'special_price_from' , 'special_price_to'  , 'weight', 'meta_title' ,'meta_keywords', 'meta_description', 'url_key', 'tax_category_id', 'cost','new', 'featured', 'visible_individually', 'status', "type" , 'attribute_family_name' , 'super_attributes'  , 'super_attribute_option', 'super_attribute_price', 'inventory_sources', 'super_attribute_qty',"super_attribute_weight", "guest_checkout","images" );
fputcsv($fpReverse,$headersReverse);

foreach ($data as $value) {
	//var_dump($value);
	fputcsv($fpReverse,$value,',','"');

}



// -------------------------------------------------- RAJOUTS IMAGES ------------------------------------------------------------------------------------//



function deZip($file){

        // $file='achive.zip';
        $path='.';
        $zip=new ZipArchive;
         
        $res=$zip->open($file);
         
        if ($res === TRUE)
        {
            $zip->extractTo($path);
            $zip->close();
            echo "Fichier $file extrait avec succès dans $path";
        } else {
            echo "Echec de l'extraction du fichier $file";
        }

}

// On MET LE ZIP au meme endroit que le script
deZip("test.zip");


// il faut pouvoir concaténer plusieurs images pour un meme sku



function getDirContents($dir, &$results = array() ) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            //var_dump($path);
            
            if($extension =="jpg"){
                $path = explode("\\", $path);

                $sku= implode("",array_slice($path, -3, 1, true));
                //var_dump("test" . $sku);
           
              
        
                $name = end($path);  
                //var_dump($name);     

                //$name = $path[6];
                $results[] = $sku .",".$name;
            }

        } 
        else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $extension = pathinfo($path, PATHINFO_EXTENSION); 
            //echo"test";     
        }
    }

    //var_dump($arraySku);
   
       
             

        return($results);

}

// Par défaut le zip akeneo extrait dans ./files
$arrayFiles = getDirContents('./files');

//var_dump($arrayFiles);


// Traiter les images multiples pour un seul Sku
$allsku = array();

    $i = 0;
    foreach($arrayFiles as $r){
        $sku = explode(",", $r);
        if(in_array($sku[0], $allsku)){
            // Concaténer les images par ; Voir fonctionnement de Bagisto 
            $arrayFiles[$i] = $arrayFiles[$i-1].";".$sku[1];
            unset($arrayFiles[$i-1]);
        }
        $allsku[] = $sku[0];
        $i++;
    }

//var_dump($arrayFiles);






// On ouvre le fichier final pour rajouter les images
$fp = fopen('bagistoFinal.csv', 'w');
$headersFinal= array('categories_slug','name' , 'description' , 'short_description', 'sku','special_price' , 'special_price_from' , 'special_price_to'  , 'weight', 'meta_title' ,'meta_keywords', 'meta_description', 'url_key', 'tax_category_id', 'cost','new', 'featured', 'visible_individually', 'status', "type" , 'attribute_family_name' , 'super_attributes'  , 'super_attribute_option', 'super_attribute_price', 'inventory_sources', 'super_attribute_qty',"super_attribute_weight", "guest_checkout","images" );
fputcsv($fp, $headersFinal);


$n          =   1;
if(($handle     =   fopen("bagistoReverse.csv", "r")) !== FALSE){

    while(($row =   fgetcsv($handle,200000, ",")) !== FALSE){

        //Sauter la 1ere line
        if ($n !=  1) {


         $skuCsv = $row[4];
         $type = $row[19];

             if($type == "configurable"){
                //var_dump($row);

                // Boucler sur le tableau , si je trouve le meme sku je rajoute la ligne
                foreach ($arrayFiles as $key => $value) {
        
                    $explodeValue = explode(',', $value);

                    $sku = $explodeValue[0];
                    //var_dump($sku);
                    $pathFile = $explodeValue[1];
                    var_dump($pathFile);

                    if($sku == $skuCsv){
                        //echo ("trouvé");

                        $lineConfig = array($row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12],$row[13],$row[14],$row[15],$row[16],$row[17],$row[18],$row[19],$row[20],$row[21],$row[22],$row[23],$row[24],$row[25],$row[26],$row[27],$pathFile);
                        //var_dump($lineConfig);
                        fputcsv($fp, $lineConfig);
                        break;
      
                    }
                }

             } else{
                //var_dump($row);
                $lineVariant = array($row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11],$row[12],$row[13],$row[14],$row[15],$row[16],$row[17],$row[18],$row[19],$row[20],$row[21],$row[22],$row[23],$row[24],$row[25],$row[26],$row[27],"");
                        //var_dump($lineConfig);
                        fputcsv($fp, $lineVariant);

             }

        }
        $n++;
    }

}
	



