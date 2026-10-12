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

    $idr = fn (float|int|string|null $value): string => number_format((float) $value, 2, '.', ',');

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
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Purchase Order</title>
    <style>
        @page {
            size: A4;
            margin: 14mm 12mm 16mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .mono { font-family: Courier, monospace; }

        .doc-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: center;
            margin: 2px 0 0;
        }

        .doc-subtitle {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }

        table.parties {
            width: 100%;
            border-collapse: collapse;
        }

        table.parties td {
            vertical-align: top;
        }

        td.party-left {
            width: 55%;
        }

        td.party-right {
            width: 45%;
        }

        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table.meta td {
            padding: 1px 0;
            vertical-align: top;
        }

        table.meta td.meta-label {
            width: 38%;
        }

        table.meta td.meta-colon {
            width: 4%;
        }

        table.delivery-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.delivery-meta td {
            padding: 1px 0;
            vertical-align: top;
        }

        table.delivery-meta td.delivery-label {
            width: 20%;
        }

        table.delivery-meta td.delivery-colon {
            width: 2%;
        }

        .intro { margin-bottom: 8px; }

        table.items {
            width: 100%;
            border-collapse: collapse;
        }

        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
        }

        table.items th {
            background-color: #e5e5e5;
            text-align: center;
            font-weight: bold;
        }

        .item-name { font-weight: bold; }

        .item-description {
            margin-top: 3px;
            color: #333;
        }

        table.bottom {
            width: 100%;
            border-collapse: collapse;
        }

        table.bottom td {
            vertical-align: top;
        }

        td.conditions-cell {
            width: 52%;
            padding: 6px 8px 0 0;
        }

        td.conditions-cell .condition-note {
            margin-bottom: 6px;
        }

        td.summary-cell {
            width: 48%;
        }

        table.summary {
            width: 100%;
            border-collapse: collapse;
        }

        table.summary td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        table.summary td.summary-value {
            text-align: right;
        }

        table.summary tr.grand-total td {
            font-weight: bold;
            background-color: #e5e5e5;
        }

        .keterangan {
            margin-top: 8px;
            margin-bottom: 10px;
        }

        table.delivery {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.delivery td {
            padding: 1px 0;
            vertical-align: top;
        }

        table.delivery td.delivery-label {
            width: 20%;
        }

        table.delivery td.delivery-colon {
            width: 2%;
        }

        table.signatures {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        table.signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 6px;
        }

        .signature-space { height: 60px; }

        .signature-line {
            border-top: 1px solid #000;
            margin: 0 8px;
            padding-top: 3px;
        }

        .legend {
            margin-top: 16px;
            font-size: 8px;
        }

        .legend span {
            margin-right: 10px;
        }

        .legend .c-white { color: #555; }
        .legend .c-red { color: #c00000; }
        .legend .c-green { color: #1b7a1b; }
        .legend .c-blue { color: #0033a0; }
        .legend .c-yellow { color: #b8860b; }

        .footer {
            position: fixed;
            bottom: -11mm;
            left: 0;
            right: 0;
            font-family: Courier, monospace;
            font-size: 7px;
            color: #000;
        }

        .footer .page-number:before { content: counter(page); }
    </style>
</head>
<body>
    <div class="doc-title">PURCHASE ORDER</div>
    <div class="doc-subtitle">(SURAT PESANAN)</div>

    <table class="parties">
        <tr>
            <td class="party-left">
                <div>Kepada:</div>
                <div class="text-bold">{{ $vendor?->name }}</div>
                <div>{{ $vendor?->address }}</div>
                <div>{{ $vendor?->city }}</div>
                <div>Phone : {{ $vendor?->phone ?: '-' }}</div>
                <div>Fax : {{ $vendor?->fax ?: '-' }}</div>
                <div>Up : {{ $vendor?->contact_person ?: '-' }}</div>
            </td>
            <td class="party-right">
                <table class="meta">
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-colon">:</td>
                        <td>{{ strtoupper($purchaseOrder->created_at?->translatedFormat('d M y') ?? '') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">No. PO</td>
                        <td class="meta-colon">:</td>
                        <td class="mono">{{ $purchaseOrder->number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">PR/BPPB</td>
                        <td class="meta-colon">:</td>
                        <td class="mono">{{ $purchaseOrderNumbers ?: '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="delivery-meta">
        <tr>
            <td class="delivery-label">Syarat Pembayaran</td>
            <td class="delivery-colon">:</td>
            <td>{{ $purchaseOrder->terms ?: '-' }}</td>
        </tr>
        <tr>
            <td class="delivery-label">Tanggal Pengiriman</td>
            <td class="delivery-colon">:</td>
            <td>{{ $deliveryDate }}</td>
        </tr>
    </table>

    <div class="intro">
        Sesuai dengan penawaran harga yang telah diberikan, pesanan barang-barang tersebut dibawah ini
        agar dikirim kepada bagian penerimaan barang (Receiving Section) dengan mengikuti
        ketentuan-ketentuan yang tertera dibawah ini.
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 9%;">Jumlah</th>
                <th style="width: 9%;">Satuan</th>
                <th style="width: 40%;">Nama Barang</th>
                <th style="width: 18%;">Harga (IDR) Satuan</th>
                <th style="width: 18%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchaseOrder->purchaseOrderItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center mono">{{ $qty($item->qty) }}</td>
                    <td class="text-center">{{ $item->item?->unit }}</td>
                    <td>
                        <div class="item-name">{{ $item->item?->name }}</div>
                        @if (filled($item->description))
                            <div class="item-description">
                                @foreach (collect(explode("\n", $item->description))->filter() as $line)
                                    {{ trim($line) }}<br>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="text-right mono">{{ $idr($item->price) }}</td>
                    <td class="text-right mono">{{ $idr($item->getSubtotalAmount()) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">-</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="bottom">
        <tr>
            <td class="conditions-cell">
                <div class="condition-note">
                    PO ini bisa dibatalkan sewaktu-waktu oleh kedua belah pihak.
                </div>
                <div class="condition-note">
                    Apabila ada uang muka, maka PO ini belum mengikat dan dapat dibatalkan jika uang muka
                    belum dibayarkan. Jika uang muka tersebut telah dibayarkan, maka PO tidak dapat dibatalkan.
                </div>
            </td>
            <td class="summary-cell">
                <table class="summary">
                    <tr>
                        <td>Subtotal</td>
                        <td class="summary-value mono">{{ $idr($summary['subtotal']) }}</td>
                    </tr>
                    <tr>
                        <td>Diskon ({{ $discountPercentage + 0 }}%)</td>
                        <td class="summary-value mono">{{ $idr($summary['discount']) }}</td>
                    </tr>
                    <tr>
                        <td>Subtotal-2</td>
                        <td class="summary-value mono">{{ $idr($summary['subtotal_after_discount']) }}</td>
                    </tr>
                    @if ($taxPercentage > 0)
                        <tr>
                            <td>PPN ({{ $taxPercentage + 0 }}%)</td>
                            <td class="summary-value mono">{{ $idr($summary['tax_amount']) }}</td>
                        </tr>
                    @endif
                    @if ((float) $summary['rounding'] != 0)
                        <tr>
                            <td>Pembulatan</td>
                            <td class="summary-value mono">{{ $idr($summary['rounding']) }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>Grand Total</td>
                        <td class="summary-value mono">{{ $idr($summary['grand_total']) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="keterangan">
        <span class="text-bold">Keterangan:</span> {{ strtoupper($terbilang) }}.
    </div>

    <table class="delivery">
        <tr>
            <td class="delivery-label">Barang segera dikirim ke</td>
            <td class="delivery-colon">:</td>
            <td>
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
            <td class="delivery-label">Penerima Barang</td>
            <td class="delivery-colon">:</td>
            <td>{{ $receivingPerson }}</td>
        </tr>
        <tr>
            <td class="delivery-label">DO/Faktur/Kwitansi a.n.</td>
            <td class="delivery-colon">:</td>
            <td>
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

    <table class="signatures">
        <tr>
            <td>
                <div>Hormat Kami,</div>
                <div class="signature-space"></div>
                <div class="signature-line">
                    {{ $company?->contact_person ?: 'Staff Purchasing' }}
                    @if ($company?->contact_person_position)
                        <br>{{ $company->contact_person_position }}
                    @endif
                </div>
            </td>
            <td>
                <div>Menyetujui,</div>
                <div class="signature-space"></div>
                <div class="signature-line">Supplier</div>
            </td>
        </tr>
    </table>

    <div class="legend">
        <span class="c-white">White: Supplier</span>
        <span class="c-red">Red: Finance</span>
        <span class="c-green">Green: Purchasing</span>
        <span class="c-blue">Blue: Audit</span>
        <span class="c-yellow">Yellow: Logistik</span>
    </div>

    <div class="footer">
        Page <span class="page-number"></span> of {{ $pageCount ?? 1 }} |
        PO: {{ $purchaseOrder->number }} |
        Printed at: {{ $printedAt->translatedFormat('d M Y - H:i') }}
    </div>
</body>
</html>
