
try {
    if (DB::getSchemaBuilder()->hasTable('tipo_pagamenti')) {
        echo "Table exists. Rows: " . DB::table('tipo_pagamenti')->count();
        file_put_contents('payment_types.txt', print_r(DB::table('tipo_pagamenti')->get()->toArray(), true));
    } else {
        echo "Table DOES NOT EXIST";
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}

