<?php

return [
    'model' => [
        'label' => 'Penerimaan Barang',
        'plural_label' => 'Penerimaan Barang',
    ],

    'section' => [
        'main_info' => [
            'label' => 'Informasi Utama',
        ],
        'goods_receive_items' => [
            'label' => 'Item Penerimaan',
        ],
        'other_info' => [
            'label' => 'Informasi Lainnya',
        ],
    ],

    'number' => [
        'label' => 'Nomor',
    ],

    'type' => [
        'label' => 'Tipe',
        'purchase_order' => [
            'label' => 'Purchase Order',
        ],
        'manual' => [
            'label' => 'Manual',
        ],
    ],

    'status' => [
        'received' => [
            'label' => 'Received',
            'action_label' => 'Terima',
        ],
        'returned' => [
            'label' => 'Returned',
            'action_label' => 'Return',
        ],
        'canceled' => [
            'label' => 'Canceled',
            'action_label' => 'Batalkan',
        ],
        'action' => [
            'note' => 'Ubah status menjadi :status. Tambahkan catatan (opsional).',
            'changed' => 'Status berhasil diubah.',
        ],
    ],

    'description' => [
        'label' => 'Deskripsi',
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
    ],

    'purchase_order' => [
        'label' => 'Purchase Order',
    ],

    'purchase_order_item' => [
        'label' => 'Item Purchase Order',
    ],

    'qty' => [
        'label' => 'Qty Diterima',
        'placeholder' => 0.01,
    ],

    'validation' => [
        'qty_exceeded' => 'Qty melebihi sisa yang bisa diterima (:remaining).',
        'source_item_not_found' => 'Item sumber tidak ditemukan.',
    ],
];

