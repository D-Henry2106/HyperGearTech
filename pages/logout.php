<?php

/**
 * Logout - Destroy session and redirect
 */
session_start();
session_unset();
session_destroy();

// Redirect to home
header('Location: /HyperGearTech/');
exit;
