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
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Konfirmasi Order</title>
    <style>
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

        .doc-date { margin-bottom: 10px; }

        .doc-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: center;
            margin: 14px 0 4px;
        }

        .doc-title-rule {
            border-bottom: 1px solid #000;
            width: 34%;
            margin: 0 auto 12px;
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

        table.summary {
            width: 45%;
            margin-left: 55%;
            margin-top: -1px;
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

        .section-title {
            font-weight: bold;
            margin: 14px 0 6px;
        }

        table.conditions {
            width: 100%;
            border-collapse: collapse;
        }

        table.conditions td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }

        table.conditions td.condition-label {
            width: 28%;
        }

        .terbilang {
            font-style: italic;
        }

        .closing { margin-top: 16px; }

        table.signatures {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        table.signatures td {
            width: 33.33%;
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

        .ps { margin-top: 14px; }

        .document-numbers {
            margin-top: 12px;
            text-align: right;
        }

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
    <div class="doc-date">
        {{ $company?->city ? $company->city . ', ' : '' }}{{ $purchaseOrder->created_at?->translatedFormat('d M Y') }}
    </div>

    <div>Kepada:</div>
    <div class="text-bold">{{ $vendor?->name }}</div>
    <div>{{ $vendor?->address }}</div>
    <div>{{ $vendor?->city }}</div>
    @php
        $vendorContacts = collect([
            $vendor?->phone ? 'Telp: ' . $vendor->phone : null,
            $vendor?->fax ? 'Fax: ' . $vendor->fax : null,
        ])->filter()->implode(', ');
    @endphp
    @if ($vendorContacts)
        <div>{{ $vendorContacts }}</div>
    @endif
    @if ($vendor?->contact_person)
        <div>UP: {{ $vendor->contact_person }}</div>
    @endif

    <div class="doc-title">KONFIRMASI ORDER</div>
    <div class="doc-title-rule"></div>

    <div class="intro">
        Dengan hormat,<br>
        Bersama dengan ini kami memesan barang-barang sebagai berikut:
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 45%;">Item</th>
                <th style="width: 9%;">Qty</th>
                <th style="width: 10%;">Satuan</th>
                <th style="width: 15%;">Harga (IDR) Satuan</th>
                <th style="width: 15%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchaseOrder->purchaseOrderItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item->item?->name }}</div>
                        @if (filled($item->description))
                            <div class="item-description">
                                @foreach (collect(explode("\n", $item->description))->filter() as $line)
                                    &bull; {{ trim($line) }}<br>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="text-center mono">{{ $qty($item->qty) }}</td>
                    <td class="text-center">{{ $item->item?->unit }}</td>
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
            <td>Total</td>
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

    <div class="section-title">Kondisi Pembelian:</div>

    <table class="conditions">
        <tr>
            <td class="condition-label">Harga Total</td>
            <td class="mono">IDR {{ $idr($summary['grand_total']) }}</td>
        </tr>
        <tr>
            <td class="condition-label"></td>
            <td class="terbilang">( {{ $terbilang }} )</td>
        </tr>
        <tr>
            <td class="condition-label">Syarat Pembayaran</td>
            <td>{{ $purchaseOrder->terms ?: '-' }}</td>
        </tr>
        <tr>
            <td class="condition-label">Tanggal Pengiriman</td>
            <td>{{ $purchaseOrder->delivery_notes ?: ($purchaseOrder->delivery_date?->translatedFormat('d M Y') ?: '-') }}</td>
        </tr>
        <tr>
            <td class="condition-label">Pengiriman ke</td>
            <td>
                @if ($project?->name)
                    <div class="text-bold">{{ $project->name }}</div>
                @endif
                {{ collect([$warehouseAddress?->address, $warehouseAddress?->city])->filter()->implode(' - ') ?: '-' }}
            </td>
        </tr>
        <tr>
            <td class="condition-label">Faktur dan Kwitansi a.n.</td>
            <td>
                <div class="text-bold">{{ $company?->name }}</div>
                @if ($company?->address)
                    <div>{{ $company->address }}</div>
                @endif
                @if ($company?->tax_number)
                    <div>NPWP/NPPKP : {{ $company->tax_number }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="condition-label">No. PO</td>
            <td class="mono">{{ $purchaseOrder->number }}</td>
        </tr>
    </table>

    <div class="closing">Atas perhatiannya kami ucapkan terimakasih.</div>

    <table class="signatures">
        <tr>
            <td>
                <div>Hormat Kami,</div>
                <div class="signature-space"></div>
                <div class="signature-line">Staff Purchasing</div>
            </td>
            <td>
                <div>Menyetujui,</div>
                <div class="signature-space"></div>
                <div class="signature-line">Supplier</div>
            </td>
            <td>
                <div>Mengetahui,</div>
                <div class="signature-space"></div>
                <div class="signature-line">Koordinator Purchasing</div>
            </td>
        </tr>
    </table>

    @if ($company?->fax)
        <div class="ps">PS: Mohon segera di-fax balik ke nomor {{ $company->fax }} setelah dicap dan ditandatangani.</div>
    @endif

    <div class="document-numbers">
        <div>BPPB No. {{ $purchaseOrder->purchaseRequests->pluck('number')->implode(', ') ?: '-' }}</div>
        <div>PO No. {{ $purchaseOrder->number }}</div>
    </div>

    <div class="footer">
        Page <span class="page-number"></span> of {{ $pageCount ?? 1 }} |
        PO: {{ $purchaseOrder->number }} |
        Printed at: {{ $printedAt->translatedFormat('d M Y - H:i') }}
    </div>
</body>
</html>
