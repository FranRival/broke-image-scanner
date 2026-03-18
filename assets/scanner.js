jQuery(document).ready(function($){

let offset = 0
let totalPosts = 0
let allImages = []


// =========================
// EVENTOS
// =========================
$("#bis_year").change(function(){
    loadMonthsStatus()
})

// cargar meses al inicio
loadMonthsStatus()


// =========================
// INICIAR SCAN
// =========================
$("#bis_start_scan").click(function(){

    let selected = $("#bis_month option:selected")

    if(selected.attr("data-empty") == "1"){
        alert("No hay posts en ese mes")
        return
    }

    offset = 0
    allImages = []

    $("#bis_progress").text("Initializing scan...")
    $("#bis_stats").html("")

    // 🔒 desactivar botón
    $("#bis_start_scan").prop("disabled", true)

    // obtener total de posts
    $.post(bis_ajax.ajax_url,{

        action:'bis_get_total_posts',
        year:$("#bis_year").val(),
        month:$("#bis_month").val()

    },function(res){

        totalPosts = res.total_posts

        // si no hay posts
        if(totalPosts === 0){
            $("#bis_progress").text("No posts found")
            $("#bis_start_scan").prop("disabled", false)
            return
        }

        scanBatch()
    })

})


// =========================
// CARGAR ESTADO DE MESES
// =========================
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


// =========================
// NOMBRE DE MES
// =========================
function getMonthName(m){

    const names = [
        "",
        "January","February","March","April","May","June",
        "July","August","September","October","November","December"
    ]

    return names[m]
}


// =========================
// SCAN POR LOTES
// =========================
function scanBatch(){

    $.ajax({
        url: bis_ajax.ajax_url,
        method: "POST",
        data: {
            action:'bis_scan_batch',
            offset:offset,
            year:$("#bis_year").val(),
            month:$("#bis_month").val()
        },
        timeout: 15000,

        success: function(res){

            offset += res.batch_count

            res.images.forEach(function(img){
                allImages.push(img)
            })

            let progress = totalPosts > 0
                ? Math.round((offset / totalPosts) * 100)
                : 100

            $("#bis_progress").text(progress + "%")

            $("#bis_stats").html(
                "Posts scanned: "+offset+" / "+totalPosts+"<br>"+
                "Images analyzed: "+allImages.length
            )

            // ✅ FIN DEL SCAN
            if(res.batch_count === 0 || offset >= totalPosts){

            $("#bis_progress").text("Scan complete")

            generateExcel()

            return
        }

            scanBatch()
        },

        error: function(){

            console.log("Batch error, retrying...")

            // reintentar
            scanBatch()
        }
    })
}


// =========================
// GENERAR EXCEL
// =========================
function generateExcel(){

    $("#bis_stats").html("Generating Excel...")

    $.post(bis_ajax.ajax_url,{

        action:'bis_generate_excel',
        data: JSON.stringify(allImages)

    },function(res){

        if(res.status === 'ok'){

            $("#bis_stats").html(`
                <strong>Scan complete</strong><br><br>
                <a href="${res.files.broken}" target="_blank">Download Broken Images</a><br>
                <a href="${res.files.timeout}" target="_blank">Download Timeout Images</a>
            `)

        }else{

            $("#bis_stats").html("Error generating reports")
        }

        // 🔓 reactivar botón
        $("#bis_start_scan").prop("disabled", false)

    }).fail(function(){

        $("#bis_stats").html("Error generating reports")
        $("#bis_start_scan").prop("disabled", false)

    })

}

})