<!DOCTYPE html>

<meta charset="utf-8">

<title>Dropzone simple example</title>


<!--
  DO NOT SIMPLY COPY THOSE LINES. Download the JS and CSS files from the
  latest release (https://github.com/enyo/dropzone/releases/latest), and
  host them yourself!
-->
<script src="https://rawgit.com/enyo/dropzone/master/dist/dropzone.js"></script>
<link rel="stylesheet" href="https://rawgit.com/enyo/dropzone/master/dist/dropzone.css">


<p>
  This is the most minimal example of Dropzone. The upload in this example
  doesn't work, because there is no actual server to handle the file upload.
</p>

<!-- Change /upload-target to your upload address -->
<div id="DropzoneTest" class="dropzone"></div>

<script>
    Dropzone.autoDiscover = false;

    Dropzone.options.DropzoneTest = {
        maxFiles: 1,
        paramName: "file",
        maxFilesize: 3,
        url: 'api/users/images',
        method: 'post',
        uploadMultiple: false,
        headers: {
            "X-API-KEY": '4w0cco0sswcw4ggo4s88sw0sog0gowwogk4gosgk'
        }
    };
    // or if you need to access a Dropzone somewhere else:
    var myDropzone = new Dropzone("div#DropzoneTest");
    myDropzone.on("addedfile", function(file) {
        this.on("addedfile", function(file) { alert("Added file."); });
    });

    myDropzone.on("complete", function(file) {
        console.log(file);
    });
</script>