<?php
/**
 * Index Page - Redirects to Landing Page
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

// Redirect to landing page
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
} else {
    redirect('landing.php');
}

// This file simply routes the user to the appropriate page
/*
 * If user is logged in -> dashboard.php
 * If user is not logged in -> landing.php
 */


