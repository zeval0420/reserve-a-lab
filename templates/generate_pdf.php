<?php
    require_once '../vendor/autoload.php';

    use Dompdf\Dompdf;
    use Dompdf\Options;

    $options = new Options();

    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Courier');

    $dompdf = new Dompdf($options);

    ob_start();
    include('print_template.php'); 
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->addInfo('Title', 'Your Document Title');

    $dompdf->render();

    $dompdf->stream("reservation_details.pdf", array("Attachment" => 0));
?>