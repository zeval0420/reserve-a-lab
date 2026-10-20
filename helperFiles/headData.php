<?php
/*
 * Shared <head> assets for the logged-in pages.
 *
 * A page may set $pageAssets before including this file to load only the optional
 * libraries it uses (contract C1 in plan.md):
 *     'datatables'  DataTables 1.13.6 + Bootstrap 3 integration + Responsive 2.5.0 + Select 1.6.2
 *     'multiselect' bootstrap-multiselect 0.9.13
 *     'barcode'     Quagga2 1.12.1 (barcode scanner)
 * When $pageAssets is not set, everything is loaded.
 *
 * All files are self-hosted. Local files get ?v=<mtime> so they can be cached for a long time.
 * jQuery, Bootstrap JS, DataTables and bootstrap-multiselect must stay classic blocking scripts:
 * the pages run inline $(...) code while they are parsed.
 */
$__headAssets = (isset($pageAssets) && is_array($pageAssets))
    ? $pageAssets
    : ['datatables', 'multiselect', 'barcode'];
$__headHas = function ($key) use ($__headAssets) {
    return in_array($key, $__headAssets, true);
};
$__headUrl = function ($rel) {
    $file = __DIR__ . '/../' . $rel;
    return is_file($file) ? $rel . '?v=' . filemtime($file) : $rel;
};
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

<!-- Raleway (latin) is used on first paint: start the download early -->
<link rel="preload" href="assets/fonts/raleway/raleway-latin.woff2" as="font" type="font/woff2" crossorigin>

<!-- Local Header and Footer CSS -->
<link rel="stylesheet" href="css/universal.css?v=<?= filemtime(__DIR__ . '/../css/universal.css') ?>">
<link rel="stylesheet" href="css/header.css?v=<?= filemtime(__DIR__ . '/../css/header.css') ?>">
<link rel="stylesheet" href="css/footer.css?v=<?= filemtime(__DIR__ . '/../css/footer.css') ?>">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="img/logo.png">

<!-- Raleway, variable 100-900, self-hosted (same woff2 files Google Fonts served, v37) -->
<style>
    /* cyrillic-ext */
    @font-face {
        font-family: 'Raleway';
        font-style: normal;
        font-weight: 100 900;
        font-display: swap;
        src: url(assets/fonts/raleway/raleway-cyrillic-ext.woff2) format('woff2');
        unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }
    /* cyrillic */
    @font-face {
        font-family: 'Raleway';
        font-style: normal;
        font-weight: 100 900;
        font-display: swap;
        src: url(assets/fonts/raleway/raleway-cyrillic.woff2) format('woff2');
        unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }
    /* vietnamese */
    @font-face {
        font-family: 'Raleway';
        font-style: normal;
        font-weight: 100 900;
        font-display: swap;
        src: url(assets/fonts/raleway/raleway-vietnamese.woff2) format('woff2');
        unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }
    /* latin-ext */
    @font-face {
        font-family: 'Raleway';
        font-style: normal;
        font-weight: 100 900;
        font-display: swap;
        src: url(assets/fonts/raleway/raleway-latin-ext.woff2) format('woff2');
        unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C4, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }
    /* latin */
    @font-face {
        font-family: 'Raleway';
        font-style: normal;
        font-weight: 100 900;
        font-display: swap;
        src: url(assets/fonts/raleway/raleway-latin.woff2) format('woff2');
        unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }
</style>

<!-- Bootstrap 3.3.7 (CSS + Theme) -->
<link rel="stylesheet" href="<?= $__headUrl('bootstrap3.3.7/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= $__headUrl('bootstrap3.3.7/css/bootstrap-theme.min.css') ?>">

<!-- Bootstrap Icons 1.10.5 -->
<link rel="stylesheet" href="<?= $__headUrl('assets/vendor/bootstrap-icons@1.10.5/font/bootstrap-icons.css') ?>">

<?php if ($__headHas('datatables')): ?>
<!-- DataTables 1.13.6 for Bootstrap 3 -->
<link rel="stylesheet" href="<?= $__headUrl('assets/vendor/datatables@1.13.6/css/dataTables.bootstrap.min.css') ?>">
<?php endif; ?>

<!-- jQuery 3.3.1 -->
<script src="<?= $__headUrl('jQuery-3.3.1/jquery-3.3.1.min.js') ?>"></script>

<!-- Send the CSRF token with every same-origin jQuery and fetch request (checked by ral_require_csrf() in the ajax/ endpoints) -->
<script>
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.content : '';
    if (!token) return;

    jQuery.ajaxPrefilter(function (options, original, jqXHR) {
        if (!options.crossDomain) jqXHR.setRequestHeader('X-CSRF-Token', token);
    });

    if (window.fetch) {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            var url = typeof input === 'string' ? input : (input && input.url) || '';
            var sameOrigin = false;
            try { sameOrigin = new URL(url, location.href).origin === location.origin; } catch (e) {}
            if (sameOrigin) {
                init = Object.assign({}, init);
                var headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
                headers.set('X-CSRF-Token', token);
                init.headers = headers;
            }
            return nativeFetch.call(this, input, init);
        };
    }
})();
</script>

<!-- Bootstrap JS 3.3.7 -->
<script src="<?= $__headUrl('bootstrap3.3.7/js/bootstrap.min.js') ?>"></script>

<?php if ($__headHas('datatables')): ?>
<!-- DataTables JS -->
<script src="<?= $__headUrl('assets/vendor/datatables@1.13.6/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= $__headUrl('assets/vendor/datatables@1.13.6/js/dataTables.bootstrap.min.js') ?>"></script>

<!-- DataTables Responsive 2.5.0 -->
<link rel="stylesheet" href="<?= $__headUrl('assets/vendor/datatables-responsive@2.5.0/css/responsive.bootstrap.min.css') ?>">
<script src="<?= $__headUrl('assets/vendor/datatables-responsive@2.5.0/js/dataTables.responsive.min.js') ?>"></script>
<script src="<?= $__headUrl('assets/vendor/datatables-responsive@2.5.0/js/responsive.bootstrap.min.js') ?>"></script>

<!-- DataTables Select 1.6.2 -->
<link rel="stylesheet" href="<?= $__headUrl('assets/vendor/datatables-select@1.6.2/css/select.bootstrap.min.css') ?>">
<script src="<?= $__headUrl('assets/vendor/datatables-select@1.6.2/js/dataTables.select.min.js') ?>"></script>
<?php endif; ?>

<?php if ($__headHas('multiselect')): ?>
<!-- Bootstrap Multiselect 0.9.13 (JS only: its CSS was never loaded, the CDN URL returned 404) -->
<script src="<?= $__headUrl('assets/vendor/bootstrap-multiselect@0.9.13/js/bootstrap-multiselect.min.js') ?>"></script>
<?php endif; ?>

<?php if ($__headHas('barcode')): ?>
<!-- Barcode scanner: Quagga2 1.12.1 (the version the unversioned CDN URL served).
     Only used after a click on #scanBarcodeBtn, so it can be deferred. -->
<script defer src="<?= $__headUrl('assets/vendor/quagga2@1.12.1/dist/quagga.min.js') ?>"></script>
<?php endif; ?>
<?php unset($__headAssets, $__headHas, $__headUrl); ?>
