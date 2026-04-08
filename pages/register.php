<?php

/**
 * ============================================
 * Registration Page - New User Signup
 * ============================================
 */
$page_title = 'Register';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

$error = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['first_name'] = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
    $old['last_name']  = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));
    $old['email']      = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $old['phone']      = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $password          = $_POST['password'] ?? '';
    $confirm           = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($old['first_name']) || empty($old['last_name']) || empty($old['email']) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $old['email'])) {
        $error = 'Please enter a valid email address.';
    } elseif (!empty($old['phone']) && !preg_match('/^\d{10}$/', $old['phone'])) {
        $error = 'Phone number must be exactly 10 digits.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $old['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email address already registered.';
        } else {
            // Insert new user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $old['first_name'], $old['last_name'], $old['email'], $hashed, $old['phone']);
            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . 'pages/login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-lg login-card scroll-reveal">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h3 class="fw-bold">Create Account</h3>
                            <p class="text-muted">Join HyperGear Tech today</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($old['first_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($old['last_name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-bold">Email Address *</label>
                                <input type="email" name="email" class="form-control" required pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Enter a valid email address" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone Number * <small class="text-muted">(exactly 10 digits)</small></label>
                                <input type="text" name="phone" class="form-control" required maxlength="10" minlength="10" pattern="\d{10}" title="Phone number must be exactly 10 digits" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Password * <small class="text-muted">(min 6 characters)</small></label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Confirm Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg hg-btn-glow">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">Already have an account? <a href="<?= BASE_URL ?>pages/login.php" class="text-primary fw-bold">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('input[name="phone"]').forEach(function(input) {
        // Only allow digits
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            validatePhone(this);
        });
        // Validate on blur
        input.addEventListener('blur', function() {
            validatePhone(this);
        });
        // Block paste of non-digits
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

    // Block form submit if phone invalid
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