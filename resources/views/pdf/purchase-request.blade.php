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

    $statusLogs = $purchaseRequest->statusLogs->keyBy(fn($log) => $log->to_status?->value);
    $creatorName = $user?->name ?? '-';
    $checkedUser = $statusLogs->get(\App\Enums\PurchaseRequestStatus::CHECKED->value)?->user?->name;
    $approvedUser = $statusLogs->get(\App\Enums\PurchaseRequestStatus::APPROVED->value)?->user?->name;
    $reviewedUser = $statusLogs->get(\App\Enums\PurchaseRequestStatus::REVIEWED->value)?->user?->name;
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
            /* font-weight: bold; */
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

        .table-signature {
            width: 47rem;
        }

        .table-signature th {
            padding: 0 0.5rem;
        }

        .table-signature td {
            padding: 0 0.5rem;
        }

        .footer {
            position: fixed;
            bottom: 9.5px;
            left: 0;
            right: 0;
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
                    PURCHASE REQUEST
                </h1>
            </div>
            <br />

            <!-- FROM -->
            <div class="row">
                <p>
                    <strong>{{ $company?->name ?? 'PERUSAHAAN' }}</strong><br>
                    @if ($company?->address)
                        {{ $company->address }}<br>
                    @endif
                    @if ($company?->city)
                        {{ $company->city }}
                        @if ($company?->post_code) - {{ $company->post_code }} @endif
                    @endif
                    @if ($company?->phone)
                        | Tel: {{ $company->phone }}
                    @endif
                    @if ($company?->fax)
                        | Fax: {{ $company->fax }}
                    @endif
                </p>
            </div>
            <br />

            <!-- PURPOSE -->
            <div class="row">
                <table>
                    <tr>

                        <td style="padding-right: 0.5rem; min-width: 22rem; max-width: 22rem">
                            <!-- 123456789 123456789 123456789 123456789 1234 -->
                            <table>
                                <!-- <tr>
								<td style="min-width: 3.5rem; max-width: 3.5rem">1234567</td>
								<td style="width: 1rem">:</td>
								<td style="min-width: 17.5rem; max-width: 17.5rem">
									<strong>0123456789 123456789 123456789 1234</strong>
								</td>
							</tr> -->

                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">Nomor</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ $purchaseRequest->number }}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">Tanggal</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ $purchaseRequest->created_at?->translatedFormat('d F Y') ?? '-' }}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">Proyek</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ $warehouse?->name ?? '-' }}-{{ $division?->name ?? '-' }}-{{ $project?->name ?? '-' }}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="min-width: 3.5rem; max-width: 3.5rem">Alamat</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ collect([$warehouseAddress?->address, $warehouseAddress?->city])->filter()->join(', ') ?: '-' }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td style="min-width: 24rem; max-width: 24rem; padding-left: 0.5rem">
                            <!-- 789 123456789 123456789 123456789 123456789 1234 -->
                            <table>
                                <!-- <tr>
								<td style="min-width: 5.5rem; max-width: 5.5rem">789 1234567</td>
								<td style="width: 1rem">:</td>
								<td style="min-width: 17.5rem; max-width: 17.5rem">
									<strong>0123456789 123456789 123456789 1234</strong>
								</td>
							</tr> -->

                                <tr>
                                    <td style="min-width: 5.5rem; max-width: 5.5rem">Jenis</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{!! nl2br(e($purchaseRequest->description)) !!}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="min-width: 5.5rem; max-width: 5.5rem">Catatan</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{!! nl2br(e($purchaseRequest->notes)) !!}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="min-width: 5.5rem; max-width: 5.5rem">Memo</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{!! nl2br(e($purchaseRequest->memo)) !!}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="min-width: 5.5rem; max-width: 5.5rem">RAB</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ $purchaseRequest->boq }}</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="min-width: 5.5rem; max-width: 5.5rem">Dibuat Oleh</td>
                                    <td style="width: 1rem">:</td>
                                    <td style="min-width: 17.5rem; max-width: 17.5rem">
                                        <strong>{{ $creatorName }}</strong>
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
                <h1>
                    Daftar Item&nbsp;
                </h1>
                <br />

                <table class="table-item-border">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Item&nbsp;</th>
                            <th>Qty</th>
                            <th>Satuan</th>
                            <th>Keterangan&nbsp;</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($items as $index => $item)
                            <tr>
                                <td class="text-end" style="min-width: 1rem; max-width: 1rem;">
                                    {{ $index + 1 }}
                                </td>
                                <td style="min-width: 3rem; max-width: 3rem;">
                                    {{ $item->item?->code ?? '-' }}
                                </td>
                                <td style="min-width: 15rem; max-width: 15rem;">
                                    {{ $item->item?->name ?? '-' }}
                                </td>
                                <td class="text-end" style="min-width: 4.5rem; max-width: 4.5rem;">
                                    {{ $qty($item->qty) }}
                                </td>
                                <td style="min-width: 3rem; max-width: 3rem;">
                                    {{ $item->item?->unit ?? '-' }}
                                </td>
                                <td style="min-width: 14.5rem; max-width: 14.5rem;">
                                    {{ $item->description ? '' : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 8px;">(Tidak ada item barang)</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <br />
        </div>

        <div style="page-break-inside: avoid">
            <div class="row">
                <table class="table-signature">
                    <tr>
                        <td style="text-align: center">Pemesan,</td>
                        <td style="text-align: center">Dibuat Oleh,&nbsp;</td>
                        <td colspan="3" style="text-align: center">Diperiksa Oleh,</td>
                    </tr>

                    <tr>
                        <td style="height: 50.7pt;">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>

                    <tr>
                        <td style="min-width: 8rem; max-width: 8rem; text-align: center">
                            <hr>Pelaksana&nbsp;
                        </td>
                        <td style="min-width: 8.5rem; max-width: 8.5rem; text-align: center">
                            <hr>Kepala Logistik
                        </td>
                        <td style="min-width: 8rem; max-width: 8rem; text-align: center">
                            <hr>Site Manager
                        </td>
                        <td style="min-width: 9.5rem; max-width: 9.5rem; text-align: center">
                            <hr>Kordinator Proyek
                        </td>
                        <td style="min-width: 8rem; max-width: 8rem; text-align: center">
                            <hr>QS
                        </td>
                    </tr>
                </table>
                <br />

                <table class="table-signature">
                    <tr>
                        <td style="text-align: center">Disetujui,&nbsp;</td>
                        <td>&nbsp;</td>
                        <td colspan="2" style="text-align: center">Direview Oleh,&nbsp;</td>
                        <td>&nbsp;</td>
                        <td style="text-align: center">Bagian Pembelian,&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="height: 50.5pt;">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="min-width: 8.5rem; max-width: 8.5rem; text-align: center">
                            <hr>Project Manager
                        </td>
                        <td style="min-width: 2rem; max-width: 2rem; text-align: center">&nbsp;</td>
                        <td style="min-width: 8rem; max-width: 8rem; text-align: center">
                            <hr>Staf Audit
                        </td>
                        <td style="min-width: 10.5rem; max-width: 10.5rem; text-align: center">
                            <hr>Kepala Audit Proyek
                        </td>
                        <td style="min-width: 2rem; max-width: 2rem; text-align: center">&nbsp;</td>
                        <td style="min-width: 10rem; max-width: 10rem; text-align: center">
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
</body>

</html>