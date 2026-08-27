<?php
$pdfUrl = 'book.pdf';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Interactive digital magazine"
    >

    <title>Digital Magazine</title>

    <link
        rel="stylesheet"
        href="css/flipbook.css"
    >
</head>

<body>

<?php
include __DIR__ . '/flipbook-embed.php';
?>

<!-- StPageFlip -->
<script
    src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.js"
></script>

<!-- Flipbook application -->
<script
    type="module"
    src="js/main.js"
></script>

</body>
</html>
```
