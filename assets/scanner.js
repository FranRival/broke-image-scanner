jQuery(document).ready(function($){

let offset = 0;
let totalPosts = 0;

loadMonthsStatus();

$("#bis_year").change(function(){
    loadMonthsStatus();
});

$("#bis_start_scan").click(function(){

    offset = 0;

    $("#bis_progress").text("Initializing...");
    $("#bis_stats").html("");
    $("#bis_start_scan").prop("disabled", true);

    // 🔥 NUEVO: si hay TAG seleccionado, ignorar mes automáticamente
    if($("#bis_tag").val() !== ""){
        $("#bis_month").val("");
    }

    $.post(bis_ajax.ajax_url, {
        action: 'bis_get_total_posts',
        year: $("#bis_year").val(),
        month: $("#bis_month").val(),

        // 🔥 NUEVO (UBICACIÓN 1): enviar TAG al backend
        tag: $("#bis_tag").val()

    }, function(res){

        totalPosts = parseInt(res.total_posts);

        if(totalPosts > 0){
            scanBatch();
        } else {
            $("#bis_progress").text("No posts found.");
            $("#bis_start_scan").prop("disabled", false);
        }

    });

});


function scanBatch(){

    $.post(bis_ajax.ajax_url, {
        action: 'bis_scan_batch',
        offset: offset,
        year: $("#bis_year").val(),
        month: $("#bis_month").val(),

        // 🔥 NUEVO (UBICACIÓN 2): enviar TAG también en cada batch
        tag: $("#bis_tag").val()

    }, function(res){

        offset += res.batch_count;

        let progress = Math.round((offset / totalPosts) * 100);

        $("#bis_progress").text(progress + "%");
        $("#bis_stats").html("Processed: " + offset + " / " + totalPosts);

        console.log("FINAL CHECK:", offset, totalPosts, res.batch_count);

        // 🔥 FIX CLAVE
        if(res.batch_count > 0 && offset < totalPosts){
            scanBatch();
        } else {
            $("#bis_progress").text("Generating reports...");
            console.log("CALLING GENERATE EXCEL");
            generateExcel();
        }

    }).fail(function(){
        console.log("Batch error, retrying...");
        setTimeout(scanBatch, 2000);
    });

}



function generateExcel(){

    console.log("AJAX REQUEST → bis_finalize");

    $.post(bis_ajax.ajax_url, {
        action: 'bis_finalize',
        nonce: bis_ajax.nonce,
        tag: $("#bis_tag").val()
    }, function(res){

        console.log("EXCEL RESPONSE:", res);

        if(res.status === 'ok'){

            $("#bis_stats").html(`
                <strong>Done!</strong><br>
                <a href="${res.files.broken}" class="button" target="_blank">Download Broken</a><br>
                <a href="${res.files.timeout}" class="button" target="_blank">Download Timeouts</a>
            `);

        } else {

            $("#bis_stats").html("Error: " + (res.msg || "Unknown"));

        }

        $("#bis_start_scan").prop("disabled", false);

    }).fail(function(err){

        console.log("EXCEL ERROR:", err);

        $("#bis_stats").html("AJAX FAIL (WAF probablemente)");
        $("#bis_start_scan").prop("disabled", false);

    });
}





function loadMonthsStatus(){

    $.post(bis_ajax.ajax_url, {
        action: 'bis_get_months_with_posts',
        year: $("#bis_year").val()
    }, function(res){

        $("#bis_month option").each(function(){

            let val = $(this).val();
            if(val === "") return;

            $(this).text(val in res ? "Month " + val + " (" + res[val] + ")" : "Month " + val + " (0)");

        });

    });

}

});