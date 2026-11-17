<?php
/**
 * Admin panel configuration.
 * The password itself is NOT stored here — it lives hashed in /data/auth.json
 * and can be changed from Settings inside the admin panel.
 */

// There is no built-in password. Never write a password in any file.

// Uploads
define('BWW_MAX_UPLOAD_MB', 12);
define('BWW_IMG_MAX_W', 1600);   // full-size image kept for the lightbox
define('BWW_THUMB_W', 800);      // smaller version used in the gallery grid
define('BWW_JPEG_QUALITY', 82);
define('BWW_LOGO_MAX_W', 900);   // logos keep transparency and stay PNG

// Lock out after this many failed attempts within the window below.
define('BWW_MAX_ATTEMPTS', 6);
define('BWW_LOCKOUT_MINUTES', 15);
