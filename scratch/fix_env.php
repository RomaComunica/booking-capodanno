<?php
$file = 'c:\\dev\\booking_new\\app\\Services\\StatisticsService.php';
$content = file_get_contents($file);

$content = str_replace(
    "->where('dataPagamentoOrdine', '!=', '0000-00-00 00:00:00')",
    "->where('statoOrdine', 2)",
    $content
);

$content = str_replace(
    "->where('ordini.dataPagamentoOrdine', '!=', '0000-00-00 00:00:00')",
    "->where('ordini.statoOrdine', 2)",
    $content
);

file_put_contents($file, $content);
echo "Replaced all occurrences successfully.\n";
