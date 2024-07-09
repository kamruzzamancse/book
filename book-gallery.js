jQuery(document).ready(function($) {
    var page = 2;
    var currentCategory = 'all';

    // Filter books by category
    $('.category-button').on('click', function() {
        currentCategory = $(this).data('category');
        $('#book-container').empty();
        loadBooks(currentCategory, 1);
        page = 2; // reset page to 2
    });

    // Load more books
    $('#load-more-books').on('click', function() {
        loadBooks(currentCategory, page);
        page++;
    });

    function loadBooks(category, page) {
        var data = {
            action: 'load_more_books',
            page: page,
            per_page: 5,
            category: category
        };
        $.post(book_gallery_ajax.ajax_url, data, function(response) {
            if (response) {
                $('#book-container').append(response);
            } else {
                $('#load-more-books').hide();
            }
        });
    }
});
