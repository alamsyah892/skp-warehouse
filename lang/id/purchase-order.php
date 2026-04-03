<?php

declare(strict_types=1);

return [
    'model' => [
        'label' => 'Purchase Order',
        'plural_label' => 'Purchase Order',
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

    'memo' => [
        'placeholder' => 'Memo internal',
        'helper' => 'Contoh: Prioritas pengiriman minggu ini',
    ],

    'termin' => [
        'placeholder' => 'Termin pembayaran',
        'helper' => 'Contoh: Termin 30 hari setelah barang diterima',
    ],

    'delivery_info' => [
        'label' => 'Info pengiriman',
        'placeholder' => 'Tuliskan info pengiriman',
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
    ],

    'purchase_requests' => [
        // 'label' => 'Pengajuan',
        'number' => [
            'label' => 'Nomor Pengajuan',
        ],
        // 'helper' => 'Pilih satu pengajuan atau lebih. Pengajuan pertama yang dipilih akan menjadi sumber acuan untuk Gudang, Perusahaan / Gudang Kecil, Divisi, Proyek, dan Alamat Pengiriman/Gudang (jika ada).',
        'helper' => 'Pilih satu pengajuan atau lebih.',
    ],

    'purchase_order_item' => [
        'qty' => [
            'label' => 'Qty Dipesan',
        ],
        'price' => [
            'label' => 'Harga Dasar (satuan)',
            'exclude_label' => 'Harga (DPP)',
            'include_label' => 'Harga (include tax)',
        ],
        'discount' => [
            'label' => 'Diskon Item (satuan)',
        ],
        'tax' => [
            'label' => 'Pajak Item',
        ],
        'final_price' => [
            'label' => 'Harga Final (satuan)',
        ],
        'total' => [
            'label' => 'Total Harga',
        ],
        'source_item' => [
            'context' => 'Konteks Sumber Item Pengajuan',
            'context_value' => 'Kode Item: :code | Nama Item: :name | Pengajuan: :number | Diajukan: :request_qty | Dipesan: :ordered_qty | Sisa: :remaining_qty',
        ],
        'description' => [
            'placeholder' => 'Deskripsi item PO',
            'helper' => 'Contoh: spesifikasi, merk, atau instruksi pengiriman',
        ],
    ],

    'discount' => [
        'label' => 'Diskon PO (keseluruhan)',
    ],

    'tax' => [
        'label' => 'Nominal Pajak',
    ],

    'tax_type' => [
        'label' => 'Mode Pajak',
        'include' => 'Include Tax',
        'exclude' => 'Exclude Tax',
    ],

    'tax_percentage' => [
        'label' => 'Persentase Pajak',
    ],

    'tax_description' => [
        'label' => 'Keterangan Pajak',
        'placeholder' => 'Contoh: PPN 11%',
    ],

    'rounding' => [
        'label' => 'Pembulatan',
    ],

    'total' => [
        'subtotal' => 'Subtotal Pesanan',
        'net_subtotal' => 'Subtotal Setelah Diskon PO',
        'discount' => 'Diskon',
        'tax' => 'Pajak',
        'grand_total' => 'Total Pembayaran',
    ],

    'validation' => [
        'incompatible_headers' => 'Item sumber tidak kompatibel. Warehouse, Perusahaan (Gudang Kecil), Divisi, dan Proyek harus sama.',
        'incompatible_purchase_requests' => 'Pengajuan yang dipilih harus memiliki Gudang, Perusahaan (Gudang Kecil), Divisi, dan Proyek yang sama.',
        'qty_exceeded' => 'Qty alokasi melebihi sisa kuota. Sisa: :remaining.',
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
    ],

    'fieldset' => [
        'warehouse_project' => [
            'label' => 'Sumber Pengajuan',
        ],
        'info' => [
            'label' => 'Informasi Purchase Order',
        ],
    ],
];
