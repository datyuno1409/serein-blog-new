<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';
require_once '../config/SearchSanitizer.php';

// Check if user is logged in
if (!AdminAuth::isLoggedIn()) {
    header('Location: index.php');
    exit();
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminAuth::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            $db = getDB();
            
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'mark_read':
                        $contactId = (int)($_POST['contact_id'] ?? 0);
                        if ($contactId > 0) {
                            $stmt = $db->prepare("UPDATE contacts SET is_read = 1, updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$contactId]);
                            $message = 'Message marked as read';
                            AdminAuth::logActivity('contact_read', "Marked contact #$contactId as read");
                        }
                        break;
                        
                    case 'mark_unread':
                        $contactId = (int)($_POST['contact_id'] ?? 0);
                        if ($contactId > 0) {
                            $stmt = $db->prepare("UPDATE contacts SET is_read = 0, updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$contactId]);
                            $message = 'Message marked as unread';
                            AdminAuth::logActivity('contact_unread', "Marked contact #$contactId as unread");
                        }
                        break;
                        
                    case 'delete':
                        $contactId = (int)($_POST['contact_id'] ?? 0);
                        if ($contactId > 0) {
                            $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
                            $stmt->execute([$contactId]);
                            $message = 'Message deleted successfully';
                            AdminAuth::logActivity('contact_delete', "Deleted contact #$contactId");
                        }
                        break;
                        
                    case 'bulk_action':
                        $bulkAction = $_POST['bulk_action'] ?? '';
                        $selectedIds = $_POST['selected_contacts'] ?? [];
                        
                        if (!empty($selectedIds) && !empty($bulkAction)) {
                            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                            
                            switch ($bulkAction) {
                                case 'mark_read':
                                    $stmt = $db->prepare("UPDATE contacts SET is_read = 1, updated_at = NOW() WHERE id IN ($placeholders)");
                                    $stmt->execute($selectedIds);
                                    $message = count($selectedIds) . ' messages marked as read';
                                    break;
                                    
                                case 'mark_unread':
                                    $stmt = $db->prepare("UPDATE contacts SET is_read = 0, updated_at = NOW() WHERE id IN ($placeholders)");
                                    $stmt->execute($selectedIds);
                                    $message = count($selectedIds) . ' messages marked as unread';
                                    break;
                                    
                                case 'delete':
                                    $stmt = $db->prepare("DELETE FROM contacts WHERE id IN ($placeholders)");
                                    $stmt->execute($selectedIds);
                                    $message = count($selectedIds) . ' messages deleted';
                                    break;
                            }
                            
                            AdminAuth::logActivity('contact_bulk', "Bulk action: $bulkAction on " . count($selectedIds) . ' contacts');
                        }
                        break;
                }
            }
            
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Pagination and filtering
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$filter = in_array($_GET['filter'] ?? 'all', ['all', 'read', 'unread']) ? $_GET['filter'] : 'all';
$search = SearchSanitizer::sanitizeSearchQuery($_GET['search'] ?? '');

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($filter === 'unread') {
    $whereConditions[] = 'is_read = 0';
} elseif ($filter === 'read') {
    $whereConditions[] = 'is_read = 1';
}

if (!empty($search)) {
    list($searchCondition, $searchParams) = SearchSanitizer::buildSearchConditions($search, ['name', 'email', 'subject', 'message']);
    $whereConditions[] = $searchCondition;
    $params = array_merge($params, $searchParams);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    $db = getDB();
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM contacts $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalContacts = $stmt->fetchColumn();
    $totalPages = ceil($totalContacts / $limit);
    
    // Get contacts
    $query = "SELECT * FROM contacts $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll();
    
    // Get stats
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
        FROM contacts";
    $stats = $db->query($statsQuery)->fetch();
    
} catch (Exception $e) {
    $error = 'Failed to load contacts: ' . $e->getMessage();
    $contacts = [];
    $stats = ['total' => 0, 'unread' => 0, 'read' => 0, 'today' => 0];
    $totalPages = 0;
}

?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Contact Messages</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Contact Messages</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

<section class="content">
    <div class="container-fluid">
        
        <?php if ($message): ?>
            <?= showSuccessMessage($message) ?>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <?= showErrorMessage($error) ?>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row mb-3">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $stats['total'] ?></h3>
                        <p>Total Messages</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $stats['unread'] ?></h3>
                        <p>Unread Messages</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $stats['read'] ?></h3>
                        <p>Read Messages</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $stats['today'] ?></h3>
                        <p>Today's Messages</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Contact Messages</h3>
                
                <div class="card-tools">
                    <!-- Search Form -->
                    <form method="GET" class="form-inline">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search messages..." value="<?= htmlspecialchars($search) ?>">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card-body p-0">
                <!-- Filter Tabs -->
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" 
                               href="?filter=all<?= $search ? '&search=' . urlencode($search) : '' ?>">All (<?= $stats['total'] ?>)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $filter === 'unread' ? 'active' : '' ?>" 
                               href="?filter=unread<?= $search ? '&search=' . urlencode($search) : '' ?>">Unread (<?= $stats['unread'] ?>)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $filter === 'read' ? 'active' : '' ?>" 
                               href="?filter=read<?= $search ? '&search=' . urlencode($search) : '' ?>">Read (<?= $stats['read'] ?>)</a>
                        </li>
                    </ul>
                </div>
                
                <?php if (!empty($contacts)): ?>
                    <form id="bulkForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= AdminAuth::generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="bulk_action">
                        
                        <!-- Bulk Actions -->
                        <div class="p-3 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="form-inline">
                                        <select name="bulk_action" class="form-control form-control-sm mr-2">
                                            <option value="">Bulk Actions</option>
                                            <option value="mark_read">Mark as Read</option>
                                            <option value="mark_unread">Mark as Unread</option>
                                            <option value="delete">Delete</option>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="executeBulkAction()">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <small class="text-muted">
                                        Showing <?= count($contacts) ?> of <?= $totalContacts ?> messages
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th width="50">Status</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                        <tr class="<?= !$contact['is_read'] ? 'table-warning' : '' ?>">
                                            <td>
                                                <input type="checkbox" name="selected_contacts[]" value="<?= $contact['id'] ?>">
                                            </td>
                                            <td>
                                                <?php if ($contact['is_read']): ?>
                                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning"><i class="fas fa-envelope"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($contact['name']) ?></strong>
                                            </td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($contact['email']) ?>">
                                                    <?= htmlspecialchars($contact['email']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($contact['subject']) ?></strong>
                                            </td>
                                            <td>
                                                <div class="message-preview">
                                                    <?= htmlspecialchars(substr($contact['message'], 0, 100)) ?>
                                                    <?= strlen($contact['message']) > 100 ? '...' : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('M j, Y g:i A', strtotime($contact['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info" 
                                                            onclick="viewMessage(<?= $contact['id'] ?>)" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if (!$contact['is_read']): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= AdminAuth::generateCSRFToken() ?>">
                                                            <input type="hidden" name="action" value="mark_read">
                                                            <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                                                            <button type="submit" class="btn btn-success" title="Mark as Read">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= AdminAuth::generateCSRFToken() ?>">
                                                            <input type="hidden" name="action" value="mark_unread">
                                                            <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                                                            <button type="submit" class="btn btn-warning" title="Mark as Unread">
                                                                <i class="fas fa-envelope"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-danger" 
                                                            onclick="deleteMessage(<?= $contact['id'] ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer">
                            <nav>
                                <ul class="pagination pagination-sm m-0 float-right">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&filter=<?= $filter ?><?= $search ? '&search=' . urlencode($search) : '' ?>">&laquo;</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&filter=<?= $filter ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&filter=<?= $filter ?><?= $search ? '&search=' . urlencode($search) : '' ?>">&raquo;</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No messages found</h5>
                        <p class="text-muted">No contact messages match your current filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</section>
</div>

<!-- Message View Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Contact Message</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="messageContent">
                    <!-- Message content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" id="replyButton" class="btn btn-primary">
                    <i class="fas fa-reply"></i> Reply via Email
                </a>
            </div>
        </div>
    </div>
</div>



<script>
// Loading functions
function showLoading() {
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoading() {
    Swal.close();
}

// Select all checkbox
const selectAllCheckbox = document.getElementById('selectAll');
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="selected_contacts[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
}

// Bulk actions
function executeBulkAction() {
    const bulkAction = document.querySelector('select[name="bulk_action"]').value;
    const selectedCheckboxes = document.querySelectorAll('input[name="selected_contacts[]"]:checked');
    
    if (!bulkAction) {
        Swal.fire({
            icon: 'warning',
            title: 'No Action Selected',
            text: 'Please select an action'
        });
        return;
    }
    
    if (selectedCheckboxes.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Messages Selected',
            text: 'Please select at least one message'
        });
        return;
    }
    
    if (bulkAction === 'delete') {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedCheckboxes.length} message(s). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                document.getElementById('bulkForm').submit();
            }
        });
    } else {
        showLoading();
        document.getElementById('bulkForm').submit();
    }
}

// View message
function viewMessage(contactId) {
    showLoading();
    
    fetch(`../api.php?action=get_contact&id=${contactId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                const contact = data.data;
                const messageContent = document.getElementById('messageContent');
                
                messageContent.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Name:</strong> ${contact.name}<br>
                            <strong>Email:</strong> <a href="mailto:${contact.email}">${contact.email}</a><br>
                            <strong>Subject:</strong> ${contact.subject}<br>
                            <strong>Date:</strong> ${new Date(contact.created_at).toLocaleString()}
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> 
                            <span class="badge badge-${contact.is_read ? 'success' : 'warning'}">
                                ${contact.is_read ? 'Read' : 'Unread'}
                            </span><br>
                            <strong>Message ID:</strong> ${contact.message_id || 'N/A'}
                        </div>
                    </div>
                    <hr>
                    <div>
                        <strong>Message:</strong><br>
                        <div class="border p-3 mt-2" style="background-color: #f8f9fa; white-space: pre-wrap;">${contact.message}</div>
                    </div>
                `;
                
                // Set reply button
                const replyButton = document.getElementById('replyButton');
                const subject = encodeURIComponent('Re: ' + contact.subject);
                const body = encodeURIComponent(`\n\n--- Original Message ---\nFrom: ${contact.name} <${contact.email}>\nDate: ${new Date(contact.created_at).toLocaleString()}\nSubject: ${contact.subject}\n\n${contact.message}`);
                replyButton.href = `mailto:${contact.email}?subject=${subject}&body=${body}`;
                
                $('#messageModal').modal('show');
                
                // Mark as read if not already
                if (!contact.is_read) {
                    markAsRead(contactId);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load message'
                });
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load message'
            });
        });
}

// Mark as read
function markAsRead(contactId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= AdminAuth::generateCSRFToken() ?>">
        <input type="hidden" name="action" value="mark_read">
        <input type="hidden" name="contact_id" value="${contactId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Delete message
function deleteMessage(contactId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You are about to delete this message. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= AdminAuth::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="contact_id" value="${contactId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Auto-refresh unread count every 30 seconds
setInterval(function() {
    fetch('../api.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update unread count in navigation if exists
                const unreadBadge = document.querySelector('.nav-link[href*="contacts"] .badge');
                if (unreadBadge && data.count > 0) {
                    unreadBadge.textContent = data.count;
                    unreadBadge.style.display = 'inline';
                } else if (unreadBadge) {
                    unreadBadge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error updating unread count:', error));
}, 30000);
</script>

<?php require_once 'includes/footer.php'; ?>