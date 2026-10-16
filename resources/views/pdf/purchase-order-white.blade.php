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

    $purchaseOrderNumbers = $purchaseOrder->purchaseRequests->pluck('number')->filter()->implode(', ');
    $receivingPerson = $purchaseOrder->purchaseRequests->first()?->user?->name ?? '-';
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
            {{ $vendor?->city }}<br>
            Telp: {{ $vendor?->phone }}, Fax: {{ $vendor?->fax }}<br>
            Up: {{ $vendor?->contact }}
        </p>
    </div>

    <div class="row">
        <h3 class="centered"><u>PURCHASE ORDER</u></h3>
        <h3 class="sub-heading centered">No. {{ $purchaseOrder->number }}</h3>
    </div>

    <div class="row">
        <p>{{ nl2br($purchaseOrder->beginning) }}</p>

        <table class="table-item">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 4%;">No</th>
                    <th rowspan="2" style="width: 44%;">Item</th>
                    <th rowspan="2" style="width: 8%;">Qty</th>
                    <th rowspan="2" style="width: 8%;">Satuan</th>
                    <th colspan="2">Harga (<?= $data->vendor->currency->name ?>)</th>
                </tr>
                <tr>
                    <th style="width: 16%;">Satuan</th>
                    <th style="width: 20%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseOrder->items as $index => $item)
                    <tr>
                        <td align="center">{{ $index + 1 }}</td>
                        <td>{{ $item->name }}</td>
                        <td align="right">{{ $qty($item->quantity) }}</td>
                        <td align="center">{{ $item->unit }}</td>
                        <td align="right">{{ $idr($item->unit_price) }}</td>
                        <td align="right">{{ $idr($item->total_price) }}</td>
                    </tr>
                @endforeach
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
                                <td align="right">Diskon ({{ $purchaseOrder->discount }})<br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="right">Subtotal 2</td>
                            </tr>
                            <tr>
                                <!-- <td align="right">PPN (<?= $data->tax ?>%)</td> -->
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
                                <td align="right" style="width: 100%">{{ number_format($total, 2) }}</td>
                            </tr>
                            <tr>
                                <td align="right">{{ number_format($after_discount, 2) }}<br>&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="right">{{ number_format($sub_total_2, 2) }}</td>
                            </tr>
                            <tr>
                                <td align="right">{{ number_format($after_tax, 2) }}</td>
                            </tr>
                            <tr>
                                <td align="right">{{ number_format($grand_total, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tfoot>
        </table>

        <p><strong><u>Kondisi Pembelian:</u></strong></p>
    </div>

    <div class="row">
        <table class="table">
            <tr>
                <td style="width: 22%">Harga Total</td>
                <td style="width: 2%">:</td>
                <td style="width: 76%; line-height: 120%;">
                    {{ $data->vendor->currency->name }} {{ number_format($grand_total, 2) }}<br>
                    ({{ ucwords($terbilang) }} {{ $data->vendor->currency->description }} )
                </td>
            </tr>


            <tr>
                <td>Barang dikirim ke</td>
                <td>:</td>
                <td>
                    Proyek {{ $data->request->warehouse_name_print }}<br>
                    {{ $data->request->wh1_address }}<br>
                    {{ $data->request->wh1_city }}
                </td>
            </tr>
            <tr>
                <td>Penerima Barang</td>
                <td>:</td>
                <td>{{ $data->request->user_fullname ? $data->request->user_fullname : $data->request->username }}</td>
            </tr>
            <tr>
                <td>No. BPPB</td>
                <td>:</td>
                <td>{{ $data->request->number }}</td>
            </tr>
            <tr>
                <td>Faktur dan Kwitansi a.n.</td>
                <td>:</td>
                <td>
                    {{ $data->request->warehouse_2_department_name }}<br>
                    {{ $data->request->warehouse_2_address }}<br>
                    NPWP/NPPKP : {{ $data->request->warehouse_2_tax_number }}
                </td>
            </tr>
        </table>

        <p>Sekian pesanan kami, terimakasih.</p>
    </div>

    <div class="row">
        <table class="table table-centered">
            <tr>
                <td style="width: 30%; height: 100px;">&nbsp;<br><strong>{{ $data->vendor->name }}</strong></td>
                <td style="width: 40%">&nbsp;</td>
                <td style="width: 30%; height: 100px;">Hormat
                    Kami,<br><strong>{{ $data->request->warehouse_2_department_name }}</strong></td>
            </tr>
            <tr>
                <td><strong><u>{{ $data->vendor->contact }}</u></strong></td>
                <td>&nbsp;</td>
                <td><strong><u>{{ $data->request->warehouse_2_contact_name }}</u></strong></td>
            </tr>
            <tr>
                <td>Supplier</td>
                <td>&nbsp;</td>
                <td>{{ $data->request->warehouse_2_position }}</td>
            </tr>
        </table>
    </div>

    <div class="row">
        <p>
            <strong>CC:</strong><br>
            {{ nl2br($data->cc) }}
        </p>
    </div>

    <div class="footer">
    </div>
    <!-- </div> -->
    <!-- </page> -->
</body>

</html>