// Global variable so it's accessible everywhere on the page/aside
window.imgArray = [];

function ImgUpload() {
    // Make sure multiple upload__inputfile can work (if you have multiple uploaders on page)
    $('.upload__inputfile').off('change').on('change', function (e) {
        let $input = $(this);
        let imgWrap = $input.closest('.upload__box').find('.upload__img-wrap');
        let maxLength = parseInt($input.data('max_length')) || 20;
        let files = Array.from(e.target.files);

        files.forEach(function (f) {
            if (!f.type.match('image.*')) return;
            if (window.imgArray.length >= maxLength) return false;

            // Prevent duplicates (by name & size)
            let exists = window.imgArray.some(img => img.name === f.name && img.size === f.size);
            if (exists) return;

            window.imgArray.push(f);

            let reader = new FileReader();
            reader.onload = function (e) {
                let html = `<div class='upload__img-box'>
                  <div style='background-image: url(${e.target.result})'
                       data-file='${f.name}'
                       data-size='${f.size}'
                       class='img-bg'>
                    <div class='upload__img-close'></div>
                  </div>
                </div>`;
                imgWrap.append(html);
            }
            reader.readAsDataURL(f);
        });

        // Clear input value so user can reselect same file again if desired
        $input.val('');
    });

    // Remove image (preview and from imgArray)
    $('body').off('click', '.upload__img-close').on('click', '.upload__img-close', function () {
        let $box = $(this).closest('.img-bg');
        let fileName = $box.data('file');
        let fileSize = $box.data('size');
        window.imgArray = window.imgArray.filter(img => !(img.name === fileName && img.size == fileSize));
        $box.closest('.upload__img-box').remove();
    });
}

// Re-init upload when sidebar is shown or after AJAX loads, etc.
$(document).on('shown.bs.controlsidebar', ImgUpload);
$(document).ready(ImgUpload);
