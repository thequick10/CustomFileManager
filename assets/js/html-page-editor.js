jQuery(document).ready(function($) {

    // Check for load parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const loadFile = urlParams.get('load');
    if (loadFile) {
        $('#page-slug').val(loadFile);
        loadPage();
    }

    // Load page functionality
    function loadPage() {
        const pageSlug = $('#page-slug').val().trim();

        if (!pageSlug) {
            showMessage('Please enter a page slug to load', 'error');
            return;
        }

        // Show loading
        $('#load-page').prop('disabled', true).text('Loading...');

        $.ajax({
            url: htmlPageEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'load_html_page',
                page_slug: pageSlug,
                nonce: htmlPageEditor.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#html-content').val(response.data.html_content);
                    $('#page-title').val(response.data.page_title);
                    showMessage('Page loaded successfully', 'success');
                } else {
                    showMessage(response.data || 'Error loading page', 'error');
                }
            },
            error: function() {
                showMessage('Network error occurred', 'error');
            },
            complete: function() {
                $('#load-page').prop('disabled', false).text('Load Page');
            }
        });
    }

    // Save page functionality
    $('#save-page').on('click', function() {
        const pageSlug = $('#page-slug').val().trim();
        const fileExtension = $('#file-extension').val();
        const pageTitle = $('#page-title').val().trim();
        const htmlContent = $('#html-content').val();

        if (!pageSlug || !pageTitle || !htmlContent) {
            showMessage('All fields are required!', 'error');
            return;
        }

        // Show loading
        $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            url: htmlPageEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'save_html_page',
                page_slug: pageSlug,
                file_extension: fileExtension,
                page_title: pageTitle,
                html_content: htmlContent,
                nonce: htmlPageEditor.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data, 'success');
                    loadUserPages();
                } else {
                    showMessage(response.data || 'Error saving page', 'error');
                }
            },
            error: function() {
                showMessage('Network error occurred', 'error');
            },
            complete: function() {
                $('#save-page').prop('disabled', false).text('Save Page');
            }
        });
    });
    
    // Load page button click
    $('#load-page').on('click', function() {
        loadPage();
    });
    
    // Delete page functionality
    $('#delete-page').on('click', function() {
        const pageSlug = $('#page-slug').val().trim();
        
        if (!pageSlug) {
            showMessage('Please enter a page slug', 'error');
            return;
        }
        
        if (!confirm('Are you sure you want to delete this page?')) {
            return;
        }
        
        $(this).prop('disabled', true).text('Deleting...');
        
        $.ajax({
            url: htmlPageEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_html_page',
                page_slug: pageSlug,
                nonce: htmlPageEditor.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data, 'success');
                    // Clear the form
                    $('#page-slug, #page-title, #html-content').val('');
                    loadUserPages();
                } else {
                    showMessage(response.data || 'Error deleting page', 'error');
                }
            },
            error: function() {
                showMessage('Network error occurred', 'error');
            },
            complete: function() {
                $('#delete-page').prop('disabled', false).text('Delete Page');
            }
        });
    });
    
    // Preview page functionality
    $('#preview-page').on('click', function() {
        const pageSlug = $('#page-slug').val().trim();
        
        if (!pageSlug) {
            showMessage('Please enter a page slug', 'error');
            return;
        }
        
        const previewUrl = htmlPageEditor.site_url + '/custom-page/' + pageSlug;
        window.open(previewUrl, '_blank');
    });
    
    // Auto-generate slug from title
    // $('#page-title').on('input', function() {
    //     const title = $(this).val();
    //     const slug = title.toLowerCase()
    //         .replace(/[^a-z0-9\s-]/g, '')
    //         .replace(/\s+/g, '-')
    //         .replace(/-+/g, '-')
    //         .trim('-');
        
    //     if ($('#page-slug').val() === '') {
    //         $('#page-slug').val(slug);
    //     }
    // });
    // New Auto-generate slug from title
    $('#page-title').on('input', function () {
        const title = $(this).val();

        const slug = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')   // Remove invalid chars
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/-+/g, '-')            // Collapse multiple -
            .replace(/^-+|-+$/g, '');       // Trim - from start and end

        // Always update slug, or only if it's empty (you can decide)
        $('#page-slug').val(slug);
    });

    
    // Clear form functionality
    $('#clear-form').on('click', function() {
        $('#page-slug, #page-title, #html-content').val('');
        $('#save-page, #delete-page').prop('disabled', false);
        showMessage('Form cleared', 'info');
    });
    
    // Load user's pages on page load
    loadUserPages();
    
    // Function to show messages
    function showMessage(message, type) {
        const messageClass = {
            'success': 'notice-success',
            'error': 'notice-error', 
            'info': 'notice-info',
            'warning': 'notice-warning'
        }[type] || 'notice-info';
        
        const messageHtml = `<div class="notice ${messageClass} is-dismissible">
            <p>${message}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>`;
        
        $('#editor-messages').html(messageHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('#editor-messages .notice').fadeOut();
        }, 5000);
        
        // Handle manual dismiss
        $('#editor-messages').on('click', '.notice-dismiss', function() {
            $(this).closest('.notice').fadeOut();
        });
    }
    
    // Function to load user's pages
    function loadUserPages() {
        $.ajax({
            url: htmlPageEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'get_user_pages',
                nonce: htmlPageEditor.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    let pagesList = '<ul>';
                    response.data.forEach(function(page) {
                        pagesList += `<li>
                            <strong>${page.page_title}</strong>
                            (<em>${page.page_slug}</em>)
                            <br>
                            <small>
                                <a href="#" class="load-page-link" data-slug="${page.page_slug}">Edit</a> |
                                <a href="${htmlPageEditor.site_url}/custom-page/${page.page_slug}" target="_blank">View</a> |
                                <a href="#" class="copy-link" data-slug="${page.page_slug}">Copy Link</a>
                            </small>
                        </li>`;
                    });
                    pagesList += '</ul>';
                    $('#user-pages-list').html(pagesList);
                } else {
                    $('#user-pages-list').html('<p>No pages found.</p>');
                }
            }
        });
    }
    
    // Handle clicking on page links in the list
    $(document).on('click', '.load-page-link', function(e) {
        e.preventDefault();
        const slug = $(this).data('slug');
        $('#page-slug').val(slug);
        $('#load-page').trigger('click');
    });
    
    // Handle copy link functionality
    $(document).on('click', '.copy-link', function(e) {
        e.preventDefault();
        const slug = $(this).data('slug');
        const url = htmlPageEditor.site_url + '/custom-page/' + slug;

        // Use modern clipboard API if available
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                showMessage('Page URL copied to clipboard!', 'success');
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                fallbackCopy(url);
            });
        } else {
            fallbackCopy(url);
        }

        function fallbackCopy(text) {
            // Fallback for older browsers
            const tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(text).select();
            try {
                document.execCommand('copy');
                showMessage('Page URL copied to clipboard!', 'success');
            } catch (err) {
                console.error('Fallback copy failed: ', err);
                showMessage('Failed to copy URL. Please copy manually: ' + text, 'error');
            }
            tempInput.remove();
        }
    });
    
    // Add clear form button functionality
    $('<button id="clear-form" type="button">Clear Form</button>').insertAfter('#preview-page');
    
    $('#clear-form').on('click', function() {
        if (confirm('Are you sure you want to clear the form?')) {
            $('#page-slug, #page-title, #html-content').val('');
            $('#save-page, #delete-page').prop('disabled', false);
            showMessage('Form cleared', 'info');
        }
    });
    
    // Add fullscreen toggle
    $('<button id="fullscreen-toggle" type="button" class="fullscreen-toggle">Fullscreen</button>').insertAfter('#clear-form');
    
    $('#fullscreen-toggle').on('click', function() {
        const container = $('#html-page-editor-container');
        const button = $(this);
        
        if (container.hasClass('fullscreen')) {
            container.removeClass('fullscreen');
            button.text('Fullscreen');
            $('body').removeClass('fullscreen-active');
        } else {
            container.addClass('fullscreen');
            button.text('Exit Fullscreen');
            $('body').addClass('fullscreen-active');
        }
    });
    
    // ESC key to exit fullscreen
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#html-page-editor-container').hasClass('fullscreen')) {
            $('#fullscreen-toggle').trigger('click');
        }
    });
    
    // Save with Ctrl+S
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('#save-page').trigger('click');
        }
    });
    
    // Add word count and character count
    $('<div id="editor-stats" style="text-align: right; margin-top: 10px; font-size: 12px; color: #666;"></div>').insertAfter('#html-content');
    
    function updateStats() {
        const content = $('#html-content').val();
        const charCount = content.length;
        const wordCount = content.trim() === '' ? 0 : content.trim().split(/\s+/).length;
        const lineCount = content.split('\n').length;
        
        $('#editor-stats').html(`Characters: ${charCount} | Words: ${wordCount} | Lines: ${lineCount}`);
    }
    
    $('#html-content').on('input', updateStats);
    updateStats(); // Initial count
    
    // Basic syntax highlighting for HTML (simple version)
    $('#html-content').on('input', function() {
        // You could integrate a more sophisticated code editor like CodeMirror here
        const content = $(this).val();
        const lines = content.split('\n').length;
        $(this).attr('rows', Math.max(20, lines + 2));
    });
});