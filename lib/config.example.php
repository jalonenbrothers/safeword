<?php
/**
 * SafeWord configuration — copy to lib/config.php and fill in.
 * NEVER commit lib/config.php (the repo .htaccess also blocks web access to it).
 */

return [
    // Hostinger host usually: localhost (or e.g. mysql.example.com)
    'db_host' => 'localhost',
    'db_name' => 'safeword',
    'db_user' => 'safeword_user',
    'db_pass' => 'CHANGE_ME',

    // Public base URL of the app (no trailing slash), used for invite links.
    // On Hostinger: https://myapp2mvp.earth-turtle.com
    'base_url' => 'https://myapp2mvp.earth-turtle.com',
];
