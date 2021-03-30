$("#profileImage").click(function(e) {
    $("#imageUpload").click();
});

function fasterPreview(uploader) {
    if (uploader.files && uploader.files[0]) {
        $('#profileImage').attr('src',
            window.URL.createObjectURL(uploader.files[0]));
    }
}

$("#imageUpload").change(function() {
    fasterPreview(this);
});

input.onmouseover(function(event) {
    let target = event.target;
    document.getElementById("search-panel").style.height = '200px';
    text.value += "mouseover " + target.tagName + "\n";
    text.scrollTop = text.scrollHeight;
});

input.onmouseout(function(event) {
    let target = event.target;
    document.getElementById("search-panel").style.height = '80px';
    text.value += "mouseout " + target.tagName + "\n";
    text.scrollTop = text.scrollHeight;
});