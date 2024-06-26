function scrollToTop() {
    window.scrollTo(0, 0);
}
$(document).scroll(function() {
    var y = $(this).scrollTop();
    if (y > 800) {
        $('#scroll-to-top').fadeIn();
    } else {
        $('#scroll-to-top').fadeOut();
    }
});

function readURL(input) {
    if (input.files && input.files[0]) {

        var reader = new FileReader();

        reader.onload = function(e) {
            $('.image-upload-wrap').hide();

            $('.file-upload-image').attr('src', e.target.result);
            $('.file-upload-content').show();

            $('.image-title').html(input.files[0].name);
        };

        reader.readAsDataURL(input.files[0]);

    } else {
        removeUpload();
    }
}

function removeUpload() {
    $('.file-upload-input').replaceWith($('.file-upload-input').clone());
    $('.file-upload-content').hide();
    $('.image-upload-wrap').show();
}

function ImageURLReader() {
    return new Promise(function(resolve, reject) {
        var reader = new FileReader();
        reader.readAsDataURL(document.getElementById('product_photos').files[0]);
        reader.onload = function() { resolve(reader.result); };
    });
}
var gallery_img = 0;
$(document).ready(function() {
    $("#uitp_gallery").click(function() {
        $("#product_photos").focus().trigger("click");
    });
    $("img").attr('alt','Jersey Swap');

    var gallery_images = new Array();
    var images = 0;
    if($("#img-gallery .col-md-3").length>0){
        images = images + $("#img-gallery .col-md-3").length;
        if(images>=3){
            $("#uitp_gallery").hide();
        }
    }
    $(document).on('change',"#product_img",function(){
        var fileUpload = this;
        if (typeof (fileUpload.files) != "undefined") {
            var size = parseFloat(fileUpload.files[0].size / 1024).toFixed(2);
            if(size>15000){
                swal(
                    "Error", 
                    "Image size is larger than 15 MB, you can optimize the images using the online tool https://tinypng.com/. Drop the Images to the box and press “download”, “view”, and lastly screen shot the images. Then upload the images to our website!",
                    "warning",{
                        'buttons' : {
                            'cancel' : 'Cancel',
                            'optimize' : {
                                'text' : 'Optimize Image',
                                'value' : 'optimize'
                            }
                        }
                    }
                ).then((value) => {
                    switch (value) {
                   
                      case "optimize":
                        window.open('https://tinypng.com/', '_blank').focus();
                        break;
                        
                      default:
                       break;
                    }
                  });
                $(this).val('');
                return;
            }
        }
    });
    $(document).on('change', "#product_photos", function() {
        var fileUpload = this;
        if (typeof (fileUpload.files) != "undefined") {
            var size = parseFloat(fileUpload.files[0].size / 1024).toFixed(2);
            if(size>15000){
                swal(
                    "Error", 
                    "Image size is larger than 15 MB, you can optimize the images using the online tool https://tinypng.com/. Drop the Images to the box and press “download”, “view”, and lastly screen shot the images. Then upload the images to our chat!",
                    "warning",{
                        'buttons' : {
                            'cancel' : 'Cancel',
                            'optimize' : {
                                'text' : 'Optimize Image',
                                'value' : 'optimize'
                            }
                        }
                    }
                ).then((value) => {
                    switch (value) {
                   
                      case "optimize":
                        window.open('https://tinypng.com/', '_blank').focus();
                        break;
                        
                      default:
                       break;
                    }
                  });
                $(this).val('');
                return;
            }
        }
        var col = document.createElement('div');
        col.classList.add('col-md-3');
        col.classList.add('col-3');

        var image_box = document.createElement('div');
        image_box.classList.add('img-box');

        var input = document.createElement('input');
        input.setAttribute("type", "hidden");
        input.setAttribute("name", "image[]");
        var img_file = ImageURLReader();
        img_file.then(function(result) {
            input.value = result;
        });

        input.style.visibility = "hidden";
        image_box.append(input);

        var img = document.createElement('img');
        img.src = URL.createObjectURL(document.getElementById('product_photos').files[0]);

        image_box.append(img);

        var btn = document.createElement('button');
        btn.classList.add('btn');
        btn.classList.add('btn-circle');
        btn.classList.add('btn-remove');

        var icon = document.createElement('i');
        icon.classList.add('fa');
        icon.classList.add('fa-times');
        btn.append(icon);

        image_box.append(btn);

        col.append(image_box);
        $("#img-gallery").append(col);
        images++;
        if($("#img-gallery .col-md-3").length>=3){
            $("#uitp_gallery").hide();
        }
    });
    $(document).on('click', '.btn-remove', function() {
        if($(this).parents('.col-md-3').find('img').data('img')!==undefined){
            var temp = "<input type='hidden' name='delete[]' value='"+$(this).parents('.col-md-3').find('img').data('img')+"'>";
            $("#delete-files").append(temp);
        }
        $(this).parents('.col-md-3').remove();
        images--;
        if(images<=3){
            $("#uitp_gallery").show();
        }
    });
    $('.owl-carousel').owlCarousel({
        loop: true,
        margin: 10,
        responsive: {
            0: {
                items: 1
            },
            600: {
                items: 3
            },
            1000: {
                items: 4
            }
        }
    });
    $('.image-upload-wrap').bind('dragover', function() {
        $('.image-upload-wrap').addClass('image-dropping');
    });
    $('.image-upload-wrap').bind('dragleave', function() {
        $('.image-upload-wrap').removeClass('image-dropping');
    });
    $("#deals-tab li a").click(function() {
        $("#deals-tab li a").removeClass("active");
        $(this).addClass("active");
        var change = $(this).data('change');
        if (change !== "false") {
            var title = $(this).data('title');
            $("#deals-table .filter-title").html(title + " Deals");
        }
    })
    $("#update_profile_photo_btn").click(function() {
        $("#avatar").trigger('click');
    })
    $("#avatar").change(function() {
        $(this).parents('form').submit();
    });
    $("#offer-form").submit(function(e){
        var amount = $("#amount").val();
        if(amount<=0 && images<=0){
            swal("Error!", "Please upload images to the gallery or create a money offer!", "error");
            e.preventDefault();
        }
        else{
            $("#post-offer-btn").closest('.modal-footer').children('button').hide();
            $("#post-offer-btn").closest('.modal-footer').append("<div class='spinner-border' role='status'><span class='sr-only'>Loading...</span></div>");
            $("#uitp_gallery").hide();
        }
    });
    $("#product-upload-form").submit(function(e){
        $("#post-listing-btn").hide();
        $("#spinner").append("<div class='spinner-border' role='status'><span class='sr-only'>Loading...</span></div>");
        $("#uitp_gallery").hide();
    });

});

Date.createFromMysql = function(mysql_string) {
    var t, result = null;

    if (typeof mysql_string === 'string') {
        t = mysql_string.split(/[- :]/);

        //when t[3], t[4] and t[5] are missing they defaults to zero
        result = new Date(t[0], t[1] - 1, t[2], t[3] || 0, t[4] || 0, t[5] || 0);
    }

    return result;
}

;(function($){
    $.fn.extend({
        donetyping: function(callback,timeout){
            timeout = timeout || 1e3; // 1 second default timeout
            var timeoutReference,
                doneTyping = function(el){
                    if (!timeoutReference) return;
                    timeoutReference = null;
                    callback.call(el);
                };
            return this.each(function(i,el){
                var $el = $(el);
                // Chrome Fix (Use keyup over keypress to detect backspace)
                // thank you @palerdot
                $el.is(':input') && $el.on('keyup keypress paste',function(e){
                    // This catches the backspace button in chrome, but also prevents
                    // the event from triggering too preemptively. Without this line,
                    // using tab/shift+tab will make the focused element fire the callback.
                    if (e.type=='keyup' && e.keyCode!=8) return;

                    // Check if timeout has been set. If it has, "reset" the clock and
                    // start over again.
                    if (timeoutReference) clearTimeout(timeoutReference);
                    timeoutReference = setTimeout(function(){
                        // if we made it here, our timeout has elapsed. Fire the
                        // callback
                        doneTyping(el);
                    }, timeout);
                }).on('blur',function(){
                    // If we can, fire the event since we're leaving the field
                    doneTyping(el);
                });
            });
        }
    });
})(jQuery);