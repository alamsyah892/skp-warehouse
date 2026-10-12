@php
    /** @var \App\Models\PurchaseOrder $purchaseOrder */
    /** @var array<string, float> $summary */
    /** @var string $terbilang */
    /** @var \Illuminate\Support\Carbon $printedAt */
    /** @var int|null $pageCount */

    $company = $purchaseOrder->company;
    $vendor = $purchaseOrder->vendor;
    $project = $purchaseOrder->project;
    $warehouseAddress = $purchaseOrder->warehouseAddress;

    $idr = fn(float|int|string|null $value): string => number_format((float) $value, 2, '.', ',');

    $qty = function (float|int|string|null $value): string {
        $formatted = number_format((float) $value, 2, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    };

    $taxPercentage = (float) ($purchaseOrder->tax_percentage ?? 0);
    $discountPercentage = $summary['subtotal'] > 0
        ? round($summary['discount'] / $summary['subtotal'] * 100, 2)
        : 0;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Print KO Merah</title>
    <style type="text/css">
        @page {
            size: A4;
            margin: 1.5in 0.5in 0.5in 0.5in;
        }

        body {
            font-size: 8pt;
            font-family: sans-serif;
            /* line-height: 1.2em; */
            /* color: darkblue; */
        }

        /* body,
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
		} */

        h1 {
            text-align: center;
            font-size: 10pt;
        }

        h2 {
            font-size: 8pt;
        }

        p {
            text-align: justify;
        }

        table {
            /* margin: 0; */
            /* padding: 0; */
            border-collapse: collapse;
            text-wrap: wrap;

            width: 100%;
        }

        td,
        th {
            vertical-align: top;
            word-wrap: break-word;
        }

        ul {
            margin-top: 0;
            margin-bottom: 0;
            /* list-style-type: none; */
        }

        .row {
            position: relative;
            /* padding-bottom: 1em; */
        }

        .dynamic-row {
            min-height: 512pt;
        }

        .table-item-border {
            /* margin-top: -2px; */
            /* margin-left: -3.5px; */
            /* width: 47rem; */

            width: 100%;
        }

        .table-item-border th {
            padding: 0 0.5rem;
            border: 1px solid black;
        }

        .table-item-border td {
            padding: 0 0.5rem;
            border-right: 1px solid black;
            border-left: 1px solid black;
        }

        .table-item-border tfoot {
            border: 1px solid black;
        }

        .table-item-border tbody {
            min-height: 256pt;
        }

        .footer {
            position: fixed;
            bottom: -0.4in;
            left: 0;
            right: 0;
            font-size: 7pt;
        }

        .footer .page-number:before {
            content: counter(page);
        }

        .text-end {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="dynamic-row">
            <!-- PURPOSE -->
            <div class="row">
                <p>{{ $company?->city ? $company->city . ', ' : '' }}{{ $purchaseOrder->created_at?->translatedFormat('d M Y') }}
                </p>
                <p>
                    Kepada:<br />
                    <strong>{{ $vendor?->name }}</strong><br />
                    {{ $vendor?->address }}<br />
                    {{ $vendor?->city }}<br />
                    Telp: {{ $vendor?->phone }}, Fax: {{ $vendor?->fax }}<br />
                    UP: {{ $vendor?->contact_person }}
                </p>
            </div>

            <!-- TITLE -->
            <div class="row">
                <h1>
                    <u>KONFIRMASI ORDER</u>
                </h1>
            </div>

            <!-- OPENING -->
            <div class="row">
                <p>Dengan hormat, <br />Bersama dengan ini kami memesan barang-barang sebagai berikut:</p>
            </div>

            <!-- ITEM TABLE -->
            <div class="row">
                <table class="table-item-border">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Item</th>
                            <th rowspan="2">Qty</th>
                            <th rowspan="2">Satuan</th>
                            <th colspan="2">Harga</th>
                        </tr>
                        <tr>
                            <th>Satuan (Rp)</th>
                            <th>Jumlah (Rp)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($purchaseOrder->purchaseOrderItems as $index => $item)

                            <tr>
                                <td class="text-end">
                                    {{ $index + 1 }}
                                </td>
                                <td>
                                    {{ $item->item?->name }}
                                    @if (filled($item->description))

                                        @foreach (collect(explode("\n", $item->description))->filter() as $line)
                                            &bull; {{ trim($line) }}<br>
                                        @endforeach

                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ $qty($item->qty) }}
                                </td>
                                <td>
                                    {{ $item->item?->unit }}
                                </td>
                                <td class="text-end">
                                    {{ $idr($item->price) }}
                                </td>
                                <td class="text-end">
                                    {{ $idr($item->getSubtotalAmount()) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">-</td>
                            </tr>
                        @endforelse


                    </tbody>

                    <tfoot>
                        <tr>
                            <td rowspan="5" colspan="4">
                                &nbsp;
                            </td>
                            <td><strong>Subtotal</strong></td>
                            <td class="text-end">
                                {{ $idr($summary['subtotal']) }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Diskon</strong></td>
                            <td class="text-end">
                                {{ $idr($summary['discount']) }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="text-end">
                                {{ $idr($summary['subtotal_after_discount']) }}
                            </td>
                        </tr>
                        @if ($taxPercentage > 0)
                            <tr>
                                <!-- <td><strong>PPN</strong>&nbsp;&nbsp;&nbsp;%</td> -->
                                <td><strong>PPN</strong>&nbsp;&nbsp;&nbsp;{{ $taxPercentage + 0 }}%</td>
                                <td class="text-end">
                                    {{ $idr($summary['tax_amount']) }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Grand Total</strong></td>
                            <td class="text-end">
                                {{ $idr($summary['grand_total']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- DESCRIPTION -->
            <div class="row" style="page-break-inside: avoid">
                <h2>
                    <u>Kondisi Pembelian:</u>
                </h2>

                <table>
                    <tr>
                        <td style="width: 24%">Harga Total</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%">
                            Rp. {{ $idr($summary['grand_total']) }}<br />
                            ({{ $terbilang }} Rupiah)
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 24%">Syarat Pembayaran</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%; text-align: justify;">
                            {{ $purchaseOrder->terms ?: '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 24%">Tanggal Pengiriman</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%; text-align: justify;">
                            {{ $purchaseOrder->delivery_date ? \Illuminate\Support\Carbon::parse($purchaseOrder->delivery_date)->format('d M Y') : '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 24%">Metode Pengiriman</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%; text-align: justify;">
                            {{ $purchaseOrder->shipping_method ?: '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 24%">Catatan Pengiriman</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%; text-align: justify;">
                            {{ $purchaseOrder->delivery_notes ?: '-' }}
                        </td>
                    </tr>
                </table>
            </div>
            <br />

            <!-- NOTES -->
            <div class="row" style="page-break-inside: avoid">
                <table>
                    <tr>
                        <td style="width: 24%">Pengiriman ke</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%">
                            Proyek {{ $project?->name }}<br />
                            {{ collect([$warehouseAddress?->address, $warehouseAddress?->city])->filter()->implode(' - ') ?: '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 24%">Faktur dan Kwitansi a.n.</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%">
                            {{ $company?->name }}<br />
                            {{ $company->address }}<br />
                            NPWP/NPPKP : {{ $company->tax_number }}
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 24%">No. PO</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 74%">
                            <strong>{{ $purchaseOrder->number }}</strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="page-break-inside: avoid">
            <!-- ENDING -->
            <div class="row">
                <p>Atas perhatiannya kami ucapkan terimakasih.</p>
            </div>

            <!-- SIGNATURE -->
            <div class="row">
                <table>
                    <tr>
                        <td style="width: 33%; text-align: center">
                            Hormat Kami,<br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br />
                            Staff Purchasing
                        </td>

                        <td style="width: 34%; text-align: center">
                            Menyetujui,<br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br />
                            Supplier
                        </td>

                        <td style="width: 33%; text-align: center">
                            Mengetahui,<br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br />
                            Koordinator Purchasing
                        </td>
                    </tr>
                </table>
            </div>

            <!-- PS -->
            <div class="row">
                <p>PS: <strong>Mohon segera di-fax balik ke nomor {{ $company->fax }} setelah dicap dan
                        ditandatangani.</strong>
                </p>
                <p class="text-end">
                    BPPB No. {{ $purchaseOrder->purchaseRequests->pluck('number')->implode(', ') ?: '-' }}<br>
                    PO No. {{ $purchaseOrder->number }}
                </p>
            </div>
        </div>
    </div>

    <div class="footer">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: left; vertical-align: bottom;">
                    Printed at: {{ $printedAt->translatedFormat('d M Y - H:i') }} | PO No. {{ $purchaseOrder->number }}
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    Page: <span class="page-number"></span>/{{ $pageCount ?? 1 }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>