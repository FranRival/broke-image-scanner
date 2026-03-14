jQuery(document).ready(function($){

let offset=0
let allImages=[]
let totalImages=0

$("#bis_start_scan").click(function(){

offset=0
allImages=[]
scanBatch()

})

function scanBatch(){

$.post(bis_ajax.ajax_url,{

action:'bis_scan_batch',
offset:offset,
year:$("#bis_year").val(),
month:$("#bis_month").val()

},function(res){

offset+=20

totalImages+=res.total_images

res.images.forEach(function(img){

allImages.push(img)

})

let progress=Math.round((offset/500)*100)

$("#bis_progress").text(progress+"%")

$("#bis_stats").html(

"Images scanned: "+allImages.length

)

if(res.count>0){

scanBatch()

}else{

$("#bis_progress").text("Scan complete")

}

})

}

})