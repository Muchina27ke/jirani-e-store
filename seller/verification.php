<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/includes/Vendor.php";
require_once dirname(__DIR__) . "/includes/Auth.php";

// Vendor session/role check
if (!isset($_SESSION['user_id']) || !$auth->hasRole('vendor')) {
  header('Location: ../auth.php?action=login');
  exit();
}

$pageTitle = 'Verification Status';

// Initialize database connection and Vendor class
$db = getDbConnection();
$vendorObj = new Vendor($db);
$currentVendorId = $_SESSION['user_id'];

// Get current vendor verification details
$vendorVerification = $vendorObj->get_verification_details($currentVendorId);
$vendorStatus = $vendorVerification['status'] ?? 'not_submitted';

ob_start();
$rejectionReason = $vendorVerification['rejection_reason'] ?? '';

// Create upload directory if it doesn't exist
$uploadDir = dirname(__DIR__) . '/uploads/vendor_documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle form submissions for verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'submit_business_info') {
    $businessName = $_POST['business_name'] ?? '';
    $businessCertUrl = '';
    
    // Handle business certificate file upload
    if (isset($_FILES['business_cert']) && $_FILES['business_cert']['error'] === UPLOAD_ERR_OK) {
      $uploadResult = handleFileUpload($_FILES['business_cert'], 'business_cert', $currentVendorId);
      if ($uploadResult['success']) {
        $businessCertUrl = $uploadResult['file_path'];
      } else {
        $_SESSION['error'] = $uploadResult['message'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
      }
    }
    
    if ($vendorObj->register_vendor_details($currentVendorId, $businessName, $businessCertUrl)) {
      $_SESSION['success'] = "Business information and certificate uploaded successfully.";
    } else {
      $_SESSION['error'] = "Failed to submit business information. Please try again.";
    }
  } elseif ($_POST['action'] === 'submit_id_document') {
    $idDocUrl = '';
    
    // Handle ID document file upload
    if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
      $uploadResult = handleFileUpload($_FILES['id_document'], 'id_document', $currentVendorId);
      if ($uploadResult['success']) {
        $idDocUrl = $uploadResult['file_path'];
      } else {
        $_SESSION['error'] = $uploadResult['message'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
      }
    } else {
      $_SESSION['error'] = "Please select an ID document to upload.";
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    }
    
    if ($vendorObj->upload_id_document($currentVendorId, $idDocUrl)) {
      $_SESSION['success'] = "ID document uploaded successfully.";
    } else {
      $_SESSION['error'] = "Failed to submit ID document. Please try again.";
    }
  }
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}

// File upload handler function
function handleFileUpload($file, $type, $vendorId) {
    global $uploadDir;
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size too large. Maximum size is 5MB.'];
    }
    
    // Generate secure filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . $vendorId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => 'uploads/vendor_documents/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file. Please try again.'];
    }
}

ob_start();
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <?php echo $_SESSION['success'];
          unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <?php echo $_SESSION['error'];
          unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <!-- Verification Status Card -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Your Verification Status</h3>
            </div>
            <div class="card-body">
              <div id="verification-status">
                <?php if ($vendorStatus === 'approved'): ?>
                  <div class="alert alert-success">
                    <h5><i class="icon fas fa-check-circle"></i> Account Approved!</h5>
                    Your vendor account has been successfully verified. You can now add and manage your products.
                  </div>
                <?php elseif ($vendorStatus === 'pending'): ?>
                  <div class="alert alert-warning">
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Verification Pending</h5>
                    Your submitted documents are currently under review. We will notify you once the process is
                    complete.
                    Please allow 1-3 business days for review.
                  </div>
                <?php elseif ($vendorStatus === 'rejected'): ?>
                  <div class="alert alert-danger">
                    <h5><i class="icon fas fa-times-circle"></i> Verification Rejected</h5>
                    Unfortunately, your verification was rejected.
                    <?php if (!empty($rejectionReason)): ?>
                      Reason: <?php echo htmlspecialchars($rejectionReason); ?><br>
                    <?php endif; ?>
                    Please review the requirements and re-submit your documents.
                  </div>
                <?php else: // not_submitted or other ?>
                  <div class="alert alert-info">
                    <h5><i class="icon fas fa-info-circle"></i> Verification Required</h5>
                    To become a verified vendor and start listing products, please submit the required documents
                    below.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Business Information Form -->
      <?php if ($vendorStatus !== 'approved'): ?>
        <div class="row">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Business Information</h3>
              </div>
              <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="submit_business_info">
                  <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" class="form-control" id="business_name" name="business_name"
                      value="<?php echo htmlspecialchars($vendorDetails['business_name'] ?? ''); ?>"
                      placeholder="Enter your business name" required>
                  </div>
                  <div class="form-group">
                    <label for="business_cert">Business Certificate</label>
                    <input type="file" class="form-control-file" id="business_cert" name="business_cert"
                      accept=".jpg,.jpeg,.png,.pdf" required>
                    <small class="form-text text-muted">
                      <i class="fas fa-info-circle"></i> Upload your business registration certificate.<br>
                      <strong>Accepted formats:</strong> JPG, PNG, PDF (Max size: 5MB)
                    </small>
                    <?php if (!empty($vendorVerification['business_doc_url'])): ?>
                      <div class="mt-2">
                        <span class="badge badge-success">✓ Certificate uploaded</span>
                        <a href="../<?php echo htmlspecialchars($vendorVerification['business_doc_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info ml-2">
                          <i class="fas fa-eye"></i> View Current
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Submit Business Information
                  </button>
                </form>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">ID Document</h3>
              </div>
              <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="submit_id_document">
                  <div class="form-group">
                    <label for="id_document">ID Document</label>
                    <input type="file" class="form-control-file" id="id_document" name="id_document"
                      accept=".jpg,.jpeg,.png,.pdf" required>
                    <small class="form-text text-muted">
                      <i class="fas fa-info-circle"></i> Upload your National ID or Passport.<br>
                      <strong>Accepted formats:</strong> JPG, PNG, PDF (Max size: 5MB)
                    </small>
                    <?php if (!empty($vendorVerification['id_doc_url'])): ?>
                      <div class="mt-2">
                        <span class="badge badge-success">✓ ID document uploaded</span>
                        <a href="../<?php echo htmlspecialchars($vendorVerification['id_doc_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info ml-2">
                          <i class="fas fa-eye"></i> View Current
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Submit ID Document
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Verification Requirements -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Verification Requirements</h3>
            </div>
            <div class="card-body">
              <div class="alert alert-info">
                <h5><i class="icon fas fa-info"></i> Document Upload Requirements:</h5>
                <div class="row">
                  <div class="col-md-6">
                    <h6><i class="fas fa-file-alt"></i> Required Documents:</h6>
                    <ul>
                      <li><strong>Business Registration Certificate</strong><br>
                          <small class="text-muted">Valid business license or registration document</small></li>
                      <li><strong>National ID or Passport</strong><br>
                          <small class="text-muted">Government-issued identification</small></li>
                    </ul>
                  </div>
                  <div class="col-md-6">
                    <h6><i class="fas fa-upload"></i> File Requirements:</h6>
                    <ul>
                      <li><strong>Formats:</strong> JPG, PNG, or PDF</li>
                      <li><strong>Size:</strong> Maximum 5MB per file</li>
                      <li><strong>Quality:</strong> Clear and readable</li>
                      <li><strong>Security:</strong> Files are stored securely</li>
                    </ul>
                  </div>
                </div>
                <div class="alert alert-warning mt-3">
                  <i class="fas fa-exclamation-triangle"></i>
                  <strong>Important:</strong> All documents must be clear and readable. Verification typically takes 1-3
                  business days.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>