<?php
// Private credentials. Gitignored: never commit this file.
// Copy secrets.example.php to secrets.php on each server and fill in the values.
return [
    'approval_secret' => '3411b42056a69f382cb410b9320025ebaabf27fb6951ac05a4c4f9cf802c97fc',
    // Old hard-coded signing key: only accepts links emailed before the move. Delete once they expire.
    'legacy_approval_secret' => 'SciLabApprovalLink2026',
    'smtp_password'   => 'wxzmkkrffptfchcc',
];
