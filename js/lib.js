var previewFile;
var ShowImage;

$( document ).ready(function() {
    
    console.log( "ready!" );
    
    load_filter();

    previewFile = function () {
      var preview = document.querySelector('img');
      var file    = document.querySelector('input[type=file]').files[0];
      var reader  = new FileReader();
      
      console.log("@@ file",file);
    
      reader.onloadend = function () {
        preview.src = reader.result;
      }
    
      if (file) {
        reader.readAsDataURL(file);
      } else {
        preview.src = "";
      }
    }

   
    
    ShowImage = function (filepath){
        var reader=new FileReader(); // File API object
    
        filepath = 'O:/mm2/photo/animation/0405 tropo/Picture 8085.jpg';
    
    
        reader.onload=function(event){
            document.getElementById('img').src = event.target.result;
        }
        reader.readAsDataURL(filepath);
    }

//    ShowImage(filepath);
    
    $("img").on("click1",function(e){
        
        console.log("@@ click1",$(this).attr('src'),"/n",e);
        $(this).closest("div").toggleClass("pop"); 
        
    });
    
    
    
    $("[data-toggle=popover]").popover();

    

  $(".grp th").each(function(e)
  {

//    console.log("@@ year",$(this).text(), year);
    
    $(this).html("<a cls href='?year="+$(this).text()+"' >"+$(this).text()+"</a>");
    
    if ($(this).text() == year) $(this).addClass('active');
    
  });
    
 
 function load_cart_data()
{
  $.ajax({
   url:"fetch_cart.php",
   method:"POST",
   dataType:"json",
   success:function(data)
   {
    $('#cart_details').html(data.img);
    $('.total_price').text(data.size);
    $('.badge').text(data.total_item);
   }
  });
}
 
 
 
 $('#cart-popover').popover({
  html : true,
        container: 'body',
        content:function(){
         return $('#popover_content_wrapper').html();
        }
});
 
 

$(document).on('click', '.filter div button', function(){ $(this).toggleClass('btn-primary') });  
$(document).on('click', '.test', function() {  load_filter(); })
$(document).on('click', '.getfiles', function() {   get_files(); })
$(document).on('click', 'img', function() { showPopup(); })
$(document).on('click', '#file_list table table tr', function() { 
        toggleFileSelect($(this));
        updateChart(); 
        })
$(document).on('click', '.add_to_cart', function(e){
        e.stopPropagation(); 
        updateChart();
        });  

/*
.btn
.btn-default
.btn-primary
.btn-success
.btn-info
.btn-warning
.btn-danger
.btn-link
*/
// 2020-06-03

function updateChart()
{       
     var cnt = 0;
     items = $( "input:checked" ).map( function(){ 
            cnt = cnt+1
            return cnt +'.'+ $(this).attr("id").replace('image/',''); } )
            .get().join('</div><div>');
     var action = "add";
     $("#display_item").html("<div>"+items+"</div>");
     length = $("#display_item div").length;

//     console.log("@@ chart length", length );
     $(".total").text(length);
}

function toggleFileSelect(e){
    chbx = e.find('input');
    chbx.prop("checked", !chbx.prop("checked"));
    console.log("@@ toggleFileSelect",chbx, chbx.prop("checked")); 
}

function showPopup()
{
    console.log("@@ ",$(this));    
}


// 2020-05-31
 
function load_filter()
{

  
  if (1)
  {  
      $.ajax({
       url:"a.php",
       method:"POST",
       data: {"a":"filter" },
       success:function(data)
       {
//        console.log ("@@ load_filter", data) ;  
        $('.filter').html(data);
       }
      });
  }    
}




// 2020-05-30 

function get_files()
{
     var fselected ={};
    
     $('[f]').map(function()
     {
            f = $(this).attr('f');
            fselected[f]=$('[f='+f+'] .btn-primary span[v]').map(function()
// .btn-primary             
                {
                    return $(this).text();
                }).get();
     });
    
      
      console.log ("@@ post get_files:", fselected);  
      
      if (1)
      {  
          $.ajax({
           url:"a.php",
           method:"POST",
           data: {"a":"get_files" ,"f":fselected },
           success:function(data)
           {
            $('#file_list').html(data);
//            console.log ("@@ get_files data", data);
            
           }
          });
      }    
}




    
    
 });