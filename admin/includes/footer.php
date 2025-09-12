</div>
    <!-- /.content-wrapper -->
    
    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; <?= date('Y') ?> <a href="../index.html" target="_blank">Serein</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
    
    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<!-- Bootstrap Color Picker -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-colorpicker@3.4.0/dist/js/bootstrap-colorpicker.min.js"></script>

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/4.19.1/standard/ckeditor.js"></script>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Custom Admin JS -->
<script src="js/admin.js"></script>

<!-- Custom Admin JS -->
<script>
$(document).ready(function() {
    // Initialize DataTables with custom settings
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "pageLength": 25,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Load sidebar counts
    loadSidebarCounts();
    
    // Load recent messages for navbar dropdown
    loadRecentMessages();
    
    // Auto-refresh counts every 30 seconds
    setInterval(function() {
        loadSidebarCounts();
        loadRecentMessages();
    }, 30000);
    
    // Form validation
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        if (submitBtn.length) {
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        }
    });
    
    // Auto-save drafts for content forms
    let autoSaveTimer;
    $('textarea[name="content"], input[name="title"]').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            saveDraft();
        }, 2000);
    });
    
    // Character counters for text inputs
    $('input[maxlength], textarea[maxlength]').each(function() {
        const maxLength = $(this).attr('maxlength');
        const counter = $('<small class="text-muted float-right">0/' + maxLength + '</small>');
        $(this).after(counter);
        
        $(this).on('input', function() {
            const currentLength = $(this).val().length;
            counter.text(currentLength + '/' + maxLength);
            
            if (currentLength > maxLength * 0.9) {
                counter.removeClass('text-muted').addClass('text-warning');
            } else if (currentLength === maxLength) {
                counter.removeClass('text-warning').addClass('text-danger');
            } else {
                counter.removeClass('text-warning text-danger').addClass('text-muted');
            }
        });
    });
    
    // Tooltip initialization
    $('[data-toggle="tooltip"]').tooltip();
    
    // Popover initialization
    $('[data-toggle="popover"]').popover();
    
    // File upload preview
    $('input[type="file"]').on('change', function() {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $(this).siblings('.image-preview');
                if (preview.length === 0) {
                    $(this).after('<div class="image-preview mt-2"><img src="" class="img-thumbnail" style="max-width: 200px;"></div>');
                }
                $(this).siblings('.image-preview').find('img').attr('src', e.target.result);
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });
});

// Load sidebar counts
function loadSidebarCounts() {
    fetch('../api.php?action=get_counts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update articles count
                const articlesCount = document.getElementById('articlesCount');
                if (articlesCount) {
                    articlesCount.textContent = data.data.articles || 0;
                }
                
                // Update projects count
                const projectsCount = document.getElementById('projectsCount');
                if (projectsCount) {
                    projectsCount.textContent = data.data.projects || 0;
                }
                
                // Update contacts count (unread only)
                const contactsCount = document.getElementById('contactsCount');
                const unreadCount = document.getElementById('unreadCount');
                if (data.data.unread_contacts > 0) {
                    if (contactsCount) {
                        contactsCount.textContent = data.data.unread_contacts;
                        contactsCount.style.display = 'inline';
                    }
                    if (unreadCount) {
                        unreadCount.textContent = data.data.unread_contacts;
                        unreadCount.style.display = 'inline';
                    }
                } else {
                    if (contactsCount) contactsCount.style.display = 'none';
                    if (unreadCount) unreadCount.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error loading counts:', error));
}

// Load recent messages for navbar dropdown
function loadRecentMessages() {
    fetch('../api.php?action=get_recent_messages&limit=5')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('recentMessages');
            if (container && data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<div class="dropdown-item text-center text-muted">No recent messages</div>';
                } else {
                    let html = '';
                    data.data.forEach(message => {
                        const date = new Date(message.created_at).toLocaleDateString();
                        const preview = message.message.substring(0, 50) + (message.message.length > 50 ? '...' : '');
                        const readClass = message.is_read ? '' : 'font-weight-bold';
                        
                        html += `
                            <a href="contacts.php?filter=all&search=${encodeURIComponent(message.email)}" class="dropdown-item ${readClass}">
                                <div class="media">
                                    <div class="media-body">
                                        <h3 class="dropdown-item-title">
                                            ${message.name}
                                            ${!message.is_read ? '<span class="badge badge-warning float-right">New</span>' : ''}
                                        </h3>
                                        <p class="text-sm">${preview}</p>
                                        <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> ${date}</p>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    container.innerHTML = html;
                }
            }
        })
        .catch(error => console.error('Error loading recent messages:', error));
}

// Save draft function
function saveDraft() {
    const form = document.querySelector('form');
    if (!form) return;
    
    const formData = new FormData(form);
    const draftData = {};
    
    for (let [key, value] of formData.entries()) {
        if (key !== 'csrf_token' && key !== 'action') {
            draftData[key] = value;
        }
    }
    
    const draftKey = 'draft_' + window.location.pathname.split('/').pop().replace('.php', '');
    localStorage.setItem(draftKey, JSON.stringify(draftData));
    
    // Show draft saved indicator
    showDraftSaved();
}

// Load draft function
function loadDraft() {
    const draftKey = 'draft_' + window.location.pathname.split('/').pop().replace('.php', '');
    const draftData = localStorage.getItem(draftKey);
    
    if (draftData) {
        try {
            const data = JSON.parse(draftData);
            Object.keys(data).forEach(key => {
                const element = document.querySelector(`[name="${key}"]`);
                if (element) {
                    element.value = data[key];
                }
            });
            
            // Show draft loaded indicator
            showDraftLoaded();
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }
}

// Clear draft function
function clearDraft() {
    const draftKey = 'draft_' + window.location.pathname.split('/').pop().replace('.php', '');
    localStorage.removeItem(draftKey);
}

// Show draft saved indicator
function showDraftSaved() {
    const indicator = document.getElementById('draftIndicator');
    if (indicator) {
        indicator.innerHTML = '<i class="fas fa-check text-success"></i> Draft saved';
        setTimeout(() => {
            indicator.innerHTML = '';
        }, 2000);
    }
}

// Show draft loaded indicator
function showDraftLoaded() {
    const indicator = document.getElementById('draftIndicator');
    if (indicator) {
        indicator.innerHTML = '<i class="fas fa-info-circle text-info"></i> Draft loaded';
        setTimeout(() => {
            indicator.innerHTML = '';
        }, 3000);
    }
}

// Security: Disable right-click and F12 in production
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            e.preventDefault();
        }
    });
}

// Session timeout warning
let sessionTimeout;
function resetSessionTimeout() {
    clearTimeout(sessionTimeout);
    sessionTimeout = setTimeout(function() {
        if (confirm('Your session is about to expire. Click OK to extend your session.')) {
            fetch('dashboard.php', { method: 'HEAD' })
                .then(() => resetSessionTimeout())
                .catch(() => window.location.href = 'index.php');
        } else {
            window.location.href = 'logout.php';
        }
    }, 25 * 60 * 1000); // 25 minutes
}

// Reset timeout on user activity
document.addEventListener('click', resetSessionTimeout);
document.addEventListener('keypress', resetSessionTimeout);
resetSessionTimeout();

// Hide preloader when page is fully loaded
$(window).on('load', function() {
    $('.preloader').fadeOut('slow');
});

// Fallback: Hide preloader after 3 seconds if window.load doesn't fire
setTimeout(function() {
    $('.preloader').fadeOut('slow');
}, 3000);
</script>

<?php if (isset($additionalJS)): ?>
    <?= $additionalJS ?>
<?php endif; ?>

</body>
</html>