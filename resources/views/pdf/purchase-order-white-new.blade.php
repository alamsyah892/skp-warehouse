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
        ->filter(fn (string $value): bool => filled($value))
        ->all();
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Print PO Putih</title>
    <style type="text/css">
        @page {
            size: A4;
            margin: 1.8cm 1.5cm 2cm 1.5cm;
        }

        body {
            font-size: 9pt;
            font-family: Arial, Helvetica, sans-serif;
            color: #111111;
            line-height: 1.35;
        }

        h1,
        h2,
        h3,
        p,
        table,
        tr,
        td,
        th {
            margin: 0;
            padding: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-justify {
            text-align: justify;
        }

        .fw-bold {
            font-weight: bold;
        }

        .mb-05 {
            margin-bottom: 3pt;
        }

        .mb-1 {
            margin-bottom: 4pt;
        }

        .mb-2 {
            margin-bottom: 6pt;
        }

        .mb-3 {
            margin-bottom: 8pt;
        }

        .mb-4 {
            margin-bottom: 10pt;
        }

        .divider {
            border-top: 1px solid #111111;
            margin: 4pt 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 2pt 4pt;
            vertical-align: top;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #111111;
        }

        .table-bordered thead th {
            background-color: #f8f8f8;
            font-weight: bold;
            font-size: 8.5pt;
        }

        .table-amount td {
            padding: 1pt 3pt;
            border: none;
        }

        .table-amount .amount-label {
            font-weight: bold;
            padding-right: 6pt;
        }

        .info-table td:first-child {
            width: 24%;
            font-weight: bold;
        }

        .info-table td:nth-child(2) {
            width: 2%;
        }

        .info-table td:nth-child(3) {
            width: 74%;
        }

        .signature-table td {
            height: 50pt;
            vertical-align: bottom;
            text-align: center;
        }

        .signature-line {
            margin-top: 28pt;
            border-top: 1px solid #111111;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-title {
            margin-top: 2pt;
            font-weight: bold;
        }

        .page-header {
            margin-bottom: 10pt;
        }

        .po-title {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 1pt;
            text-transform: uppercase;
        }

        .po-number {
            font-size: 10pt;
            font-weight: bold;
        }

        .opening-letter {
            margin-bottom: 8pt;
            text-align: justify;
        }

        .footer {
            position: fixed;
            bottom: -1.4cm;
            left: 0;
            right: 0;
            font-size: 8pt;
            color: #666666;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <p class="fw-bold mb-05">
            {{ $company?->city ? strtoupper($company->city) . ', ' : '' }}{{ $purchaseOrder->created_at?->translatedFormat('d F Y') }}
        </p>

        <div class="mb-3">
            <p class="fw-bold mb-05">Kepada:</p>
            <p class="fw-bold">{{ $vendor?->name }}</p>
            @if ($vendor?->address)
                <p>{{ $vendor->address }}</p>
            @endif
            @if ($vendor?->city)
                <p>{{ $vendor->city }}</p>
            @endif
            <p>Telp: {{ $vendor?->phone ?: '-' }} | Fax: {{ $vendor?->fax ?: '-' }} | Up: {{ $vendor?->contact_person ?: '-' }}</p>
        </div>
    </div>

    <div class="text-center mb-3">
        <h1 class="po-title"><u>Purchase Order</u></h1>
        <p class="po-number">No. {{ $purchaseOrder->number }}</p>
    </div>

    @if (filled($purchaseOrder->po_opening_letter))
        <div class="opening-letter">
            {!! nl2br(e($purchaseOrder->po_opening_letter)) !!}
        </div>
    @endif

    <table class="table table-bordered mb-2">
        <thead>
            <tr>
                <th rowspan="2" class="text-center" style="width: 4%;">No</th>
                <th rowspan="2" style="width: 45%;">Nama Barang</th>
                <th rowspan="2" class="text-center" style="width: 9%;">Qty</th>
                <th rowspan="2" class="text-center" style="width: 9%;">Satuan</th>
                <th colspan="2" class="text-center" style="width: 33%;">Harga</th>
            </tr>
            <tr>
                <th class="text-center">Satuan (Rp)</th>
                <th class="text-center">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchaseOrder->purchaseOrderItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->item?->name }}
                        @if (filled($item->description))
                            @foreach (collect(explode("\n", $item->description))->filter() as $line)
                                <div style="font-size: 8.5pt;">• {{ trim($line) }}</div>
                            @endforeach
                        @endif
                    </td>
                    <td class="text-right">{{ $qty($item->qty) }}</td>
                    <td class="text-center">{{ $item->item?->unit ?: '-' }}</td>
                    <td class="text-right">{{ $idr($item->price) }}</td>
                    <td class="text-right">{{ $idr($item->getSubtotalAmount()) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">- Tidak ada item -</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="display: flex; justify-content: flex-end; margin-bottom: 10pt;">
        <table class="table-amount" style="width: 38%;">
            <tr>
                <td class="text-right amount-label">Subtotal</td>
                <td class="text-right">{{ $idr($summary['subtotal']) }}</td>
            </tr>
            <tr>
                <td class="text-right amount-label">Diskon</td>
                <td class="text-right">{{ $idr($summary['discount']) }}</td>
            </tr>
            <tr>
                <td class="text-right amount-label">Subtotal Setelah Diskon</td>
                <td class="text-right">{{ $idr($summary['subtotal_after_discount']) }}</td>
            </tr>
            <tr>
                <td class="text-right amount-label">PPN</td>
                <td class="text-right">{{ $idr($summary['tax_amount']) }}</td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="divider"></div>
                </td>
            </tr>
            <tr>
                <td class="text-right amount-label">Grand Total</td>
                <td class="text-right fw-bold">{{ $idr($summary['grand_total']) }}</td>
            </tr>
        </table>
    </div>

    <div class="mb-2">
        <p class="fw-bold mb-05">Terbilang:</p>
        <p class="text-justify">{{ strtoupper($terbilang) }} RUPIAH.</p>
    </div>

    @if ($conditionRows !== [])
        <div class="mb-2">
            <p class="fw-bold mb-05"><u>Kondisi Pembelian</u></p>
            <table class="table info-table">
                @foreach ($conditionRows as $condition => $value)
                    <tr>
                        <td>{{ $condition }}</td>
                        <td>:</td>
                        <td class="text-justify">{!! nl2br(e($value)) !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="mb-2">
        <p class="fw-bold mb-05"><u>Informasi Pengiriman</u></p>
        <table class="table info-table">
            <tr>
                <td>Barang Dikrim Ke</td>
                <td>:</td>
                <td>
                    @if ($project?->name)
                        <div>Proyek {{ $project->name }}</div>
                    @endif
                    @if ($warehouseAddress?->address)
                        <div>{{ $warehouseAddress->address }}</div>
                    @endif
                    @if ($warehouseAddress?->city)
                        <div>{{ $warehouseAddress->city }}</div>
                    @endif
                    @if (!$project?->name && !$warehouseAddress?->address && !$warehouseAddress?->city)
                        <div>-</div>
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
                <td>{{ $purchaseOrderNumbers ?: '-' }}</td>
            </tr>
            <tr>
                <td>Faktur & Kwitansi a.n.</td>
                <td>:</td>
                <td>
                    <div class="fw-bold">{{ $company?->name }}</div>
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

    <p class="mb-3">Sekian pesanan kami, atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>

    <table class="table signature-table mb-2">
        <tr>
            <td style="width: 30%;">
                <div class="signature-line"></div>
                <p class="signature-title">{{ $vendor?->name ?: 'Supplier' }}</p>
                <p>Supplier</p>
            </td>
            <td style="width: 40%;"></td>
            <td style="width: 30%;">
                <div class="signature-line"></div>
                <p class="signature-title">{{ $company?->contact_person ?: 'Staff Purchasing' }}</p>
                <p>{{ $company?->contact_person_position ?: 'Purchasing' }}</p>
            </td>
        </tr>
    </table>

    @if (filled($purchaseOrder->po_carbon_copy))
        <div class="mb-1">
            <p class="fw-bold">CC:</p>
            <p>{!! nl2br(e($purchaseOrder->po_carbon_copy)) !!}</p>
        </div>
    @endif

    <div class="footer">
        <table class="table" style="border: none;">
            <tr>
                <td style="text-align: left; padding: 0; border: none;">
                    Dicetak pada: {{ $printedAt->translatedFormat('d M Y - H:i') }} | PO No. {{ $purchaseOrder->number }}
                </td>
                <td style="text-align: right; padding: 0; border: none;">
                    Halaman: <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $x = 500;
            $y = 820;
            $text = "Halaman " . $PAGE_NUM . " dari " . $PAGE_COUNT;
            $font = $fontMetrics->get_font("Arial", "normal");
            $size = 8;
            $color = array(0,0,0);
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>
</body>

</html>
