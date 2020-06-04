<?php

$time_start = $time_lap = microtime(true);

$create_files_table = "CREATE TABLE `files` (
  `findex` int(11) NOT NULL,
  `fpath` varchar(512) NOT NULL,
  `fname` varchar(128) NOT NULL,
  `mtime` float NOT NULL,
  `exif_created` float NOT NULL,
  `exif_camerа` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
COMMIT;";

function secondsToTime($seconds)
{
    $dtF = new DateTime('@0');
    $dtT = new DateTime("@$seconds");

    $d = $dtF->diff($dtT)->format('%a');
    $h = $dtF->diff($dtT)->format('%h');
    $i = $dtF->diff($dtT)->format('%i');

    $d = ($d) ? $d . "дн. " : "";
    $h = ($h) ? $h . "ч. " : "";
    $i = ($i) ? $i . "мин. " : "";

    return $d . $h . $i;
}


function jq_header()
{
    $head = '<html>
<head>                                                               
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<!-- chart and elevation
    <script type="text/javascript" src="js/cookie/jquery.cookie.js"></script>
    <script type="text/javascript" src="js/google_sheets_api.js"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script src="https://www.google.com/uds/?file=visualization&amp;v=1&amp;packages=columnchart" type="text/javascript"></script>
-->     
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="js/lib.js"></script> 
    <link href="style.css" rel="stylesheet" type="text/css" />
</head>';

    $head1 = '<html>
<head>                                                               
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="bs/css/bootstrap.min.css" >
    <script src="bs/js/bootstrap.min.js"></script>
    <script src="js/lib.js"></script> 
    <link href="style.css" rel="stylesheet" type="text/css" />
</head>';


echo $head;

}

$gsql = new rdupSQL();

class rdupSQL extends PDO
{
    private $db;
    
    public function __construct() {
    $this->db = new PDO('mysql:host=localhost; dbname=rdup','root', '', 
                    array(PDO::MYSQL_ATTR_LOCAL_INFILE => true));
//     $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }   

/*

$gsql = new rdupSQL('mysql:host=localhost; dbname=rdup', 'root',  '',  array(PDO::MYSQL_ATTR_LOCAL_INFILE => true),
        );


try{
$gsql = new rdupSQL('mysql:host=localhost; dbname=rdup', 'root',  '', 
        array(PDO::MYSQL_ATTR_LOCAL_INFILE => true),); 
//        die(json_encode(array('outcome' => true)));
}

catch(PDOException $ex){
    die(json_encode(array('outcome' => false, 'message' => 'Unable to connect')));
}
*/


    public function getDirContents($dir, &$results = array()) {
        $files = scandir($dir);
    
        foreach ($files as $key => $value) {
            $fname = $dir . DIRECTORY_SEPARATOR . $value;
            $path = realpath($fname);
            
            if (!is_dir($path)) {
//                print $fname."\n";
                
                $info = (object) [];
                
                if ( @exif_imagetype($fname) )  {
//                    print "\n".$fname."\n".exif_imagetype($fname);
                    $exif = exif_read_data($fname, 0, true);
                    $exif_to_db = array(
                    "fsize"=>$exif["FILE"]["FileSize"],
                    "DateTimeOriginal"=> (isset($exif["EXIF"]["DateTimeOriginal"]))? $exif["EXIF"]["DateTimeOriginal"] : "no data" 
                    );
                    }
                else 
                    {
                    $exif_to_db = "exif type not supported";
                    }
                $info->f_path = dirname($path);
                $info->f_basename = basename($path);
                $info->f_size= filesize($path);
                $info->ctime_h=date ("Y-m-d H:i", filectime ($path));
                                
                $results[] = $info;
            } else if ($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
                $results[] = ["f_path"=>dirname($path),"type"=>"dir"];
            }
        }
    
        return $results;
    }

    public function getDupFiles($data = [], $start_pos=0, $perpage=40) // 2020-02-21
        {

        $whe = "where 1 ".((isset($data['f_year']))? " and  LEFT(ctime_h,4) in ('".join("','",$data['f_year'])."') ":" ")
                         .((isset($data['f_path']))? " and  LEFT(f_path,5) in ('".join("','",str_replace('\\','\\\\\\' , $data['f_path']))."') ":" ")
                         .((isset($data['cnt_g']))? " and  cnt_g in ('".join("',",$data['cnt_g'])."') ":" ")
                         .((isset($data['f_ext']))? " and  SUBSTRING_INDEX(f_basename,'.',-1) in ('".join("','",$data['f_ext'])."') ":" ")
                         ; 
            
//         $start_pos=rand(0,120000);
         
         $flist = [];
         
//         $sql = "SELECT fname,fsize,exif_created,fpath FROM `rfiles` order by fname,exif_created,fsize limit :start, :rows ;";
/*
index, ctime_h, exif_Camera, exif_DateTimeOriginal, exif_h, exif_w, f_basename, f_path, f_size
*/         
         $sql = "SELECT f_basename, f_size ,exif_DateTimeOriginal, ctime_h, f_path FROM `dupfiles` $whe  order by cnt_g, f_basename, f_size, exif_DateTimeOriginal asc limit :start, :rows";
         
         print "<br /><m>$sql</m><br />".$start_pos."<br />".$perpage; 
         
         try    
            {
                $sth = $this->db->prepare($sql);
                
                $sth->bindValue(':start', (int)$start_pos, PDO::PARAM_INT);
                $sth->bindValue(':rows', (int)$perpage, PDO::PARAM_INT);
                
                $r = $sth->execute();
           
                while ($result = $sth->fetch(PDO::FETCH_OBJ))
                {
                   $flist[] = $result; 
                }
                
//                var_dump($flist);
                
                return $flist; 
     
             } 
            catch (PDOException $e) {
                throw new pdoDbException($e);
                var_dump($e->getMessage());
            }
 
        
        }


    public function getDupFiles2($whe="", $start_pos=0, $perpage=20) // 2020-02-21
        {
            
//         $start_pos=rand(0,120000);
         
         $flist = [];
         
//         $sql = "SELECT fname,fsize,exif_created,fpath FROM `rfiles` order by fname,exif_created,fsize limit :start, :rows ;";
/*
index, ctime_h, exif_Camera, exif_DateTimeOriginal, exif_h, exif_w, f_basename, f_path, f_size
*/         
         $sql = "SELECT f_basename, f_size ,exif_DateTimeOriginal, ctime_h, f_path FROM `dupfiles` $whe  order by cnt_g, f_basename, f_size, exif_DateTimeOriginal asc limit :start, :rows";
         
         print "$sql, <br />".$start_pos."<br />".$perpage; 
         
         try    
            {
                $sth = $this->db->prepare($sql);
                
                $sth->bindValue(':start', (int)$start_pos, PDO::PARAM_INT);
                $sth->bindValue(':rows', (int)$perpage, PDO::PARAM_INT);
                
                $r = $sth->execute();
           
                while ($result = $sth->fetch(PDO::FETCH_OBJ))
                {
                   $flist[] = $result; 
                }
                
//                var_dump($flist);
                
                return $flist; 
     
             } 
            catch (PDOException $e) {
                throw new pdoDbException($e);
                var_dump($e->getMessage());
            }
        }
    
    public function scannedToDB($data=[])
    {
//        var_dump($data);
        
        $vals = [];
        $cnt = 0;
        
        foreach ($data as $k=>$v)
        {
            $cnt = $cnt +1;
//            if ($cnt > 10 ) break;   
//          print_r ($v);
//          print (" --- ".$v->f_size."\n" );
          if (isset($v->f_size)) 
               $vals[] = "('$v->ctime_h','$v->f_basename','$v->f_path','$v->f_size')";
//          print_r ($vals);
        }

        $sql = "TRUNCATE dupfiles;
                INSERT into dupfiles (
                    ctime_h,
                    f_basename,
                    f_path,
                    f_size ) values ".join(",", $vals).";";
//        print $sql;
        
        $sql = "TRUNCATE dupfiles;";
        
        $res = $this->q($sql);

        $sql = "INSERT into dupfiles (
                    ctime_h,
                    f_basename,
                    f_path,
                    f_size ) values ".join(",", $vals).";";

        
        $res = $this->q($sql);
//        var_dump($res);
    }

    
    public function  getFilterFromStat()
    {
        $res = $this->q("SELECT * from `stats` order by 1,5 desc");
//        var_dump($res);

        print count($res);

        $fld = ["cnt_g"=>5e9, "f_year"=>5e9, "f_path"=>1e10, "f_ext"=>5e9];
          
        $grp = [];
        
        foreach( $res as $k=>$v)
        {
            
            foreach( $fld as $f=>$e)
            {   
                if (!isset($grp[$f])) $grp[$f] = [];
                if (!isset($grp[$f][$v->$f]))
                        { 
                        $grp[$f][$v->$f]['cnt'] = 0;
                        $grp[$f][$v->$f]['size'] = 0;
                        }
                
                $grp[$f][$v->$f]['cnt'] += $v->cnt;    
                $grp[$f][$v->$f]['size'] += $v->size;    
            }
            
        }
        
//        var_dump($grp);

        $str ='';
        
        
        $end = '</button>';
        $start = '<button type="button" class="btn">';
        
        
        foreach ($grp as $k=>$v)
        {
            $str .= "<div class='row'><div f='$k'>";
                foreach($v as $kk=>$vv)
                {
                  $str .= "<button 
                            type='button'
                            title='".
                            number_format($vv['size']/1024/1024, 0, ',', ' '). " ' 
                            class='btn'>".
                            "<img src='c.gif' style='background-color:red;' width=".($vv['size']/$fld[$k])."px height=4px ><br />".
                            "<span v>".$kk."</span>".
                            "<br /><span>".
                            $vv['cnt']."</span>".
                            "".
                            "</button>";
                }                
            
            $str .= "</div></div>"; 
        
        }
        
        echo $str;        

        
    }
    
    
    public function q($sql) // 2020-02-21
        {
         $res = [];
//         print "sql".$sql; 
         try    
            {
                $sth = $this->db->prepare($sql);
                
//                $sth->bindValue(':start', (int)$start_pos, PDO::PARAM_INT);
//                $sth->bindValue(':rows', (int)$perpage, PDO::PARAM_INT);
                
                $r = $sth->execute();
           
                while ($result = $sth->fetch(PDO::FETCH_OBJ))
                {
                   $res[] = $result; 
                }
                
                return $res; 
     
             } 
            catch (PDOException $e) {
                throw new pdoDbException($e);
                var_dump($e->getMessage());
            }
 
        
        }
    
    
    public function getGroups() // 2020-02-21
        {
         $res = [];
         
         $sql = "select Year, tmp.cnt, cnt_g, sum_size, count(*) as cnt_group from 
         (SELECT f_basename, cnt_g, LEFT(`ctime_h`,4) as Year, f_size, count(*) as cnt, sum(f_size) as sum_size FROM `dupfiles` 
         group by f_basename, f_size, LEFT(`ctime_h`,4), cnt_g order by cnt desc) 
         tmp group by tmp.cnt, Year";
         
//        print "$sql"; 
         
         try    
            {
                $sth = $this->db->prepare($sql);
                
//                $sth->bindValue(':start', (int)$start_pos, PDO::PARAM_INT);
//                $sth->bindValue(':rows', (int)$perpage, PDO::PARAM_INT);
                
                $r = $sth->execute();
           
                while ($result = $sth->fetch(PDO::FETCH_OBJ))
                {
                   $res[] = $result; 
                }
                
                return $res; 
     
             } 
            catch (PDOException $e) {
                throw new pdoDbException($e);
                var_dump($e->getMessage());
            }
 
        
        }
        
    public function scvToDB($fname = "O:\mm2\mm2_duplicates_db.csv") // 2020-02-21
    {
        $fname = addslashes($fname );
        
        $row = 1;
        $csv = array();
/*        
        if (($handle = fopen($fname, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                $num = count($data);
//                echo "<p> $num полей в строке $row: <br /></p>\n";
                $csv_arr[$row] = $data;
                $row++;
//                for ($c=0; $c < $num; $c++) {
//                    echo $data[$c] . "<br />\n";
//                }
            }
            fclose($handle);
        }
 */       
//        print_r($csv_arr);
        
        $res_data = array();

        $dtF = date("Y-m-d H:i:s");


        $sql_create =
        "CREATE TABLE `rfiles` (
          `findex` varchar(256) NOT NULL,
          `fpath` varchar(512) NOT NULL,
          `fname` varchar(128) NOT NULL,
          `mtime` varchar(64) NOT NULL,
          `exif_created` varchar(64) NOT NULL,
          `exif_camera` varchar(128) NOT NULL,
          `fsize` int(11) UNSIGNED NOT NULL,
          `exif_size` varchar(64) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

// 	02.path	03.basename	ImgSize	size	ctime	дата съемки	камера
//

        $sql = "Truncate table `rfiles`;";
        
        $sql .= 'set profiling=1;';
        
        $sql .= "LOAD DATA LOCAL INFILE '$fname' 
        INTO TABLE rfiles 
        FIELDS TERMINATED BY '\t' 
        ENCLOSED BY '\"'
        ESCAPED BY ''
        LINES TERMINATED BY '\n'
        IGNORE 1 ROWS
        
        (@no,@path,@basename,@ImgSize,@size,@exif_cdate,@cam,@cdate)
        set
          findex = @no,
          fpath = @path,
          fname = @basename,
          exif_size = @ImgSize,
          fsize = @size,
          exif_camera = @cam,
          mtime=@cdate,
          exif_created=@exif_cdate;";
        
        
        print $sql;
        
        try {
            $sth = $this->db->prepare($sql);
            $r = $sth->execute();
            var_dump($r);
            
//            print "<br />".$sql."<br />Err code:";

            
            var_dump($sth->errorCode());
            ?><br />$sth->errorInfo():<?php 
            var_dump($sth->errorInfo());
            ?><br />fetchAll(PDO::FETCH_ASSOC):<?php 
            var_dump($sth->fetchAll(PDO::FETCH_ASSOC));
            
            } 
            catch (PDOException $e) {
                throw new pdoDbException($e);
                var_dump($e->getMessage());
            }

        $sql_res = '$sql_res ***';    

        $res_json = array('sql_res'=>$sql_res);

        return json_encode($res_json);
    }

    public function drawFileList ($data=[])
    {
//        var_dump($data);

        $flist = $this->getDupFiles($data);
        
        //var_dump($flist);
        
        
        /** Инициируем переменные **/
        $rows = "";
        $grp = [];
        $locations = [];
        
        
        $ext_dict = ['.MPG' => 'video',
        'MP4' => 'video',
        'AVI' => 'video',
        'avi' => 'video',
        'MOV' => 'video',
        'mp4' => 'video',
        'MTS' => 'video',
        'mov' => 'video',
        'info' => 'txt',
        'plt' => 'track',
        'CR3' => 'pho',
        'JPG' => 'img',
        'jpg' => 'img',
        'psd' => 'img',
        'jpeg' => 'img',
        'gif' => 'img',
        'swf' => 'img',
        'wav' => 'audio',
        'mp3' => 'audio',
        'flac' => 'audio',
        'WAV' => 'audio',
        ];
        
        
        
        foreach ($flist as $k=>$v)
        {
            $location = substr($v->f_path,0,10);
            $img = "image/".str_replace("%","%25",substr($v->f_path.'/'.$v->f_basename,3));
            
            $ext = pathinfo($v->f_basename, PATHINFO_EXTENSION);
            
            $ext_type =  $ext_dict[$ext] ?? 'no def';   

            $content = "
                <table><tr><td><input class='add_to_cart' id='$img' type=checkbox ></td>
                <td type='$ext_type'>
                    <img title='$img' src='c.gif' width=20px style='background-color:gray'>
                </td>
                <td width=90%>$v->f_path
                <b>$v->f_basename</b></td>
                <td class=col-sm-1><img src='c.gif' width=110px height=2px>$v->ctime_h</td>
                <td class=col-sm-1>$v->f_size</td>
                </tr>
                </table>
                ";
            
        //    print $ext_type."<br />"; 
            
            switch($ext_type)
            {
            case 'img': 
                        $grp[$v->f_basename.'_'.$v->f_size][$location][] = $content;
                         break;
            case 'video': 
        //                print "**video**";
                        $grp[$v->f_basename.'_'.$v->f_size][$location][] = $content;
                         break;
              default:
                        $grp[$v->f_basename.'_'.$v->f_size][$location][] = $content;
                
            }
            
            array_push($locations, $location);
        }
        
        $locations = array_unique($locations);
        
        //var_dump($locations,1);
        //var_dump($grp,1);
        
        foreach ($grp as $k=>$v)
        {   
            $rows .= "<tr>";

            foreach ($locations as $lk)
            {
        //    $img2 = "file:\\\\".$v->fpath."\\".$v->fname ;
        //    $img = substr($v->fpath."\\".$v->fname,2);
        //    $img = "image/".str_replace('\\','/',$img);
        //    $img = addslashes($img);
        //    getImg ($fname)
              $rows .= ((isset ($v[$lk]))? "<td>".join("",$v[$lk])."</td>":"<td>-</td>");
            }              
            $rows .= "</tr>";
        }
        
        $th = '';
        
        foreach ($locations as $lk)
            {
                $th .= "<th><input type=checkbox> ".$lk."</th>";
            }      
        
        print  "<table class='table' ><thead><tr class='flist'>$th</tr></thead>".$rows."</table>";
        
        function microtime_float()
        {
            list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
        }

        
    } 
 
 
 
 }



// ****** helpers ********************************************************


$tm_on = 0;

function tm($s = '', $is_str = 0)
{
    global $time_start, $time_lap, $tm_on;

    $t = microtime(true);
    $res = $t;

    if ($s == '') {
        $time_lap = $time_start = microtime(true);
        return;
    } else {
        $res = sprintf("<sup><a class=red>+%0.4f</a> %0.3f %s <a class=blue>%s</a></sup><br/>",
            ($t - $time_lap), ($t - $time_start), date('M-d H:i:s', time()), $s);

        $res2 = sprintf("+%0.4f %0.3f %s %s\n", ($t - $time_lap), ($t - $time_start),
            date('M-d H:i:s', time()), $s);

    }

    $time_lap = $t;

    if ($is_str || !$tm_on)
        return $res2;
    else
        echo $res;
}


function getImg ($fname)
{
    $fname = addslashes($fname );
    $type = pathinfo($fname, PATHINFO_EXTENSION); 
    $data = file_get_contents($fname);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    return $base64;

}


/*
 $exif_struct    = array(7) {
      ["FILE"]=>
      array(6) {
        ["FileName"]=>
        string(24) "!IMG_20180308_102906.jpg"
        ["FileDateTime"]=>
        int(1520494146)
        ["FileSize"]=>
        int(4423111)
        ["FileType"]=>
        int(2)
        ["MimeType"]=>
        string(10) "image/jpeg"
        ["SectionsFound"]=>
        string(44) "ANY_TAG, IFD0, THUMBNAIL, EXIF, GPS, INTEROP"
      }
      ["COMPUTED"]=>
      array(8) {
        ["html"]=>
        string(26) "width="3000" height="4000""
        ["Height"]=>
        int(4000)
        ["Width"]=>
        int(3000)
        ["IsColor"]=>
        int(1)
        ["ByteOrderMotorola"]=>
        int(1)
        ["ApertureFNumber"]=>
        string(5) "f/2.2"
        ["Thumbnail.FileType"]=>
        int(2)
        ["Thumbnail.MimeType"]=>
        string(10) "image/jpeg"
      }
      ["IFD0"]=>
      array(10) {
        ["Make"]=>
        string(6) "Xiaomi"
        ["Model"]=>
        string(8) "MI MAX 2"
        ["XResolution"]=>
        string(4) "72/1"
        ["YResolution"]=>
        string(4) "72/1"
        ["ResolutionUnit"]=>
        int(2)
        ["Software"]=>
        string(54) "oxygen-user 7.1.1 NMF26F V9.2.1.0.NDDMIEK release-keys"
        ["DateTime"]=>
        string(19) "2018:03:08 10:29:06"
        ["YCbCrPositioning"]=>
        int(1)
        ["Exif_IFD_Pointer"]=>
        int(244)
        ["GPS_IFD_Pointer"]=>
        int(728)
      }
      ["THUMBNAIL"]=>
      array(6) {
        ["Compression"]=>
        int(6)
        ["XResolution"]=>
        string(4) "72/1"
        ["YResolution"]=>
        string(4) "72/1"
        ["ResolutionUnit"]=>
        int(2)
        ["JPEGInterchangeFormat"]=>
        int(908)
        ["JPEGInterchangeFormatLength"]=>
        int(13819)
      }
      ["EXIF"]=>
      array(28) {
        ["ExposureTime"]=>
        string(5) "1/100"
        ["FNumber"]=>
        string(7) "220/100"
        ["ExposureProgram"]=>
        int(0)
        ["ISOSpeedRatings"]=>
        int(200)
        ["ExifVersion"]=>
        string(4) "0220"
        ["DateTimeOriginal"]=>
        string(19) "2018:03:08 10:29:06"
        ["DateTimeDigitized"]=>
        string(19) "2018:03:08 10:29:06"
        ["ComponentsConfiguration"]=>
        string(4) ""
        ["ShutterSpeedValue"]=>
        string(9) "6643/1000"
        ["ApertureValue"]=>
        string(7) "227/100"
        ["BrightnessValue"]=>
        string(5) "0/100"
        ["MeteringMode"]=>
        int(2)
        ["Flash"]=>
        int(16)
        ["FocalLength"]=>
        string(7) "381/100"
        ["SubSecTime"]=>
        string(6) "307310"
        ["SubSecTimeOriginal"]=>
        string(6) "307310"
        ["SubSecTimeDigitized"]=>
        string(6) "307310"
        ["FlashPixVersion"]=>
        string(4) "0100"
        ["ColorSpace"]=>
        int(1)
        ["ExifImageWidth"]=>
        int(3000)
        ["ExifImageLength"]=>
        int(4000)
        ["InteroperabilityOffset"]=>
        int(697)
        ["SensingMethod"]=>
        int(2)
        ["SceneType"]=>
        string(1) ""
        ["ExposureMode"]=>
        int(0)
        ["WhiteBalance"]=>
        int(0)
        ["FocalLengthIn35mmFilm"]=>
        int(22)
        ["SceneCaptureType"]=>
        int(0)
      }
      ["GPS"]=>
      array(3) {
        ["GPSAltitudeRef"]=>
        string(7) "220/100"
        ["GPSTimeStamp"]=>
        array(3) {
          [0]=>
          string(3) "7/1"
          [1]=>
          string(4) "29/1"
          [2]=>
          string(3) "6/1"
        }
        ["GPSDateStamp"]=>
        string(10) "2018:03:08"
      }
      ["INTEROP"]=>
      array(2) {
        ["InterOperabilityIndex"]=>
        string(3) "R98"
        ["InterOperabilityVersion"]=>
        string(4) "0100"
      }
    }
  }

*/

?>