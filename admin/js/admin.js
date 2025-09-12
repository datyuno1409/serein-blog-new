// AdminLTE3 Custom JavaScript

$(document).ready(function() {
    // Initialize DataTables with custom styling
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "pageLength": 10,
            "language": {
                "search": "Tìm kiếm:",
                "lengthMenu": "Hiển thị _MENU_ mục",
                "info": "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                "infoEmpty": "Hiển thị 0 đến 0 của 0 mục",
                "infoFiltered": "(lọc từ _MAX_ tổng số mục)",
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Tiếp",
                    "previous": "Trước"
                },
                "emptyTable": "Không có dữ liệu trong bảng",
                "zeroRecords": "Không tìm thấy kết quả phù hợp"
            },
            "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                   '<"row"<"col-sm-12"tr>>' +
                   '<"row"<"col-sm-5"i><"col-sm-7"p>>'
        });
    }

    // Initialize tooltips
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Initialize popovers
    if ($.fn.popover) {
        $('[data-toggle="popover"]').popover();
    }

    // Form validation
    $('.needs-validation').on('submit', function(e) {
        if (this.checkValidity() === false) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Auto-hide alerts after 5 seconds
    $('.alert').delay(5000).fadeOut('slow');

    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url');
        const title = $(this).data('title') || 'Xác nhận xóa';
        const message = $(this).data('message') || 'Bạn có chắc chắn muốn xóa mục này?';
        
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                if (url) {
                    window.location.href = url;
                } else {
                    $(this).closest('form').submit();
                }
            }
        });
    });

    // Loading overlay
    function showLoading() {
        $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
    }

    function hideLoading() {
        $('.loading-overlay').remove();
    }

    // AJAX form submission
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const url = form.attr('action');
        const method = form.attr('method') || 'POST';
        const formData = new FormData(this);

        showLoading();

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    Swal.fire({
                        title: 'Thành công!',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi xử lý yêu cầu.',
                    icon: 'error'
                });
            }
        });
    });

    // Color picker initialization
    if ($.fn.colorpicker) {
        $('.color-picker').colorpicker({
            format: 'hex',
            component: '.input-group-append'
        });
    }

    // File upload preview
    $('.file-input').on('change', function() {
        const file = this.files[0];
        const preview = $(this).siblings('.file-preview');
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.html(`<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">`);
            };
            reader.readAsDataURL(file);
        } else {
            preview.html(`<p class="text-muted">File: ${file ? file.name : 'Không có file nào được chọn'}</p>`);
        }
    });

    // Sortable lists
    if (typeof Sortable !== 'undefined') {
        $('.sortable-list').each(function() {
            new Sortable(this, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    const order = [];
                    $(evt.to).find('[data-id]').each(function() {
                        order.push($(this).data('id'));
                    });
                    
                    // Send order to server
                    const updateUrl = $(evt.to).data('update-url');
                    if (updateUrl) {
                        $.post(updateUrl, {
                            action: 'update_order',
                            order: order
                        });
                    }
                }
            });
        });
    }

    // Auto-save drafts
    let autoSaveTimer;
    $('.auto-save').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            saveDraft();
        }, 2000);
    });

    function saveDraft() {
        const form = $('.auto-save').closest('form');
        const formData = new FormData(form[0]);
        formData.append('action', 'save_draft');

        $.ajax({
            url: 'save_draft.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('.draft-status').text('Đã lưu nháp lúc ' + new Date().toLocaleTimeString());
                }
            }
        });
    }

    // Load draft
    $('.load-draft').on('click', function() {
        const type = $(this).data('type');
        
        $.post('load_draft.php', {
            action: 'load_draft',
            type: type
        }, function(response) {
            if (response.success && response.data) {
                Object.keys(response.data).forEach(key => {
                    const input = $(`[name="${key}"]`);
                    if (input.length) {
                        if (input.is('textarea') && typeof CKEDITOR !== 'undefined') {
                            CKEDITOR.instances[input.attr('id')]?.setData(response.data[key]);
                        } else {
                            input.val(response.data[key]);
                        }
                    }
                });
                
                Swal.fire({
                    title: 'Thành công!',
                    text: 'Đã tải nháp thành công.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });

    // Clear draft
    $('.clear-draft').on('click', function() {
        const type = $(this).data('type');
        
        Swal.fire({
            title: 'Xác nhận',
            text: 'Bạn có chắc chắn muốn xóa nháp này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('clear_draft.php', {
                    action: 'clear_draft',
                    type: type
                }, function(response) {
                    if (response.success) {
                        $('.draft-status').text('');
                        Swal.fire({
                            title: 'Thành công!',
                            text: 'Đã xóa nháp.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    });

    // Toggle sidebar
    $('.sidebar-toggle').on('click', function() {
        $('body').toggleClass('sidebar-collapse');
    });

    // Search functionality
    $('.search-input').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const searchTarget = $(this).data('target');
        
        $(searchTarget).each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchTerm) > -1);
        });
    });

    // Copy to clipboard
    $('.copy-btn').on('click', function() {
        const text = $(this).data('copy') || $(this).prev('input').val();
        
        navigator.clipboard.writeText(text).then(() => {
            $(this).tooltip('dispose').tooltip({
                title: 'Đã sao chép!',
                trigger: 'manual'
            }).tooltip('show');
            
            setTimeout(() => {
                $(this).tooltip('hide');
            }, 1000);
        });
    });

    // Initialize CKEditor if available
    if (typeof CKEDITOR !== 'undefined') {
        $('.ckeditor').each(function() {
            CKEDITOR.replace(this.id, {
                height: 300,
                filebrowserUploadUrl: 'upload.php',
                toolbar: [
                    ['Bold', 'Italic', 'Underline'],
                    ['NumberedList', 'BulletedList'],
                    ['Link', 'Unlink'],
                    ['Image', 'Table'],
                    ['Source']
                ]
            });
        });
    }
});

// Global functions
window.showAlert = function(type, message, title = '') {
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        timer: type === 'success' ? 2000 : null,
        showConfirmButton: type !== 'success'
    });
};

window.confirmAction = function(callback, title = 'Xác nhận', message = 'Bạn có chắc chắn?') {
    Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
};

// Loading overlay styles
const loadingCSS = `
<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
</style>
`;

$('head').append(loadingCSS);