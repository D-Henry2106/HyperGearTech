<?php

/**
 * ============================================
 * User Profile Page
 * ============================================
 */
$page_title = 'My Profile';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = $conn->real_escape_string(trim($_POST['first_name']));
    $last  = $conn->real_escape_string(trim($_POST['last_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $addr  = $conn->real_escape_string(trim($_POST['address']));
    $city  = $conn->real_escape_string(trim($_POST['city']));

    // Validate phone
    if (!empty($phone) && !preg_match('/^\d{10}$/', $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    }

    // Update password if provided
    $password_sql = '';
    if (!$error && !empty($_POST['new_password'])) {
        if (empty($_POST['current_password'])) {
            $error = 'Please enter your current password to change it.';
        } elseif (strlen($_POST['new_password']) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            // Verify current password
            $check = $conn->query("SELECT password FROM users WHERE id = $user_id")->fetch_assoc();
            if (!password_verify($_POST['current_password'], $check['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $password_sql = ", password = '$hashed'";
            }
        }
    }

    if (!$error) {
        $conn->query("UPDATE users SET first_name='$first', last_name='$last', phone='$phone', address='$addr', city='$city' $password_sql WHERE id=$user_id");
        $_SESSION['user_name'] = "$first $last";
        $message = 'Profile updated successfully!';
    }
}

$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';
?>

<section class="page-header py-4">
    <div class="container">
        <h2 class="fw-bold text-white mb-0"><i class="fas fa-user me-2"></i>My Profile</h2>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($message): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $message ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm scroll-reveal">
                    <div class="card-body p-5">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">First Name</label>
                                    <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($user['first_name']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($user['last_name']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Email <small class="text-muted">(cannot change)</small></label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phone * <small class="text-muted">(exactly 10 digits)</small></label>
                                    <input type="text" name="phone" class="form-control" required maxlength="10" minlength="10" pattern="\d{10}" title="Phone number must be exactly 10 digits" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">City</label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <hr>
                                    <h6 class="fw-bold">Change Password <small class="text-muted">(leave blank to keep current)</small></h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="New password (min 6 characters)">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg hg-btn-glow"><i class="fas fa-save me-2"></i>Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('input[name="phone"]').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            validatePhone(this);
        });
        input.addEventListener('blur', function() {
            validatePhone(this);
        });
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = text.replace(/[^0-9]/g, '').substring(0, 10);
            validatePhone(this);
        });
    });

    function validatePhone(input) {
        var errorId = input.getAttribute('data-error-id') || 'phone-error';
        var errorEl = document.getElementById(errorId);
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.id = errorId;
            errorEl.className = 'text-danger small mt-1';
            input.parentNode.appendChild(errorEl);
            input.setAttribute('data-error-id', errorId);
        }
        if (input.value.length === 0) {
            errorEl.textContent = 'Phone number is required.';
            input.classList.add('is-invalid');
        } else if (input.value.length < 10) {
            errorEl.textContent = 'Phone number must be exactly 10 digits. You entered ' + input.value.length + ' digit(s).';
            input.classList.add('is-invalid');
        } else {
            errorEl.textContent = '';
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        }
    }

    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var phoneInput = form.querySelector('input[name="phone"]');
            if (phoneInput && phoneInput.value.replace(/[^0-9]/g, '').length !== 10) {
                e.preventDefault();
                validatePhone(phoneInput);
                phoneInput.focus();
                alert('Please enter exactly 10 digits for the phone number.');
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>