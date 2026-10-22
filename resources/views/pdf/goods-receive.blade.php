@php
    /** @var \App\Models\GoodsReceive $goodsReceive */
    /** @var \Illuminate\Support\Carbon|null $printedAt */
    /** @var int|null $pageCount */

    $company = $goodsReceive->company;
    $warehouse = $goodsReceive->warehouse;
    $warehouseAddress = $goodsReceive->warehouseAddress;
    $division = $goodsReceive->division;
    $project = $goodsReceive->project;
    $purchaseOrder = $goodsReceive->purchaseOrder;
    $vendor = $purchaseOrder?->vendor;
    $items = $goodsReceive->goodsReceiveItems;
    $printedAt ??= now();
    $pageCount ??= 1;

    $qty = fn(float|int|string|null $val): string => rtrim(rtrim(number_format((float) $val, 2, '.', ','), '0'), '.') ?: '0';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GR - {{ $goodsReceive->number }}</title>
    <style>
        @page {
            size: A4;
            margin: 1cm 0.5cm;
        }

        body {
            font-size: 10pt;
            font-family: Courier, monospace;
            line-height: 1.2em;
        }

        body,
        h1,
        p,
        table,
        tr,
        th,
        td,
        ul,
        li {
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            font-size: 10pt;
        }

        table {
            margin: 0;
            padding: 0;
            border-collapse: collapse;
            text-wrap: wrap;
        }

        td,
        th {
            vertical-align: top;
            word-wrap: break-word;
        }

        ul {
            list-style-type: none;
        }

        /* Generic Utilities */
        .w-full {
            width: 100%;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .row {
            position: relative;
        }

        /* Metadata Section */
        .meta-col-left {
            min-width: 22rem;
            max-width: 22rem;
            padding-right: 0.5rem;
        }

        .meta-col-right {
            min-width: 24rem;
            max-width: 24rem;
            padding-left: 0.5rem;
        }

        .meta-lbl-left {
            min-width: 6.5rem;
            max-width: 6.5rem;
        }

        .meta-lbl-right {
            min-width: 7.5rem;
            max-width: 7.5rem;
        }

        .meta-sep {
            width: 1rem;
        }

        .meta-val {
            min-width: 14.5rem;
            max-width: 14.5rem;
        }

        /* Items Table */
        .table-item-border {
            margin-top: -2px;
            margin-left: -3.5px;
            width: 47rem;
            border: 1px solid black;
        }

        .table-item-border th {
            padding: 0 0.5rem;
            border: 1px solid black;
        }

        .table-item-border td {
            padding: 0 0.5rem;
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }

        .table-item-border tfoot {
            border: 1px solid black;
        }

        .col-no {
            min-width: 1.5rem;
            max-width: 1.5rem;
            text-align: right;
        }

        .col-code {
            min-width: 5rem;
            max-width: 5rem;
        }

        .col-name {
            min-width: 15rem;
            max-width: 15rem;
        }

        .col-qty-po {
            min-width: 4.5rem;
            max-width: 4.5rem;
            text-align: right;
        }

        .col-qty {
            min-width: 4.5rem;
            max-width: 4.5rem;
            text-align: right;
        }

        .col-unit {
            min-width: 3.5rem;
            max-width: 3.5rem;
        }

        .col-desc {
            min-width: 13rem;
            max-width: 13rem;
        }

        .item-empty {
            padding: 8px;
            text-align: center;
        }

        /* Signatures Section */
        .table-signature {
            width: 47rem;
        }

        .table-signature th,
        .table-signature td {
            padding: 0 0.5rem;
            text-align: center;
        }

        .sig-space-1 td {
            height: 55pt;
        }

        .sig-col {
            width: 25%;
            min-width: 11.5rem;
            max-width: 11.5rem;
        }

        /* Distribution & Pagination Footers */
        .signature-space {
            height: 120pt;
            page-break-inside: avoid;
        }

        .signature-section {
            position: absolute;
            bottom: 7pt;
            left: 0;
            right: 0;
            page-break-inside: avoid;
        }

        .footer {
            margin-top: 15pt;
        }

        .footer-page {
            position: fixed;
            bottom: -0.4in;
            left: 0;
            right: 0;
            font-size: 7.5pt;
        }

        .footer-page table {
            width: 100%;
        }

        .footer-page td {
            vertical-align: bottom;
        }

        .footer-page .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="page">
        <!-- COMPANY & HEADER -->
        <div class="row">
            <table class="w-full">
                <tr>
                    <td>
                        <strong>{{ $company?->name ?? 'PERUSAHAAN' }}</strong><br>
                        @if ($company?->address)
                            {{ $company->address }}<br>
                        @endif
                        @if ($company?->city)
                            {{ $company->city }}@if ($company?->post_code) - {{ $company->post_code }}@endif
                        @endif
                        @if ($company?->phone)
                            | Tel: {{ $company->phone }}
                        @endif
                        @if ($company?->fax)
                            | Fax: {{ $company->fax }}
                        @endif
                    </td>
                    <td class="text-end">
                        <strong><u>BON PENERIMAAN BARANG</u></strong><br />
                        {{ $goodsReceive->type?->label() ?? 'BPB' }}
                    </td>
                </tr>
            </table>
        </div>

        <br />

        <!-- PURPOSE / DETAILS -->
        <div class="row">
            <table>
                <tr>
                    <td class="meta-col-left">
                        <table>
                            <tr>
                                <td class="meta-lbl-left">Nomor BPB</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ $goodsReceive->number }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-lbl-left">Tanggal</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ $goodsReceive->created_at?->translatedFormat('d F Y') ?? '-' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-lbl-left">Nomor PO</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ $purchaseOrder?->number ?? '-' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-lbl-left">Surat Jalan</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ $goodsReceive->delivery_order ?: '-' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-lbl-left">Vendor</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ $vendor?->name ?? '-' }}</strong>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td class="meta-col-right">
                        <table>
                            <tr>
                                <td class="meta-lbl-right">Gudang/Proyek</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ $warehouse?->name ?? '-' }}-{{ $division?->name ?? '-' }}-{{ $project?->name ?? '-' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-lbl-right">Alamat</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ implode(', ', array_filter([$warehouseAddress?->address, $warehouseAddress?->city])) ?: '-' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-lbl-right">Penerima</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{{ $goodsReceive->user?->name ?? '-' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-lbl-right">Keterangan</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{!! nl2br(e($goodsReceive->description ?: '-')) !!}</strong>
                                </td>
                            </tr>
                            @if (filled($goodsReceive->notes))
                            <tr>
                                <td class="meta-lbl-right">Catatan</td>
                                <td class="meta-sep">:</td>
                                <td class="meta-val">
                                    <strong>{!! nl2br(e($goodsReceive->notes)) !!}</strong>
                                </td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <br />

        <!-- ITEMS TABLE -->
        <div class="row">
            <table class="table-item-border">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-code">Kode</th>
                        <th class="col-name">Nama Barang</th>
                        <th class="col-qty-po">Qty PO</th>
                        <th class="col-qty">Qty Terima</th>
                        <th class="col-unit">Satuan</th>
                        <th class="col-desc">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $item)
                        <tr>
                            <td class="col-no">{{ $index + 1 }}</td>
                            <td class="col-code">{{ $item->item?->code ?? '-' }}</td>
                            <td class="col-name">{{ $item->item?->name ?? '-' }}</td>
                            <td class="col-qty-po">{{ $item->purchaseOrderItem ? $qty($item->purchaseOrderItem->qty) : '-' }}</td>
                            <td class="col-qty">{{ $qty($item->qty) }}</td>
                            <td class="col-unit">{{ $item->item?->unit ?? '-' }}</td>
                            <td class="col-desc">{{ $item->description ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="item-empty">(Tidak ada item barang)</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <br />

        <!-- Spacer in normal flow reserving vertical space for signatures -->
        <div class="signature-space"></div>

        <!-- SIGNATURES & DISTRIBUTION FOOTER -->
        <div class="signature-section">
            <div class="row">
                <table class="table-signature">
                    <tr>
                        <td class="sig-col">Pengirim / Ekspedisi,</td>
                        <td class="sig-col">Diterima Oleh,</td>
                        <td class="sig-col">Diperiksa Oleh,</td>
                        <td class="sig-col">Mengetahui / Menyetujui,</td>
                    </tr>
                    <tr class="sig-space-1">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="sig-col">
                            <hr>Pengemudi / Ekspedisi
                        </td>
                        <td class="sig-col">
                            <hr>{{ $goodsReceive->user?->name ?? 'Petugas Gudang' }}
                        </td>
                        <td class="sig-col">
                            <hr>Pelaksana / QC Lapangan
                        </td>
                        <td class="sig-col">
                            <hr>Kepala Gudang / SM
                        </td>
                    </tr>
                </table>
            </div>

            <br />

            <div class="row footer">
                <p>Lembar 1: Bag. Akuntansi/Keuangan; Lembar 2: Bag. Pembelian; Lembar 3: Bag. Gudang/Logistik;</p>
            </div>
        </div>
    </div>

    <!-- PAGE NUMBERING FOOTER -->
    <div class="footer-page">
        <table>
            <tr>
                <td>
                    Printed at: {{ $printedAt->translatedFormat('d M Y - H:i') }} | GR No.
                    {{ $goodsReceive->number }}
                </td>
                <td class="text-end">
                    Halaman <span class="page-number"></span> dari {{ $pageCount }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
