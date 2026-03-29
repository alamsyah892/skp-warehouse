<?php

declare(strict_types=1);

return [
    'model' => [
        'label' => 'Pengajuan Pembelian',
        'plural_label' => 'Pengajuan Pembelian',
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
        'requested' => [
            'label' => 'Diajukan',
            'action_label' => 'Ajukan',
        ],
        'approved' => [
            'label' => 'Disetujui',
            'action_label' => 'Setujui',
        ],
        'ordered' => [
            'label' => 'Dipesan',
            'action_label' => 'Dipesan',
        ],
        'finished' => [
            'label' => 'Selesai',
            'action_label' => 'Selesaikan',
        ],

        'action' => [
            'note' => 'Konfirmasi perubahan status pengajuan pembelian menjadi :status',
            'changed' => 'Status berhasil diubah',
        ],
    ],

    'description' => [
        'placeholder' => 'Tuliskan deskripsi permintaan pembelian',
        'helper' => 'Contoh: Pembelian material untuk pekerjaan pondasi proyek A',
    ],

    'memo' => [
        'placeholder' => 'Memo internal',
        'helper' => 'Contoh: Pembelian urgent untuk kebutuhan lapangan, Nomor Memo: MEMO-001',
    ],

    'boq' => [
        'label' => 'RAB',
        'placeholder' => 'Tuliskan nomor RAB terkait',
        'helper' => 'Contoh: RAB-PRJ-001',
    ],

    'notes' => [
        'label' => 'Catatan',
        'placeholder' => 'Catatan tambahan',
        'helper' => 'Contoh: Barang harus dikirim sebelum tanggal 25',
    ],

    'info' => [
        'label' => 'Informasi Revisi',
        'placeholder' => 'Tuliskan alasan revisi',
        'helper' => 'Contoh: Revisi jumlah barang karena kebutuhan proyek bertambah',
    ],

    'revision_history' => [
        'label' => 'Riwayat Revisi',
    ],

    'purchase_request_items' => [
        'label' => 'Item Pengajuan Pembelian',
        'count_label' => 'Jumlah Item',
    ],

    'purchase_request_item' => [
        'description' => [
            'placeholder' => 'Deskripsi item yang dipesan',
            'helper' => 'Contoh: merk, spesifikasi, dll',
        ],
        'ordered_qty' => [
            'label' => 'Qty Dipesan',
        ],
        'remaining_qty' => [
            'label' => 'Qty Tersisa',
        ],
    ],

    'section' => [
        'main_info' => [
            'label' => 'Informasi Pengajuan Pembelian',
            'description' => 'Informasi utama dari pengajuan pembelian.',
        ],

        'other_info' => [
            'label' => 'Informasi Lainnya',
            'description' => 'Informasi tambahan dari pengajuan pembelian.',
        ],

        'purchase_request_items' => [
            'label' => 'Item Pengajuan Pembelian',
            'description' => 'Item yang dipesan pada pengajuan pembelian ini.',
        ],
    ],

    'fieldset' => [
        'warehouse_project' => [
            'label' => 'Gudang Proyek',
        ],

        'info' => [
            'label' => 'Informasi Pengajuan Pembelian',
        ],
    ],
];
