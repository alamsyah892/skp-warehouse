<?php

return [
    'model' => [
        'label' => 'Penerimaan Barang',
        'plural_label' => 'Penerimaan Barang',
    ],

    'number' => [
        'label' => 'Nomor',
    ],

    'company' => [
        'label' => 'Perusahaan (Gudang Kecil)',
    ],

    'warehouse_address' => [
        'label' => 'Alamat Penerimaan (Gudang)',
    ],

    'section' => [
        'main_info' => [
            'label' => 'Informasi Penerimaan Barang',
        ],

        'goods_receive_items' => [
            'label' => 'Item Penerimaan',
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
        'label' => 'Tipe Penerimaan',
        'purchase_order' => [
            'label' => 'BPB PO',
        ],
        'manual' => [
            'label' => 'BPB Manual',
        ],
        'correction' => [
            'label' => 'Koreksi Stok',
        ],
    ],

    'status' => [
        'received' => [
            'label' => 'Diterima',
            'action_label' => 'Terima',
        ],
        'returned' => [
            'label' => 'Dikembalikan',
            'action_label' => 'Kembalikan',
        ],
        'canceled' => [
            'label' => 'Dibatalkan',
            'action_label' => 'Batalkan',
        ],
        'action' => [
            'note' => 'Ubah status menjadi :status. Tambahkan catatan (opsional).',
            'changed' => 'Status berhasil diubah.',
        ],
    ],

    'description' => [
        'placeholder' => 'Contoh: Penerimaan barang tahap 1',
        'helper' => 'Boleh diisi untuk memberikan konteks penerimaan.',
    ],

    'delivery_order' => [
        'label' => 'Delivery Order',
        'placeholder' => 'Nomor DO / SJ',
        'helper' => 'Nomor dokumen pengiriman dari vendor / ekspedisi (opsional).',
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

    'goods_receive_items' => [
        'label' => 'Item Penerimaan',
        'count_label' => 'Jumlah Item',
    ],

    'goods_receive_item' => [
        'description' => [
            'placeholder' => 'Deskripsi item penerimaan barang',
            'helper' => 'Contoh: spesifikasi, merk, atau instruksi pengiriman',
        ],
    ],

    'purchase_order_item' => [
        'source_item' => [
            'context_value' => 'Dipesan: :ordered_qty | Diterima: :received_qty | Kuota: :remaining_qty',
        ],
    ],

    'validation' => [
        'qty_exceeded' => 'Qty melebihi kuota yang bisa diterima (:remaining).',
        'source_item_not_found' => 'Item sumber tidak ditemukan.',
    ],
];

