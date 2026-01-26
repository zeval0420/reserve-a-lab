
<?php
    // Include the autoloader. Adjust the path if you are not using Composer (e.g., '../dompdf/autoload.inc.php')
    require_once '../vendor/autoload.php';

    use Dompdf\Dompdf;
    use Dompdf\Options;

    // Instantiate the options class
    $options = new Options();

    // Set specific options
    $options->set('isRemoteEnabled', true); // Enables loading remote assets (images, CSS)
    $options->set('defaultFont', 'Courier'); // Sets a default font

    // Pass the options to the Dompdf constructor
    $dompdf = new Dompdf($options);

    // To capture the HTML content from another file, we can use output buffering.
    ob_start();
    include('print_template.php'); 
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->addInfo('Title', 'Your Document Title');

    // Render the HTML as PDF
    $dompdf->render();

    // Output the generated PDF to the browser for preview
    $dompdf->stream("reservation_details.pdf", array("Attachment" => 0));
?>