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
    $deliveryDate = $purchaseOrder->delivery_notes ?: ($purchaseOrder->delivery_date?->translatedFormat('d M Y') ?: '-');
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Print PO Merah</title>
    <style>
        @page {
            size: A4;
            margin: 1cm 0.5cm;
        }

        /* .page {
        width: 21cm;
        height: 29.7cm;
				margin: 1cm 0.5cm;
        border: 1px solid grey;
      } */

        body {
            font-size: 10pt;
            font-family: Courier, monospace Courier;
            line-height: 1.2em;
            font-weight: bold;
            /* color: darkblue; */
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

        .row {
            position: relative;
            /* padding-bottom: 1em; */
        }

        .dynamic-row {
            min-height: 581pt;
        }

        .table-item-border {
            margin-top: -2px;
            margin-left: -3.5px;
            width: 47rem;
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

        .footer {
            position: fixed;
            bottom: 9.5px;
            left: 0;
            right: 0;
        }

        .footer-page {
            position: fixed;
            bottom: -0.4in;
            left: 0;
            right: 0;
            font-size: 7pt;
        }

        .footer-page .page-number:before {
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
            <!-- TITLE -->
            <div class="row">
                <h1>
                    <u>PURCHASE ORDER</u><br />
                    (SURAT PESANAN)
                </h1>
            </div>
            <br />

            <!-- PURPOSE -->
            <div class="row">
                <table>
                    <tr>
                        <td style="min-width: 24rem; max-width: 24rem; padding-right: 0.5rem">
                            <table>
                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">Kepada:</td>
                                </tr>
                                <tr>
                                    <td style="padding-left: 2rem; min-width: 22rem; max-width: 22rem">
                                        <strong>
                                            {{ strtoupper($vendor?->name) }}<br />
                                            {{ strtoupper($vendor?->address) }}<br />
                                            {{ strtoupper($vendor?->city) }}<br />
                                        </strong>
                                        <table>
                                            <tr>
                                                <td style="min-width: 2.5rem; max-width: 2.5rem">Phone</td>
                                                <td style="width: 1rem">:</td>
                                                <td style="min-width: 18.5rem; max-width: 18.5rem">
                                                    <strong>{{ $vendor?->phone ?: '-' }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="min-width: 2.5rem; max-width: 2.5rem">Fax</td>
                                                <td style="width: 1rem">:</td>
                                                <td style="min-width: 18.5rem; max-width: 18.5rem">
                                                    <strong>{{ $vendor?->fax ?: '-' }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="min-width: 2.5rem; max-width: 2.5rem">UP</td>
                                                <td style="width: 1rem">:</td>
                                                <td style="min-width: 18.5rem; max-width: 18.5rem">
                                                    <strong>{{ strtoupper($vendor?->contact_person ?: '-') }}</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td style="padding-left: 0.5rem; min-width: 22rem; max-width: 22rem">
                            <table>
                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">Tanggal</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ strtoupper($purchaseOrder->created_at?->translatedFormat('d M y') ?? '') }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">No. PO</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ $purchaseOrder->number }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">PR/BPPB</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ $purchaseOrderNumbers ?: '-' }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">&nbsp;</td>
                                    <td style="width: 1rem">&nbsp;</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <table>
                                            <tr>
                                                <td style="min-width: 9rem; max-width: 9rem">Syarat Pembayaran</td>
                                                <td style="width: 1rem">:</td>
                                                <td style="min-width: 12rem; max-width: 12rem">
                                                    <strong>{{ $purchaseOrder->terms ?: '-' }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="min-width: 9rem; max-width: 9rem">Tanggal Pengiriman</td>
                                                <td style="width: 1rem">:</td>
                                                <td style="min-width: 12rem; max-width: 12rem">
                                                    <strong>{{ $deliveryDate }}</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <br />

            <!-- OPENING -->
            <div class="row">
                <p>
                    Sesuai dengan penawaran harga yang telah diberikan, pesanan barang-barang tersebut dibawah ini agar
                    dikirim kepada bagian penerimaan barang (Receiving Section) dengan mengikuti ketentuan-ketentuan
                    yang tertera dibawah ini.
                </p>
            </div>
            <br />

            <!-- ITEM TABLE -->
            <div class="row">

                <table class="table-item-border">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">&nbsp;Jumlah</th>
                            <th rowspan="2">Satuan</th>
                            <th rowspan="2">Nama Barang</th>
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
                                <td class="text-end" style="min-width: 1rem; max-width: 1rem;">
                                    <strong>{{ $index + 1 }}</strong>
                                </td>
                                <td class="text-end" style="min-width: 4.5rem; max-width: 4.5rem;">
                                    <strong>{{ $qty($item->qty) }}</strong>
                                </td>
                                <td style="min-width: 3rem; max-width: 3rem;">
                                    <strong>{{ $item->item?->unit }}</strong>
                                </td>
                                <td style="min-width: 16.5rem; max-width: 16.5rem;">
                                    <strong>{{ $item->item?->name }}</strong>
                                    @if (filled($item->description))
                                        <strong>
                                            @foreach (collect(explode("\n", $item->description))->filter() as $line)
                                                {{ trim($line) }}<br>
                                            @endforeach
                                        </strong>
                                    @endif
                                </td>
                                <td class="text-end" style="min-width: 8rem; max-width: 8rem;">
                                    <strong>{{ $idr($item->price) }}</strong>
                                </td>
                                <td class="text-end" style="min-width: 8rem; max-width: 8rem;">
                                    <strong>{{ $idr($item->getSubtotalAmount()) }}</strong>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="text-center">-</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <table class="table-item-border">
                    <tbody>
                        <tr>
                            <td class="text-end" style="min-width: 1rem; max-width: 1rem;">&nbsp;</td>
                            <td class="text-end" style="min-width: 4.5rem; max-width: 4.5rem;">&nbsp;</td>
                            <td style="min-width: 3rem; max-width: 3rem;">&nbsp;</td>
                            <td style="min-width: 16.5rem; max-width: 16.5rem;">
                                &nbsp;
                            </td>
                            <td class="text-end" style="min-width: 8rem; max-width: 8rem;">&nbsp;</td>
                            <td class="text-end" style="min-width: 8rem; max-width: 8rem;">&nbsp;</td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="4" style="min-width: 28rem; max-width: 28rem">
                                <ul>
                                    <li>PO ini bisa dibatalkan sewaktu-waktu oleh kedua belah pihak.</li>
                                    <li>
                                        Apabila ada uang muka, maka PO ini belum mengikat dan
                                        dapat dibatalkan jika uang muka belum dibayarkan. Jika
                                        uang muka tersebut telah dibayarkan, maka PO tidak dapat
                                        dibatalkan.
                                    </li>
                                </ul>
                            </td>

                            <td style="min-width: 8rem; max-width: 8rem;">
                                <strong>Subtotal</strong><br />
                                <strong>Diskon</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br />
                                <strong>Subtotal-2</strong><br />
                                <strong>PPN</strong>&nbsp;&nbsp;&nbsp;<strong>{{ $taxPercentage + 0 }}%</strong><br />
                                <strong>Grand Total</strong>
                            </td>
                            <td class="text-end" style="min-width: 8rem; max-width: 8rem;">
                                <strong>{{ $idr($summary['subtotal']) }}<br /></strong>
                                <strong>{{ $idr($summary['discount']) }}<br /></strong>
                                <strong>{{ $idr($summary['subtotal_after_discount']) }}<br /></strong>
                                <strong>{{ $idr($summary['tax_amount']) }}<br /></strong>
                                <strong>{{ $idr($summary['grand_total']) }}</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <br />

            <!-- DESCRIPTION -->
            <div class="row">
                <p>Keterangan:</p>
                <p style="padding-left: 2rem">
                    <strong>{{ strtoupper($terbilang) }}.</strong>
                </p>
            </div>
            <br />
        </div>

        <div style="page-break-inside: avoid">
            <!-- NOTES -->
            <div class="row">
                <table>
                    <tr>
                        <td style="min-width: 12rem; max-width: 12rem">Barang segera dikirim ke</td>
                        <td style="width: 1rem">:</td>
                        <td style="min-width: 34rem; max-width: 34rem">
                            <div class="text-bold">{{ $project?->name ? 'Proyek ' . $project->name : '-' }}</div>
                            @if ($warehouseAddress?->address)
                                <div>{{ $warehouseAddress->address }}</div>
                            @endif
                            @if ($warehouseAddress?->city)
                                <div>{{ $warehouseAddress->city }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="min-width: 12rem; max-width: 12rem">Penerima Barang</td>
                        <td style="width: 1rem">:</td>
                        <td style="min-width: 34rem; max-width: 34rem">
                            <strong>{{ $receivingPerson }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="min-width: 12rem; max-width: 12rem">DO/Faktur/Kwitansi a.n.</td>
                        <td style="width: 1rem">:</td>
                        <td style="min-width: 34rem; max-width: 34rem">
                            <div class="text-bold">{{ $company?->name }}</div>
                            @if ($company?->address)
                                <div>{{ $company->address }}</div>
                            @endif
                            @if ($company?->tax_number)
                                <div>NPWP/NPPKP: {{ $company->tax_number }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <br />

            <!-- SIGNATURE -->
            <div class="row">
                <table>
                    <tr>
                        <td style="min-width: 17rem; max-width: 17rem; text-align: center">
                            Hormat Kami,
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <u><strong>{{ $company?->contact_person ?: 'Staff Purchasing' }}</strong></u><br />
                            <strong>
                                {{ $company?->contact_person_position }}
                            </strong>
                        </td>

                        <td style="min-width: 13rem; max-width: 13rem; text-align: center">&nbsp;</td>

                        <td style="min-width: 17rem; max-width: 17rem; text-align: center">
                            Menyetujui,&nbsp;
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <br />
                            <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br />
                            <strong>Supplier</strong>
                        </td>
                    </tr>
                </table>
            </div>
            <br />

            <br />

            <div class="row footer">
                <table style="min-width: 47rem; max-width: 47rem">
                    <tr>
                        <td style="min-width: 10rem; max-width: 10rem; text-align: center">White: Supplier&nbsp;</td>
                        <td style="min-width: 8rem; max-width: 8rem; text-align: center">Red: Finance</td>
                        <td style="min-width: 11rem; max-width: 11rem; text-align: center">Green: Purchasing&nbsp;</td>
                        <td style="min-width: 8rem; max-width: 8rem; text-align: center">Blue: Audit&nbsp;</td>
                        <td style="min-width: 10rem; max-width: 10rem; text-align: center">Yellow: Logistik</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="footer-page">
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