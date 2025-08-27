// AdminLTE and Custom JavaScript for Jirani Platform

$(document).ready(function() {
    // Initialize AdminLTE components
    initializeAdminLTE();
    
    // Initialize custom components
    initializeCustomComponents();
    
    // Initialize data tables
    initializeDataTables();
    
    // Initialize modals
    initializeModals();
    
    // Initialize form validation
    initializeFormValidation();
});

function initializeAdminLTE() {
    // Sidebar toggle functionality
    $('[data-widget="pushmenu"]').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('sidebar-open');
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Dropdown functionality
    $('.dropdown-toggle').dropdown();
}

function initializeCustomComponents() {
    // Status badge updates
    $('.status-badge').each(function() {
        const status = $(this).data('status');
        updateStatusBadge($(this), status);
    });
    
    // Confirmation dialogs
    $('.confirm-action').on('click', function(e) {
        e.preventDefault();
        const message = $(this).data('confirm') || 'Are you sure you want to perform this action?';
        if (confirm(message)) {
            window.location.href = $(this).attr('href');
        }
    });
    
    // Auto-refresh for real-time updates
    if ($('.auto-refresh').length > 0) {
        setInterval(function() {
            $('.auto-refresh').each(function() {
                const url = $(this).data('refresh-url');
                if (url) {
                    refreshContent($(this), url);
                }
            });
        }, 30000); // Refresh every 30 seconds
    }
}

function initializeDataTables() {
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    }
}

function initializeModals() {
    // Modal form submissions
    $('.modal form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const modal = form.closest('.modal');
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method') || 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    modal.modal('hide');
                    showAlert('success', response.message || 'Operation completed successfully');
                    if (response.reload) {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    showAlert('danger', response.message || 'An error occurred');
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while processing your request');
            }
        });
    });
}

function initializeFormValidation() {
    // Basic form validation
    $('form[data-validate="true"]').on('submit', function(e) {
        const form = $(this);
        let isValid = true;
        
        // Check required fields
        form.find('[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Check email fields
        form.find('input[type="email"]').each(function() {
            const email = $(this).val().trim();
            if (email && !isValidEmail(email)) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showAlert('warning', 'Please fill in all required fields correctly');
        }
    });
}

function updateStatusBadge(element, status) {
    element.removeClass('badge-primary badge-secondary badge-success badge-danger badge-warning badge-info');
    
    switch (status) {
        case 'pending':
            element.addClass('badge-warning');
            break;
        case 'approved':
        case 'active':
        case 'completed':
        case 'delivered':
            element.addClass('badge-success');
            break;
        case 'rejected':
        case 'cancelled':
        case 'failed':
            element.addClass('badge-danger');
            break;
        case 'processing':
        case 'confirmed':
            element.addClass('badge-info');
            break;
        case 'shipped':
            element.addClass('badge-primary');
            break;
        default:
            element.addClass('badge-secondary');
    }
}

function refreshContent(element, url) {
    $.get(url, function(data) {
        element.html(data);
    }).fail(function() {
        console.log('Failed to refresh content from: ' + url);
    });
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('.content .container-fluid').prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// AJAX setup for CSRF protection
$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
            const token = $('meta[name=csrf-token]').attr('content');
            if (token) {
                xhr.setRequestHeader("X-CSRF-TOKEN", token);
            }
        }
    }
});

// Utility functions for common operations
const JiraniAdmin = {
    // Update order status
    updateOrderStatus: function(orderId, status) {
        $.post('/api/orders/update-status', {
            order_id: orderId,
            status: status
        }, function(response) {
            if (response.success) {
                showAlert('success', 'Order status updated successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', response.message || 'Failed to update order status');
            }
        });
    },
    
    // Approve/reject vendor verification
    updateVerificationStatus: function(vendorId, status, reason = '') {
        $.post('/api/verifications/update-status', {
            vendor_id: vendorId,
            status: status,
            reason: reason
        }, function(response) {
            if (response.success) {
                showAlert('success', 'Verification status updated successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', response.message || 'Failed to update verification status');
            }
        });
    },
    
    // Release escrow payment
    releaseEscrowPayment: function(orderId, recipient) {
        if (confirm('Are you sure you want to release the escrow payment?')) {
            $.post('/api/escrow/release', {
                order_id: orderId,
                recipient: recipient
            }, function(response) {
                if (response.success) {
                    showAlert('success', 'Escrow payment released successfully');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', response.message || 'Failed to release escrow payment');
                }
            });
        }
    }
};

// Make JiraniAdmin globally available
window.JiraniAdmin = JiraniAdmin;

