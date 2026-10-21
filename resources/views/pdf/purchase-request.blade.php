@php
    /** @var \App\Models\PurchaseRequest $purchaseRequest */
    /** @var \Illuminate\Support\Carbon|null $printedAt */
    /** @var int|null $pageCount */

    $company = $purchaseRequest->company;
    $warehouse = $purchaseRequest->warehouse;
    $warehouseAddress = $purchaseRequest->warehouseAddress;
    $division = $purchaseRequest->division;
    $project = $purchaseRequest->project;
    $items = $purchaseRequest->purchaseRequestItems;
    $printedAt ??= now();
    $pageCount ??= 1;

    $qty = fn(float|int|string|null $val): string => rtrim(rtrim(number_format((float) $val, 2, '.', ','), '0'), '.') ?: '0';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PR - {{ $purchaseRequest->number }}</title>
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
            min-width: 3.5rem;
            max-width: 3.5rem;
        }

        .meta-lbl-right {
            min-width: 5.5rem;
            max-width: 5.5rem;
        }

        .meta-sep {
            width: 1rem;
        }

        .meta-val {
            min-width: 17.5rem;
            max-width: 17.5rem;
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
            min-width: 1rem;
            max-width: 1rem;
            text-align: right;
        }

        .col-code {
            min-width: 3rem;
            max-width: 3rem;
        }

        .col-name {
            min-width: 15rem;
            max-width: 15rem;
        }

        .col-qty {
            min-width: 4.5rem;
            max-width: 4.5rem;
            text-align: right;
        }

        .col-unit {
            min-width: 3rem;
            max-width: 3rem;
        }

        .col-desc {
            min-width: 14.5rem;
            max-width: 14.5rem;
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
            height: 50.7pt;
        }

        .sig-space-2 td {
            height: 50.5pt;
        }

        .sig-w-2 {
            min-width: 2rem;
            max-width: 2rem;
        }

        .sig-w-8 {
            min-width: 8rem;
            max-width: 8rem;
        }

        .sig-w-8-5 {
            min-width: 8.5rem;
            max-width: 8.5rem;
        }

        .sig-w-9-5 {
            min-width: 9.5rem;
            max-width: 9.5rem;
        }

        .sig-w-10 {
            min-width: 10rem;
            max-width: 10rem;
        }

        .sig-w-10-5 {
            min-width: 10.5rem;
            max-width: 10.5rem;
        }

        /* Distribution & Pagination Footers */
        .signature-space {
            height: 215pt;
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
                            <strong><u>PURCHASE REQUEST</u></strong><br />Bon Pengajuan Pengadaan Barang / Jasa
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
                                    <td class="meta-lbl-left">Nomor</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{{ $purchaseRequest->number }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-lbl-left">Tanggal</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{{ $purchaseRequest->created_at?->translatedFormat('d F Y') ?? '-' }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-lbl-left">Proyek</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{{ $warehouse?->name ?? '-' }}-{{ $division?->name ?? '-' }}-{{ $project?->name ?? '-' }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-lbl-left">Alamat</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{{ implode(', ', array_filter([$warehouseAddress?->address, $warehouseAddress?->city])) ?: '-' }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td class="meta-col-right">
                            <table>
                                <tr>
                                    <td class="meta-lbl-right">Jenis</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{!! nl2br(e($purchaseRequest->description)) !!}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-lbl-right">Catatan</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{!! nl2br(e($purchaseRequest->notes)) !!}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-lbl-right">Memo</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{!! nl2br(e($purchaseRequest->memo)) !!}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-lbl-right">RAB</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{{ $purchaseRequest->boq }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="meta-lbl-right">Dibuat Oleh</td>
                                    <td class="meta-sep">:</td>
                                    <td class="meta-val">
                                        <strong>{{ $purchaseRequest->user?->name ?? '-' }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <br />

            <!-- ITEM TABLE -->
            <div class="row">
                <table class="table-item-border">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-code">Kode</th>
                            <th class="col-name">Nama Item&nbsp;</th>
                            <th class="col-qty">Qty</th>
                            <th class="col-unit">Satuan</th>
                            <th class="col-desc">Keterangan&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $item)
                            <tr>
                                <td class="col-no">{{ $index + 1 }}</td>
                                <td class="col-code">{{ $item->item?->code ?? '-' }}</td>
                                <td class="col-name">{{ $item->item?->name ?? '-' }}</td>
                                <td class="col-qty">{{ $qty($item->qty) }}</td>
                                <td class="col-unit">{{ $item->item?->unit ?? '-' }}</td>
                                <td class="col-desc">{{ $item->description ? '' : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="item-empty">(Tidak ada item barang)</td>
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
                        <td>Pemesan,</td>
                        <td>Dibuat Oleh,&nbsp;</td>
                        <td colspan="3">Diperiksa Oleh,</td>
                    </tr>
                    <tr class="sig-space-1">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="sig-w-8">
                            <hr>Pelaksana&nbsp;
                        </td>
                        <td class="sig-w-8-5">
                            <hr>Kepala Logistik
                        </td>
                        <td class="sig-w-8">
                            <hr>Site Manager
                        </td>
                        <td class="sig-w-9-5">
                            <hr>Kordinator Proyek
                        </td>
                        <td class="sig-w-8">
                            <hr>QS
                        </td>
                    </tr>
                </table>

                <br />

                <table class="table-signature">
                    <tr>
                        <td>Disetujui,&nbsp;</td>
                        <td>&nbsp;</td>
                        <td colspan="2">Direview Oleh,&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>Bagian Pembelian,&nbsp;</td>
                    </tr>
                    <tr class="sig-space-2">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="sig-w-8-5">
                            <hr>Project Manager
                        </td>
                        <td class="sig-w-2">&nbsp;</td>
                        <td class="sig-w-8">
                            <hr>Staf Audit
                        </td>
                        <td class="sig-w-10-5">
                            <hr>Kepala Audit Proyek
                        </td>
                        <td class="sig-w-2">&nbsp;</td>
                        <td class="sig-w-10">
                            <hr>Purchasing Manager
                        </td>
                    </tr>
                </table>
            </div>

            <br />
            <br />

            <div class="row footer">
                <p>Bag. Pembelian 1 (satu) lembar; Bag. Pemesanan 1 (satu) lembar; Bag. Logistik 1 (satu) lembar;</p>
            </div>
        </div>
    </div>

    <!-- PAGE NUMBERING FOOTER -->
    <div class="footer-page">
        <table>
            <tr>
                <td>
                    Printed at: {{ $printedAt->translatedFormat('d M Y - H:i') }} | PR No.
                    {{ $purchaseRequest->number }}
                </td>
                <td class="text-end">
                    Halaman <span class="page-number"></span> dari {{ $pageCount }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>