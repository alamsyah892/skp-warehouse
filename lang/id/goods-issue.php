<?php

return [
    'model' => [
        'label' => 'Pengeluaran Barang',
        'plural_label' => 'Pengeluaran Barang',
    ],
    'company' => [
        'label' => 'Perusahaan (Gudang Kecil)',
    ],
    'warehouse_address' => [
        'label' => 'Alamat Gudang',
    ],
    'section' => [
        'main_info' => [
            'label' => 'Informasi Pengeluaran Barang',
        ],
        'goods_issue_items' => [
            'label' => 'Item Pengeluaran',
        ],
        'other_info' => [
            'label' => 'Informasi Lainnya',
        ],
    ],
    'fieldset' => [
        'warehouse_project' => [
            'label' => 'Gudang Proyek',
        ],
        'main_info' => [
            'label' => 'Informasi Utama',
        ],
    ],
    'type' => [
        'label' => 'Tipe Pengeluaran',
        'issue' => [
            'label' => 'Pengeluaran',
        ],
        'transfer' => [
            'label' => 'Mutasi',
        ],
    ],
    'status' => [
        'issued' => [
            'label' => 'Dikeluarkan',
            'action_label' => 'Keluarkan',
        ],
        'canceled' => [
            'label' => 'Dibatalkan',
            'action_label' => 'Batalkan',
        ],
        'action' => [
            'note' => 'Ubah status menjadi :status. Tambahkan catatan (opsional).',
            'changed' => 'Status berhasil diubah.',
        ],
        'all' => 'Semua',
    ],
    'description' => [
        'placeholder' => 'Contoh: Pengeluaran barang operasional proyek',
        'helper' => 'Boleh diisi untuk memberikan konteks pengeluaran barang.',
    ],
    'notes' => [
        'label' => 'Catatan',
        'placeholder' => 'Catatan tambahan',
        'helper' => 'Opsional.',
    ],
    'info' => [
        'label' => 'Info Revisi',
        'placeholder' => 'Alasan perubahan / revisi',
        'helper' => 'Wajib diisi jika ada perubahan data yang dipantau.',
    ],
    'revision_history' => [
        'label' => 'Riwayat Revisi',
    ],
    'goods_issue_items' => [
        'label' => 'Item Pengeluaran',
        'count_label' => 'Jml Item',
        'sum_qty_label' => 'Qty Item',
    ],
    'goods_issue_item' => [
        'description' => [
            'placeholder' => 'Deskripsi item pengeluaran barang',
            'helper' => 'Contoh: tujuan pemakaian, area kerja, atau catatan pemindahan.',
        ],
        'available_qty' => [
            'label' => 'Kuota Tersedia',
        ],
    ],
    'stock_item' => [
        'context_value' => 'Diterima: :received_qty | Dikeluarkan: :issued_qty | Kuota: :available_qty',
    ],
    'validation' => [
        'qty_exceeded' => 'Qty melebihi kuota yang bisa dikeluarkan (:available).',
        'source_item_not_found' => 'Item sumber tidak ditemukan.',
    ],
];
