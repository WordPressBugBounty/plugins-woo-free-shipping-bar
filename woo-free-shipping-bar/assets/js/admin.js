jQuery(document).ready(function ($) {
    'use strict';
    if ($('.vi-ui.tabular.menu').length) {
        $('.vi-ui.tabular.menu .item').vi_tab({history: true, historyType: 'hash'});
    }
    if ($('.vi-ui.accordion').length) {
        $('.vi-ui.accordion:not(.wfsb-accordion-init)').addClass('wfsb-accordion-init').vi_accordion('refresh');
    }
    $('.vi-ui.checkbox:not(.wfsb-checkbox-init)').addClass('wfsb-checkbox-init').off().checkbox();
    $('.vi-ui.dropdown:not(.wfsb-dropdown-init)').addClass('wfsb-dropdown-init').off().dropdown();;

    /*Save Submit button*/
    $(document).on('click','.wfsb-submit:not(.loading)', function () {
        $(this).addClass('loading');
    });
    /*Color picker*/
    $('.color-picker').each(function () {
        $(this).css({backgroundColor: $(this).val()});
    });
    $('.color-picker').minicolors({
        change: function (value, opacity) {
            let input = $(this).parent().find('.color-picker');
            input.css({backgroundColor: value});
            switch (input.attr('name')){
                case 'bg-color':
                    $('#wfspb-top-bar').css('background-color', value);
                    break;
                case 'text-color':
                    $('#wfspb-top-bar').css('color', value);
                    break;
                case 'link-color':
                    $('#wfspb-top-bar #wfspb-main-content a').css('color', value);
                    break;
                case 'progress-text-color':
                    $('#wfspb-label').css('color', value);
                    break;
                case 'bg-color-progress':
                    $('#wfspb-progress').css('background-color', value);
                    break;
                case 'bg-current-progress':
                    $('#wfspb-current-progress').css('background-color', value);
                    break;
            }
        },
        animationSpeed: 50,
        animationEasing: 'swing',
        changeDelay: 0,
        control: 'wheel',
        defaultValue: '',
        format: 'rgb',
        hide: null,
        hideSpeed: 100,
        inline: false,
        keywords: '',
        letterCase: 'lowercase',
        opacity: true,
        position: 'bottom left',
        show: null,
        showSpeed: 100,
        theme: 'default',
        swatches: []
    });
    $('#wfspb-progress-percent').on('change', function () {
        if ($(this).prop('checked')){
            $('.'+$(this).attr('id')+'-class').show() ;
        }else {
            $('.'+$(this).attr('id')+'-class').hide();
        }
    }).trigger('change');
    $('.wfspb-enable-progress').checkbox('setting', 'onChange', function () {
        if ($('.wfspb-enable-progress').hasClass('checked')) {
            $('#wfspb-progress').removeClass('disable_progress_bar').addClass('anable_progress_bar');
        } else {
            $('#wfspb-progress').removeClass('anable_progress_bar').addClass('disable_progress_bar');
        }
    });
    $('#wfspb-font').fontselect().on('change',function () {
        var font = $(this).val().replace(/\+/g, ' ');
        $('#wfspb-top-bar').css('font-family', font);
    });

    $(document).on('click','.wfspb-progress-style',function (){
        let tmp = $(this).data('style_id')|| 1;
        $('#wfspb-progress-style-value').val(tmp).trigger('change');
        $('.wfspb-progress-style').removeClass('wfspb-progress-style-selected');
        $('.wfspb-progress-style-'+tmp).addClass('wfspb-progress-style-selected');
    });
    $(document).on('click','.wfspb-progress-position',function (){
        $('#wfspb-progress-position-value').val($(this).data('style_id')||0).trigger('change');
    });

    $(document).on('change','#wfspb-progress-position-value', function () {
        let data = $(this).val();
        $('.wfspb-progress-position').removeClass('wfspb-progress-position-selected');
        $('.wfspb-progress-position-'+data).addClass('wfspb-progress-position-selected');
        if (data == 0) {
            $('#wfspb-top-bar').removeClass('bottom_bar').addClass('top_bar');
        } else {
            $('#wfspb-top-bar').removeClass('top_bar').addClass('bottom_bar');
        }
    });

    $('.select-textalign').dropdown({
        onChange: function () {
            var text_align = $('.select-textalign').children('.text').text();
            $('#wfspb-top-bar #wfspb-main-content').css('text-align', text_align);
        }
    });

    $('.select-fontsize').dropdown({
        onChange: function () {
            var font_size = $('.select-fontsize').children('.text').text();
            $('#wfspb-top-bar #wfspb-main-content').css('font-size', font_size);
            $('#wfspb-close').css({'font-size': font_size, 'line-height': font_size});
        }
    });

    $('.select-fontsize-progress').dropdown({
        onChange: function () {
            var font_size = $('.select-fontsize-progress').children('.text').text();
            $('#wfspb-label').css('font-size', font_size);
        }
    });
});

