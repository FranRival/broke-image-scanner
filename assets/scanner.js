jQuery(document).ready(function($){

let offset=0
let totalPosts=0
let allImages=[]

$("#bis_year").change(function(){
loadMonthsStatus()
})

loadMonthsStatus()

$("#bis_start_scan").click(function(){

    let selected = $("#bis_month option:selected")

    if(selected.attr("data-empty") == "1"){

    alert("No hay posts en ese mes")
    return

    }

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



function loadMonthsStatus(){

$.post(bis_ajax.ajax_url,{

action:'bis_get_months_with_posts',
year:$("#bis_year").val(),
date_type:'post_date'

},function(res){

$("#bis_month option").each(function(){

let val = $(this).val()

if(val === "") return

if(!res[val]){

$(this).text(getMonthName(val)+" (no data)")
$(this).attr("data-empty","1")

}else{

$(this).text(getMonthName(val)+" ("+res[val]+")")
$(this).removeAttr("data-empty")

}

})

})

}

function getMonthName(m){

const names = [
"",
"January","February","March","April","May","June",
"July","August","September","October","November","December"
]

return names[m]

}




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