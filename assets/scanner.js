jQuery(document).ready(function($){

let offset=0
let totalPosts=0
let allImages=[]

$("#bis_start_scan").click(function(){

offset=0
allImages=[]

$("#bis_progress").text("Initializing scan...")

$.post(bis_ajax.ajax_url,{

action:'bis_get_total_posts',
year:$("#bis_year").val(),
month:$("#bis_month").val()

},function(res){

totalPosts=res.total_posts

scanBatch()

})

})

function scanBatch(){

$.post(bis_ajax.ajax_url,{

action:'bis_scan_batch',
offset:offset,
year:$("#bis_year").val(),
month:$("#bis_month").val()

},function(res){

offset+=res.batch_count

res.images.forEach(function(img){

allImages.push(img)

})

let progress=Math.round((offset/totalPosts)*100)

$("#bis_progress").text(progress+"%")

$("#bis_stats").html(

"Posts scanned: "+offset+" / "+totalPosts+"<br>"+
"Images analyzed: "+allImages.length

)

if(res.batch_count===0){

$("#bis_progress").text("Scan complete")

console.log(allImages)

return

}

scanBatch()

})

}

})