(function($){
    'use strict';

    $(function(){
        var $tagDiv = $('#tagsdiv-cl_blog_tag');
        if (!$tagDiv.length) return;

        var $input = $('#new-tag-cl_blog_tag');
        var $addBtn = $tagDiv.find('.tagadd');

        $input.attr('placeholder', 'bijv. ecommerce, shopify').val('');
        $addBtn.val('Label toevoegen');
    });
})(jQuery);
