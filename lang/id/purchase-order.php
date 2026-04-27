<?php

declare(strict_types=1);

return [
    'model' => [
        'label' => 'Purchase Order',
        'plural_label' => 'Purchase Order',
    ],

    'type' => [
        'label' => 'Tipe PO',
    ],

    'warehouse_address' => [
        'label' => 'Alamat Pengiriman/Gudang',
    ],

    'company' => [
        'label' => 'Perusahaan / Gudang Kecil',
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
            'note' => 'Konfirmasi perubahan status purchase order menjadi :status',
            'changed' => 'Status berhasil diubah',
        ],
    ],

    'description' => [
        'placeholder' => 'Tuliskan deskripsi purchase order',
        'helper' => 'Contoh: PO material struktur tahap pertama',
    ],

    'terms' => [
        'placeholder' => 'Termin pembayaran',
        'helper' => 'Contoh: Termin 30 hari setelah barang diterima',
    ],

    'delivery_date' => [
        'label' => 'Tanggal Pengiriman',
    ],

    'shipping_method' => [
        'label' => 'Metode Pengiriman',
        'placeholder' => 'Tuliskan metode pengiriman yang digunakan',
        'helper' => 'Contoh: Pickup',
    ],

    'delivery_notes' => [
        'label' => 'Catatan Pengiriman',
        'placeholder' => 'Tuliskan catatan pengiriman',
        'helper' => 'Contoh: Dikirim langsung oleh vendor',
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
        'label' => 'Item Purchase Order',
        'count_label' => 'Jumlah Item',
        'total_qty_label' => 'Total Qty',
        'received_qty_label' => 'Total Qty Diterima',
        'received_percentage_label' => 'Persentase Diterima',
    ],

    'purchase_requests' => [
        'number' => [
            'label' => 'Nomor Pengajuan',
        ],
        'helper' => 'Pilih satu pengajuan atau lebih.',
    ],

    'purchase_request_item' => [
        'label' => 'Sumber Item Pengajuan',
    ],

    'purchase_order_item' => [
        'price' => [
            'label' => 'Harga',
            'include_label' => 'Harga (Include Tax)',
        ],
        'source_item' => [
            'context' => 'Konteks Sumber Item Pengajuan',
            'context_value' => 'Diajukan: :request_qty | Dipesan: :ordered_qty | Kuota: :remaining_qty',
        ],
        'description' => [
            'placeholder' => 'Deskripsi item PO',
            'helper' => 'Contoh: spesifikasi, merk, atau instruksi pengiriman',
        ],
        'ordered_qty' => [
            'label' => 'Dipesan',
        ],
        'remaining_qty' => [
            'label' => 'Tersisa',
        ],
        'received_qty' => [
            'label' => 'Diterima',
        ],
    ],

    'discount' => [
        'label' => 'Diskon',
    ],

    'after_discount' => [
        'label' => 'Subtotal Setelah Diskon',
    ],

    'tax' => [
        'label' => 'PPN :percentage',
    ],

    'tax_type' => [
        'label' => 'Mode Pajak',
        'include' => 'Include',
        'exclude' => 'Exclude',
    ],

    'tax_percentage' => [
        'label' => 'Persentase Pajak',
    ],

    'tax_description' => [
        'label' => 'Deskripsi Pajak',
        'placeholder' => 'Tuliskan deskripsi pajak (opsional)',
        'helper' => 'Contoh: PPN 11%',
    ],

    'tax_base' => [
        'label' => 'DPP',
    ],

    'rounding' => [
        'label' => 'Pembulatan',
    ],

    'subtotal' => [
        'label' => 'Subtotal',
    ],

    'total' => [
        'label' => 'Total',
    ],

    'grand_total' => [
        'label' => 'Total Pembayaran',
    ],

    'validation' => [
        'incompatible_headers' => 'Item sumber tidak kompatibel. Warehouse, Perusahaan (Gudang Kecil), Divisi, dan Proyek harus sama.',
        'incompatible_purchase_requests' => 'Pengajuan yang dipilih harus memiliki Gudang, Perusahaan (Gudang Kecil), Divisi, dan Proyek yang sama.',
        'purchase_request_item_changed' => 'Data item pengajuan sudah berubah. Muat ulang form lalu periksa kembali qty pengajuan dan qty teralokasi.',
        'purchase_request_status_changed' => 'Status pengajuan sudah berubah. Muat ulang form lalu periksa kembali pengajuan yang dipilih.',
        'qty_exceeded' => 'Qty melebihi kuota yang bisa dipesan (:remaining).',
        'source_item_not_found' => 'Item sumber Pengajuan tidak ditemukan.',
        'source_item_not_selected_pr' => 'Item sumber harus berasal dari Pengajuan yang sudah dipilih.',
        'source_purchase_request_not_found' => 'Pengajuan sumber tidak ditemukan.',
    ],

    'section' => [
        'main_info' => [
            'label' => 'Informasi Purchase Order',
            'description' => 'Informasi utama dari purchase order.',
        ],
        'other_info' => [
            'label' => 'Informasi Lainnya',
            'description' => 'Informasi tambahan dari purchase order.',
        ],
        'purchase_order_items' => [
            'label' => 'Item Purchase Order',
            'description' => 'Item yang dialokasikan dari pengajuan.',
        ],
        'summary_total' => [
            'label' => 'Ringkasan Total',
            'description' => '',
        ],
    ],

    'fieldset' => [
        'warehouse_project' => [
            'label' => 'Gudang Proyek',
        ],
        'main_info' => [
            'label' => 'Informasi Utama',
        ],
        'detail_total' => [
            'label' => 'Rincian Total',
        ],
    ],
];
