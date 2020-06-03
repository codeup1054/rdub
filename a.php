<?php 
include "lib.php";

$a = (isset($_REQUEST['a']))?$_REQUEST['a']:'';  

//var_dump($_REQUEST);

echo tm(" *** case:".$a)."<br />";

switch($a)
{

case 'read_dir':
    $start_dir = 'O:\mm2\photo\Canon\\';
    $start_dir = 'O:\mm2\photo\Canon\2018\2018-10-09 Рим\\';
    $response = $gsql->getDirContents($start_dir);

    break;
    
case 'read_pcl': 
    $response = $gsql->scvToDB();
    break;
    
case 'filter': 
    $response = $gsql->getFilterFromStat();
    break;
    
case 'get_files': 
    
    $param = (isset($_REQUEST['f']))?$_REQUEST['f']:'';
    $response = $gsql->drawFileList($param);
    break;

default: 
    $response = "no operation";
}

echo tm('>>>')."<br />";

//var_dump ($response)







?>
