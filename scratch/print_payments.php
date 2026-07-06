<?php
$rows = DB::table('tipo_pagamenti')->get();
foreach($rows as $r) {
    echo $r->idTipoPagamento . ' -> ' . $r->descrizioneTipoPagamento . "\n";
}
