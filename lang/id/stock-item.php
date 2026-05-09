<?php

declare(strict_types=1);

return [
    'model.label' => 'Stok Item',
    'model.plural_label' => 'Stok Item',

    'qty.received' => 'Qty Masuk',
    'qty.issued' => 'Qty Keluar',
    'qty.available' => 'Qty Tersedia',

    'filters.low_stock' => 'Stok Menipis (<= 10)',

    'mutation.action.label' => 'Mutasi',
    'mutation.heading' => 'Histori Mutasi :item',
    'mutation.empty' => 'Belum ada mutasi untuk periode ini.',
    'mutation.period.label' => 'Periode',
    'mutation.period.all' => 'Semua Periode',

    'mutation.filter_section.label' => 'Filter Periode',
    'mutation.filter_section.description' => 'Jika tahun dan bulan tidak dipilih, saldo awal dihitung 0. Jika keduanya dipilih, saldo awal diambil dari saldo akhir bulan sebelumnya.',

    'mutation.context_section.label' => 'Informasi Stok',

    'mutation.summary_section.label' => 'Ringkasan Saldo',
    'mutation.summary.opening_balance' => 'Saldo Awal',
    'mutation.summary.total_received' => 'Total Masuk',
    'mutation.summary.total_issued' => 'Total Keluar',
    'mutation.summary.ending_balance' => 'Saldo Akhir',

    'mutation.table_section.label' => 'Daftar Mutasi',
    'mutation.table_section.description' => 'Menampilkan mutasi stok untuk periode :period.',
    'mutation.table.document_type' => 'Jenis',
    'mutation.table.balance' => 'Saldo',

    'mutation.filters.year' => 'Tahun',
    'mutation.filters.month' => 'Bulan',
    'mutation.filters.placeholder' => 'Semua',

    'empty.heading' => 'Belum ada stok tersedia',
    'empty.description' => 'Coba ubah filter warehouse/company/division/project untuk melihat stok.',
];
