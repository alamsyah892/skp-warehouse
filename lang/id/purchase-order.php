<?php

declare(strict_types=1);

return [
    'model' => [
        'label' => 'Purchase Order',
        'plural_label' => 'Purchase Order',
    ],

    'warehouse_address' => [
        'label' => 'Alamat Pengiriman (Gudang)',
    ],

    'company' => [
        'label' => 'Perusahaan (Gudang Kecil)',
    ],

    'number' => [
        'label' => 'Nomor',
    ],

    'status' => [
        'all' => 'Semua',
        'draft' => [
            'label' => 'Draft',
            'action_label' => 'Draft',
        ],
        'canceled' => [
            'label' => 'Dibatalkan',
            'action_label' => 'Batalkan',
        ],
        'ordered' => [
            'label' => 'Dipesan',
            'action_label' => 'Pesan',
        ],
        'finished' => [
            'label' => 'Selesai',
            'action_label' => 'Selesaikan',
        ],
        'action' => [
            'note' => 'Konfirmasi perubahan status pesanan pembelian menjadi :status',
            'changed' => 'Status berhasil diubah',
        ],
    ],

    'description' => [
        'placeholder' => 'Tuliskan deskripsi pesanan pembelian',
        'helper' => 'Contoh: PO material struktur tahap pertama',
    ],

    'memo' => [
        'placeholder' => 'Memo internal',
        'helper' => 'Contoh: Prioritas pengiriman minggu ini',
    ],

    'termin' => [
        'placeholder' => 'Termin pembayaran',
        'helper' => 'Contoh: Termin 30 hari setelah barang diterima',
    ],

    'notes' => [
        'label' => 'Catatan',
        'placeholder' => 'Catatan tambahan',
        'helper' => 'Contoh: Sertakan sertifikat mutu',
    ],

    'info' => [
        'label' => 'Informasi Revisi',
        'placeholder' => 'Tuliskan alasan revisi',
        'helper' => 'Contoh: Revisi kuantitas karena perubahan kebutuhan lapangan',
    ],

    'revision_history' => [
        'label' => 'Riwayat Revisi',
    ],

    'purchase_order_items' => [
        'label' => 'Item Pesanan Pembelian',
        'count_label' => 'Jumlah Item',
    ],

    'purchase_order_item' => [
        'qty' => [
            'label' => 'Qty Alokasi',
        ],
        'price' => [
            'label' => 'Harga',
        ],
        'source_item' => [
            'label' => 'Sumber Item PR',
            'pr_number' => 'Nomor PR',
            'context' => 'Konteks Sumber',
            'context_value' => 'PR: :number | :code - :name | Request: :request_qty | Allocated: :allocated_qty | Remaining: :remaining_qty',
        ],
        'description' => [
            'placeholder' => 'Deskripsi item PO',
            'helper' => 'Contoh: spesifikasi, merk, atau instruksi pengiriman',
        ],
    ],

    'validation' => [
        'incompatible_headers' => 'Item sumber tidak kompatibel. Warehouse, company, division, dan project harus sama.',
        'qty_exceeded' => 'Qty alokasi melebihi sisa kuota. Remaining: :remaining.',
        'source_item_not_found' => 'Item sumber PR tidak ditemukan.',
    ],

    'section' => [
        'main_info' => [
            'label' => 'Informasi Pesanan Pembelian',
            'description' => 'Informasi utama dari pesanan pembelian.',
        ],
        'other_info' => [
            'label' => 'Informasi Lainnya',
            'description' => 'Informasi tambahan dari pesanan pembelian.',
        ],
        'purchase_order_items' => [
            'label' => 'Item Pesanan Pembelian',
            'description' => 'Item yang dialokasikan dari pengajuan pembelian.',
        ],
    ],

    'fieldset' => [
        'warehouse_project' => [
            'label' => 'Vendor, Gudang, dan Proyek',
        ],
        'info' => [
            'label' => 'Informasi Pesanan Pembelian',
        ],
    ],
];
