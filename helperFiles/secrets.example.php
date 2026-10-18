<?php
// Copy to secrets.php and fill in. secrets.php is gitignored.
// approval_secret: long random string, e.g. `php -r "echo bin2hex(random_bytes(32));"`.
// Changing it invalidates every approval link already emailed.
return [
    'approval_secret' => '',
    'legacy_approval_secret' => '', // optional: previous key, validates old links only
    'smtp_password'   => '',
];
