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

    $purchaseOrderNumbers = $purchaseOrder->purchaseRequests->pluck('number')->filter()->implode(', ');
    $receivingPerson = $purchaseOrder->purchaseRequests->first()?->user?->name ?? '-';

    $conditionRows = collect([
        'Syarat Pembayaran' => $purchaseOrder->po_payment_terms,
        'Cara Pembayaran' => $purchaseOrder->po_payment_method,
        'Tanggal Pengiriman' => $purchaseOrder->po_processing_date,
        'Waktu Pengiriman / Pengerjaan' => $purchaseOrder->po_processing_time,
        'Garansi' => $purchaseOrder->po_warranty,
        'Keterangan' => $purchaseOrder->po_description,
        'Lain-lain' => $purchaseOrder->po_miscellaneous,
    ])
        ->filter(fn(string $value): bool => filled($value))
        ->all();
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Print PO Putih</title>
    <style type="text/css">
        @page {
            margin: 25px 50px 25px 50px;
        }

        body {
            font-size: 10px;
            font-family: sans-serif;
            /*background-color: grey;*/
        }

        p {
            margin: 2px 0px;
            padding: 2px 0px;
            text-align: justify;
        }

        h3 {
            margin: 2px 0px;
            padding: 2px 0px;
        }

        .sub-heading {
            margin: 0px 0px;
            padding: 0px 0px 2px 0px;
        }

        .indent-left {
            padding-left: 25px;
        }

        .centered {
            text-align: center;
        }

        .row {
            width: 100%;
            margin: 2px 0px;
            padding: 2px 0px;
            /*background-color: grey;*/
        }

        .table {
            margin: 2px 0px;
            padding: 2px 0px;
            width: 100%;
            border-collapse: collapse;
        }

        .table tr {
            line-height: 100%;
            vertical-align: top;
        }

        .table-centered td {
            text-align: center;
        }

        .table-item {
            width: 100%;
            border-collapse: collapse;
        }

        .table-item thead,
        .table-item tfoot,
        .table-item th {
            border: 1px solid black;
        }

        .table-item td {
            border: 1px solid black;
            padding: 1px 5px;
        }

        .table-item .notes {
            margin: 0px;
            padding: 0px 20px 0px 20px;
        }

        .table-item .notes li {
            text-align: justify;
        }

        .table-item #amount {
            width: 100%;
            border-collapse: collapse;
            border: 0px;
        }

        .table-item #amount td {
            border: 0px;
            padding: 0px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 0px;
            right: 0px;
        }
    </style>
</head>

<body>
    <!-- <page size="A4"> -->
    <!-- <div class="page"> -->
    <div class="row" style="margin-top: 100px;">
        <p><strong>{{ $company?->city ? $company->city . ', ' : '' }}{{ $purchaseOrder->created_at?->translatedFormat('d M Y') }}</strong>
        </p>
        <p>
            Kepada:<br>
            <strong>{{ $vendor?->name }}</strong><br>
            {{ $vendor?->address }}<br>
            {{ $vendor->city }}<br>
            Telp: {{ $vendor?->phone }}, Fax: {{ $vendor?->fax }}<br>
            Up: {{ $vendor?->contact_person }}
        </p>
    </div>

    <div class="row">
        <h3 class="centered"><u>PURCHASE ORDER</u></h3>
        <h3 class="sub-heading centered">No. {{ $purchaseOrder->number }}</h3>
    </div>

    <div class="row">
        @if (filled($purchaseOrder->po_opening_letter))
            <p>{!! nl2br($purchaseOrder->po_opening_letter) !!}</p>
        @endif

        <table class="table-item">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 4%;">No</th>
                    <th rowspan="2" style="width: 44%;">Item</th>
                    <th rowspan="2" style="width: 8%;">Qty</th>
                    <th rowspan="2" style="width: 8%;">Satuan</th>
                    <th colspan="2">Harga</th>
                </tr>
                <tr>
                    <th style="width: 16%;">Satuan (Rp)</th>
                    <th style="width: 20%;">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchaseOrder->purchaseOrderItems as $index => $item)

                    <tr>
                        <td align="center">{{ $index + 1 }}</td>
                        <td>
                            {{ $item->item?->name }}
                            @if (filled($item->description))
                                <br>
                                @foreach (collect(explode("\n", $item->description))->filter() as $line)
                                    {{ trim($line) }}<br>
                                @endforeach
                            @endif
                        </td>
                        <td align="right">{{ $qty($item->qty) }}</td>
                        <td align="center">{{ $item->item?->unit }}</td>
                        <td align="right">{{ $idr($item->price) }}</td>
                        <td align="right">{{ $idr($item->getSubtotalAmount()) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">-</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">
                        &nbsp;
                    </td>
                    <td>
                        <table id="amount">
                            <tr>
                                <td align="right" style="width: 100%">Subtotal</td>
                            </tr>
                            <tr>
                                <td align="right">Diskon<br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="right">Subtotal 2</td>
                            </tr>
                            <tr>
                                <td align="right">PPN</td>
                            </tr>
                            <tr>
                                <td align="right">Grand Total</td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table id="amount">
                            <tr>
                                <td align="right" style="width: 100%">{{ $idr($summary['subtotal']) }}</td>
                            </tr>
                            <tr>
                                <td align="right">{{ $idr($summary['discount']) }}<br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="right">{{ $idr($summary['subtotal_after_discount']) }}</td>
                            </tr>
                            <tr>
                                <td align="right">{{ $idr($summary['tax_amount']) }}</td>
                            </tr>
                            <tr>
                                <td align="right">{{ $idr($summary['grand_total']) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="row">
        <table class="table">
            <tr>
                <td style="width: 22%">Harga Total</td>
                <td style="width: 2%">:</td>
                <td style="width: 76%; line-height: 120%;">
                    {{ number_format((float) $summary['grand_total'], 2) }}<br>
                    ({{ ucwords($terbilang) }})
                </td>
            </tr>
        </table>

        @if ($conditionRows !== [])
            <p><strong><u>Kondisi Pembelian:</u></strong></p>
            <table class="table">
                @foreach ($conditionRows as $condition => $value)
                    <tr>
                        <td style="width: 22%">{{ $condition }}</td>
                        <td style="width: 2%">:</td>
                        <td style="width: 76%; text-align: justify;">{!! nl2br($value) !!}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <table class="table">
            <tr>
                <td style="width: 22%">Barang dikirim ke</td>
                <td style="width: 2%">:</td>
                <td style="width: 76%; line-height: 120%;">
                    <div>{{ $project?->name ? 'Proyek ' . $project->name : '-' }}</div>
                    @if ($warehouseAddress?->address)
                        <div>{{ $warehouseAddress->address }}</div>
                    @endif
                    @if ($warehouseAddress?->city)
                        <div>{{ $warehouseAddress->city }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td>Penerima Barang</td>
                <td>:</td>
                <td>{{ $receivingPerson }}</td>
            </tr>
            <tr>
                <td>No. BPPB</td>
                <td>:</td>
                <td>{{ $purchaseOrderNumbers }}</td>
            </tr>
            <tr>
                <td>Faktur dan Kwitansi a.n.</td>
                <td>:</td>
                <td>
                    <div>{{ $company?->name }}</div>
                    @if ($company?->address)
                        <div>{{ $company->address }}</div>
                    @endif
                    @if ($company?->tax_number)
                        <div>NPWP/NPPKP: {{ $company->tax_number }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <p>Sekian pesanan kami, terimakasih.</p>
    </div>

    <div class="row">
        <table class="table table-centered">
            <tr>
                <td style="width: 30%; height: 100px;">&nbsp;<br><strong>{{ $vendor->name }}</strong></td>
                <td style="width: 40%">&nbsp;</td>
                <td style="width: 30%; height: 100px;">Hormat Kami,<br><strong>{{ $company->name }}</strong></td>
            </tr>
            <tr>
                <td><strong><u>{{ $vendor->contact }}</u></strong></td>
                <td>&nbsp;</td>
                <td><strong><u>{{ $company->contact_person }}</u></strong></td>
            </tr>
            <tr>
                <td>Supplier</td>
                <td>&nbsp;</td>
                <td>{{ $company->contact_person_position }}</td>
            </tr>
        </table>
    </div>

    <div class="row">
        @if (filled($purchaseOrder->po_carbon_copy))
            <p>
                <strong>CC:</strong><br>
                {{ nl2br($purchaseOrder->po_carbon_copy) }}
            </p>
        @endif
    </div>

    <div class="footer">
    </div>
    <!-- </div> -->
    <!-- </page> -->
</body>

</html>