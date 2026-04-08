<?php

/**
 * Contact Page
 */
$page_title = 'Contact Us';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, send email or store in DB
    $message = 'Thank you for your message! We will get back to you soon.';
}
?>

<section class="page-header py-4">
    <div class="container">
        <h2 class="fw-bold text-white mb-0"><i class="fas fa-envelope me-2"></i>Contact Us</h2>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5 scroll-reveal">
                <h4 class="fw-bold mb-4">Get In Touch</h4>
                <div class="mb-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="feature-icon me-3" style="min-width:50px;"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Address</h6>
                            <p class="text-muted mb-0">123 Tech Street, Silicon City, SC 10001</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <div class="feature-icon me-3" style="min-width:50px;"><i class="fas fa-phone"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Phone</h6>
                            <p class="text-muted mb-0">+1 (555) 123-4567</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <div class="feature-icon me-3" style="min-width:50px;"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Email</h6>
                            <p class="text-muted mb-0">info@hypergear.com</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <div class="feature-icon me-3" style="min-width:50px;"><i class="fas fa-clock"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Hours</h6>
                            <p class="text-muted mb-0">Mon-Sat: 9AM - 9PM</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 scroll-reveal">
                <?php if ($message): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $message ?></div>
                <?php endif; ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">Send us a Message</h5>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Your Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Your Email</label>
                                    <input type="email" name="email" class="form-control" required pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Enter a valid email address">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Subject</label>
                                    <input type="text" name="subject" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Message</label>
                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg hg-btn-glow"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>