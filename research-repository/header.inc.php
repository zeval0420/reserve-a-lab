<?php
/*
 * Page header shared by index.php and upload.php.
 * Set $rrNav before including: a list of [href, label] links shown at the right.
 * A link whose label is "Reserve-a-Lab" is hidden on phones to save room.
 */
$rrNav = $rrNav ?? [];
?>
    <header class="rr-header">
        <div class="rr-header-inner">
            <img src="../img/logo.png" alt="PSHS Logo" class="rr-logo" width="52" height="52">
            <div class="rr-school">
                <span>Department of Science and Technology</span>
                <strong>PHILIPPINE SCIENCE HIGH SCHOOL</strong>
                <strong>ILOCOS REGION CAMPUS</strong>
            </div>
            <nav class="rr-nav" aria-label="Site">
                <?php foreach ($rrNav as [$href, $label]): ?>
                    <a class="rr-home<?= $label === 'Reserve-a-Lab' ? ' rr-home-lab' : '' ?>" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>
