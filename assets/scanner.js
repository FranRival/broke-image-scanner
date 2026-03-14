jQuery(document).ready(function($){

let offset=0
let totalPosts=0

$("#bis_start_scan").click(function(){

offset=0
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

let progress=Math.round((offset/500)*100)

$("#bis_progress").text(progress+"%")

$("#bis_stats").append(
"Images checked: "+res.images.length+"<br>"
)

scanBatch()

})

}

})