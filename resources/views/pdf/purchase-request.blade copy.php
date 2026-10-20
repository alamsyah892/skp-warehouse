@php
    /** @var \App\Models\PurchaseRequest $purchaseRequest */
    /** @var \Illuminate\Support\Carbon $printedAt */
    /** @var \App\Models\User|null $printedBy */
    /** @var int|null $pageCount */

    $company = $purchaseRequest->company;
    $warehouse = $purchaseRequest->warehouse;
    $warehouseAddress = $purchaseRequest->warehouseAddress;
    $division = $purchaseRequest->division;
    $project = $purchaseRequest->project;
    $user = $purchaseRequest->user;
    $items = $purchaseRequest->purchaseRequestItems;

    $qty = function (float|int|string|null $value): string {
        $formatted = number_format((float) $value, 2, '.', ',');
        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    };

    $totalQty = $items->sum('qty');

    $statusLogs = $purchaseRequest->statusLogs->keyBy(fn ($log) => $log->to_status?->value);
    $creatorName = $user?->name ?? '-';
    $checkedUser = $statusLogs->get(\App\Enums\PurchaseRequestStatus::CHECKED->value)?->user?->name;
    $approvedUser = $statusLogs->get(\App\Enums\PurchaseRequestStatus::APPROVED->value)?->user?->name;
    $reviewedUser = $statusLogs->get(\App\Enums\PurchaseRequestStatus::REVIEWED->value)?->user?->name;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Request - {{ $purchaseRequest->number }}</title>
    <style type="text/css">
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 14mm 12mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9pt;
            line-height: 1.25;
            color: #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        th, td {
            vertical-align: top;
            color: #000000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* Document Header */
        .header-table {
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .company-detail {
            font-size: 8pt;
            line-height: 1.2;
        }

        .doc-title-box {
            text-align: right;
            vertical-align: top;
        }

        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            font-size: 9pt;
            font-weight: bold;
        }

        .divider-solid {
            border-top: 1px solid #000000;
            margin: 4px 0 6px 0;
        }

        .divider-double {
            border-top: 3px double #000000;
            margin: 4px 0 6px 0;
        }

        /* Metadata Information */
        .meta-table {
            margin-bottom: 8px;
            font-size: 8.5pt;
        }

        .meta-table td {
            padding: 1px 3px;
        }

        .meta-label {
            width: 18%;
            font-weight: bold;
        }

        .meta-sep {
            width: 2%;
        }

        .meta-value {
            width: 30%;
        }

        /* Items Table - Optimized for Dot Matrix */
        .table-items {
            margin-top: 4px;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }

        .table-items thead {
            display: table-header-group;
        }

        .table-items tfoot {
            display: table-footer-group;
        }

        .table-items tr {
            page-break-inside: avoid;
        }

        .table-items th {
            border: 1px solid #000000;
            padding: 4px 4px;
            font-weight: bold;
            text-align: center;
            background-color: #ffffff;
        }

        .table-items td {
            border: 1px solid #000000;
            padding: 3px 4px;
            background-color: #ffffff;
        }

        .item-desc {
            font-size: 8pt;
            padding-left: 8px;
            font-style: italic;
        }

        /* Remarks Section */
        .remarks-table {
            margin-top: 6px;
            margin-bottom: 10px;
            font-size: 8.5pt;
            border: 1px dashed #000000;
            padding: 4px;
        }

        .remarks-table td {
            padding: 2px 4px;
        }

        /* Signature Box */
        .signature-table {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signature-table th,
        .signature-table td {
            border: 1px solid #000000;
            text-align: center;
            font-size: 8pt;
            padding: 3px;
            width: 25%;
        }

        .signature-table th {
            font-weight: bold;
        }

        .signature-space {
            height: 52px;
        }

        /* Footer */
        .footer-page {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            border-top: 1px solid #000000;
            padding-top: 2px;
        }

        .footer-page .page-number:before {
            content: counter(page);
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">{{ $company?->name ?? 'PERUSAHAAN' }}</div>
                <div class="company-detail">
                    @if ($company?->address)
                        {{ $company->address }}<br>
                    @endif
                    @if ($company?->city)
                        {{ $company->city }}
                        @if ($company?->post_code) - {{ $company->post_code }} @endif
                    @endif
                    @if ($company?->phone)
                        | Telp: {{ $company->phone }}
                    @endif
                    @if ($company?->fax)
                        | Fax: {{ $company->fax }}
                    @endif
                </div>
            </td>
            <td class="doc-title-box" style="width: 45%;">
                <div class="doc-title">PURCHASE REQUEST</div>
                <div class="doc-subtitle">SURAT PERMINTAAN PEMBELIAN</div>
                <div style="font-size: 8.5pt; margin-top: 2px;">
                    <strong>STATUS: {{ strtoupper($purchaseRequest->status?->label() ?? '-') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider-double"></div>

    <!-- DOCUMENT METADATA -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">NO. PR</td>
            <td class="meta-sep">:</td>
            <td class="meta-value text-bold">{{ $purchaseRequest->number }}</td>

            <td class="meta-label">DIVISI</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $division?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">TANGGAL PR</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $purchaseRequest->created_at?->translatedFormat('d F Y') ?? '-' }}</td>

            <td class="meta-label">PROYEK</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $project?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">PEMOHON</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $creatorName }}</td>

            <td class="meta-label">GUDANG</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $warehouse?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">ALAMAT KIRIM</td>
            <td class="meta-sep">:</td>
            <td class="meta-value" colspan="4">
                {{ collect([$warehouseAddress?->address, $warehouseAddress?->city])->filter()->join(', ') ?: '-' }}
            </td>
        </tr>
    </table>

    <!-- ITEMS TABLE -->
    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 17%;">KODE BARANG</th>
                <th style="width: 46%;">NAMA BARANG / SPESIFIKASI</th>
                <th style="width: 12%;">QTY</th>
                <th style="width: 8%;">SATUAN</th>
                <th style="width: 12%;">CATATAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->item?->code ?? '-' }}</td>
                    <td>
                        <span class="text-bold">{{ $item->item?->name ?? '-' }}</span>
                        @if (filled($item->description))
                            <div class="item-desc">
                                @foreach (collect(explode("\n", $item->description))->filter() as $line)
                                    - {{ trim($line) }}<br>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="text-right text-bold">{{ $qty($item->qty) }}</td>
                    <td class="text-center">{{ $item->item?->unit ?? '-' }}</td>
                    <td>{{ $item->description ? '' : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 8px;">(Tidak ada item barang)</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">TOTAL JUMLAH KUANTITAS:</th>
                <th class="text-right">{{ $qty($totalQty) }}</th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>

    <!-- REMARKS / NOTES (if any) -->
    @if (filled($purchaseRequest->description) || filled($purchaseRequest->memo) || filled($purchaseRequest->boq) || filled($purchaseRequest->notes))
        <table class="remarks-table">
            @if (filled($purchaseRequest->description))
                <tr>
                    <td style="width: 16%; font-weight: bold;">Keperluan:</td>
                    <td>{!! nl2br(e($purchaseRequest->description)) !!}</td>
                </tr>
            @endif
            @if (filled($purchaseRequest->memo))
                <tr>
                    <td style="width: 16%; font-weight: bold;">Memo:</td>
                    <td>{!! nl2br(e($purchaseRequest->memo)) !!}</td>
                </tr>
            @endif
            @if (filled($purchaseRequest->boq))
                <tr>
                    <td style="width: 16%; font-weight: bold;">No. BOQ:</td>
                    <td>{{ $purchaseRequest->boq }}</td>
                </tr>
            @endif
            @if (filled($purchaseRequest->notes))
                <tr>
                    <td style="width: 16%; font-weight: bold;">Catatan:</td>
                    <td>{!! nl2br(e($purchaseRequest->notes)) !!}</td>
                </tr>
            @endif
        </table>
    @endif

    <!-- SIGNATURE BOX -->
    <table class="signature-table">
        <thead>
            <tr>
                <th>Pemohon</th>
                <th>Diperiksa</th>
                <th>Disetujui</th>
                <th>Diterima (Purchasing)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="signature-space">
                    <br><br><br>
                </td>
                <td class="signature-space">
                    <br><br><br>
                </td>
                <td class="signature-space">
                    <br><br><br>
                </td>
                <td class="signature-space">
                    <br><br><br>
                </td>
            </tr>
            <tr>
                <td>
                    <u><strong>{{ $creatorName }}</strong></u><br>
                    Tgl: {{ $purchaseRequest->created_at?->format('d/m/Y') ?? '..../..../........' }}
                </td>
                <td>
                    <u><strong>{{ $checkedUser ?: '( .................... )' }}</strong></u><br>
                    Tgl: ..../..../........
                </td>
                <td>
                    <u><strong>{{ $approvedUser ?: '( .................... )' }}</strong></u><br>
                    Tgl: ..../..../........
                </td>
                <td>
                    <u><strong>{{ $reviewedUser ?: '( .................... )' }}</strong></u><br>
                    Tgl: ..../..../........
                </td>
            </tr>
        </tbody>
    </table>

    <!-- FOOTER PAGE -->
    <div class="footer-page">
        <table style="width: 100%;">
            <tr>
                <td style="width: 70%; text-align: left;">
                    Dicetak: {{ $printedAt->translatedFormat('d/m/Y H:i') }} | PR No: {{ $purchaseRequest->number }}
                    @if ($printedBy)
                        | Oleh: {{ $printedBy->name }}
                    @endif
                </td>
                <td style="width: 30%; text-align: right;">
                    Halaman <span class="page-number"></span> / {{ $pageCount ?? 1 }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
