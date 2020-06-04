<?php 
/*select tmp.cnt, count(*) from (SELECT fname,fsize, count(*) as cnt FROM `rfiles` group by fname,fsize order by cnt desc) tmp group by tmp.cnt  

SELECT fpath, count(*) FROM `rfiles` group by left(fpath,10)
select count(*) from rfiles
select * from rfiles limit 50000,10
SELECT fname,fsize, count(*) as cnt FROM `rfiles` group by fname,fsize order by cnt desc

http://htmlbook.ru/html5/video
https://www.webslesson.info/2018/04/shopping-cart-by-using-bootstrap-popover-with-ajax-php.html

*/
?>



<?php 
include "lib.php";
tm('start');

header("Content-Type: text/html; charset=UTF-8");
jq_header(); 

?>

<!-- <input type="file" onchange="previewFile()"></br>
<img src="" height="200" alt="Image preview...">
-->
<!-- filters -->

<div class="row1">
    <div class="col-md-8 filter"></div>   
    <div class="col-md-3">
        <button class="btn btn-lg btn-success center-block getfiles">Найти</button>
        <button class="btn btn-lg btn-danger center-block scandir">Сканировать</button>
    <!-- Корзина для переноса файлов -->
        <button class="btn btn-lg btn-info center-block movefiles">Переместить</button><br />
        Всего файлов <span class="total">0</span>
        <span id="display_item"></span>
    </div>        
</div>
<div class="row1"><span id='file_list'></span></div>
<div class='test hide'>
    <button class="btn btn-sn btn-danger center-block cleartestoutput"
            onclick="$('.test').addClass('hide');"
        >Очистить</button>
        <br />
    <textarea ></textarea>
</div>
<?php

tm('>>>')


?>


