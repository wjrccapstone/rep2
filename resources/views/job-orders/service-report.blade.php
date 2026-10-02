<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Report {{ $jobOrder->code }} · WJRC Computer Services</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wjrclogo-trimmed.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="no-print sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
        <a href="{{ route('job-orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Back to Job Orders
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.017-1.837-2.185a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.017 1.837-2.185a48.055 48.055 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
            Print
        </button>
    </div>

    @php
        $blank = '&nbsp;';
        // "Waiting for Parts" has no equivalent JobOrder::STATUSES value, so it's left unbound (always unchecked).
        $statusItems = [
            'pending' => 'For Approval',
            'for_pickup' => 'For Pull-Out',
            'in_progress' => 'For Testing',
            null => 'Waiting for Parts',
            'completed' => 'For Release',
        ];
    @endphp

    <div class="mx-auto max-w-4xl bg-white p-6 print:p-0 text-[12px] leading-tight" id="report">
        {{-- Letterhead --}}
        <div class="flex items-start justify-between pb-3">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/wjrclogo-trimmed.png') }}" alt="WJRC Computer Services" class="h-20 w-auto">
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Service Report</h1>
        </div>

        <div class="border-2 border-black">
            {{-- Contact Information --}}
            <table class="w-full border-collapse">
                <tr><th colspan="4" class="border border-black bg-[#4a6f90] py-1 text-center text-[12px] font-bold uppercase tracking-widest text-white">Contact Information:</th></tr>
                <tr>
                    <td class="w-[15%] border border-black px-2 py-1 font-semibold">Name:</td>
                    <td class="w-[35%] border border-black px-2 py-1">{{ $jobOrder->customer->name ?? '' }}</td>
                    <td class="w-[15%] border border-black px-2 py-1 font-semibold">Job Order No.</td>
                    <td class="w-[35%] border border-black px-2 py-1">{{ $jobOrder->code }}</td>
                </tr>
                <tr>
                    <td class="border border-black px-2 py-1 font-semibold">Business Name:</td>
                    <td class="border border-black px-2 py-1">{{ $jobOrder->customer->business_name ?? '' }}</td>
                    <td class="border border-black px-2 py-1 font-semibold">Date Received:</td>
                    <td class="border border-black px-2 py-1">{{ $jobOrder->planned_start_date?->format('M d, Y') ?? '' }}</td>
                </tr>
                <tr>
                    <td class="border border-black px-2 py-1 font-semibold">Address:</td>
                    <td class="border border-black px-2 py-1">{{ $jobOrder->customer->address ?? '' }}</td>
                    <td class="border border-black px-2 py-1 font-semibold">Contact No.</td>
                    <td class="border border-black px-2 py-1">{{ $jobOrder->customer->contact_no ?? '' }}</td>
                </tr>
                <tr class="h-6">
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                </tr>
            </table>

            {{-- Unit Details --}}
            <table class="w-full border-collapse">
                <tr><th colspan="4" class="border border-black bg-[#4a6f90] py-1 text-center text-[12px] font-bold uppercase tracking-widest text-white">Unit Details</th></tr>
                <tr>
                    <td class="w-[15%] border border-black px-2 py-1 font-semibold">Under Warranty:</td>
                    <td class="w-[35%] border border-black px-2 py-1">{!! $blank !!}</td>
                    <td class="w-[15%] border border-black px-2 py-1 font-semibold">Accessories:</td>
                    <td class="w-[35%] border border-black px-2 py-1">{!! $blank !!}</td>
                </tr>
                <tr>
                    <td class="border border-black px-2 py-1 font-semibold">Brand/Model:</td>
                    <td class="border border-black px-2 py-1">{{ $jobOrder->device?->label() }}</td>
                    <td class="border border-black px-2 py-1 font-semibold">Hard Drive S/N:</td>
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                </tr>
                <tr>
                    <td class="border border-black px-2 py-1 font-semibold">Serial No:</td>
                    <td class="border border-black px-2 py-1">{{ $jobOrder->serial_no ?? '' }}</td>
                    <td class="border border-black px-2 py-1 font-semibold">Battery S/N:</td>
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                </tr>
                <tr>
                    <td class="border border-black px-2 py-1 font-semibold">Memory S/N:</td>
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                    <td class="border border-black px-2 py-1 font-semibold">Charger S/N:</td>
                    <td class="border border-black px-2 py-1">{!! $blank !!}</td>
                </tr>
            </table>

            {{-- Client Reported Issue / Work Summary --}}
            <div class="grid grid-cols-5">
                <div class="col-span-2 flex flex-col border-l border-black">
                    <div class="border border-black bg-[#4a6f90] py-1 text-center text-[12px] font-bold uppercase tracking-widest text-white">Client Reported Issue</div>
                    <table class="w-full flex-1 table-fixed border-collapse">
                        <colgroup>
                            <col class="w-[42%]">
                            <col class="w-[29%]">
                            <col class="w-[29%]">
                        </colgroup>
                        <tr class="h-[55px]">
                            <td colspan="3" class="border border-black px-1 py-1 align-top text-[12px]">{{ $jobOrder->issue ?: '' }}</td>
                        </tr>
                        <tr class="text-[12px] font-semibold uppercase">
                            <td class="border border-black bg-[#4a6f90] px-1 py-0.5">&nbsp;</td>
                            <td class="border border-black bg-[#4a6f90] px-1 py-0.5 text-center text-white">Date</td>
                            <td class="border border-black bg-[#4a6f90] px-1 py-0.5 text-center text-white">Remarks</td>
                        </tr>
                        @foreach ($statusItems as $key => $label)
                            <tr>
                                <td class="border border-black px-1 py-1">
                                    <label class="flex items-center gap-1.5">
                                        <input type="checkbox" disabled @checked($jobOrder->status === $key) class="h-3 w-3 shrink-0 rounded-none border-black align-middle">
                                        <span class="uppercase text-[12px]">{{ $label }}</span>
                                    </label>
                                </td>
                                <td class="border border-black px-1 py-1">&nbsp;</td>
                                <td class="border border-black px-1 py-1">&nbsp;</td>
                            </tr>
                        @endforeach
                    </table>
                    <p class="mt-auto border-x border-b border-black px-1 py-1 text-[12px] italic leading-snug">
                        NOTE: unit/s released are in good condition unless otherwise stated in this report.
                    </p>
                </div>
                <div class="col-span-3 flex flex-col border-l border-r border-b border-black">
                    <div class="border-y border-black bg-[#4a6f90] py-1 text-center text-[12px] font-bold uppercase tracking-widest text-white">Work Summary &amp; Recommendation</div>
                    <div class="flex min-h-[110px] flex-1 flex-col px-2 py-1">
                        <p>{{ $jobOrder->work_summary ?: '' }}</p>
                        <div class="mt-[190px] mx-[20%] border-b-2 border-black"></div>
                    </div>
                </div>
            </div>

            {{-- Parts / Pricing --}}
            <table class="w-full border-collapse">
                <tr class="text-[12px] font-bold uppercase tracking-wide">
                    <th class="w-[30%] border border-black bg-[#4a6f90] py-1 text-white">Description</th>
                    <th class="w-[15%] border border-black bg-[#4a6f90] py-1 text-white">Parts No.</th>
                    <th class="w-[10%] border border-black bg-[#4a6f90] py-1 text-white">Qty</th>
                    <th class="w-[20%] border border-black bg-[#4a6f90] py-1 text-white">Unit Price</th>
                    <th class="w-[25%] border border-black bg-[#4a6f90] py-1 text-white">Price</th>
                </tr>
                <tr>
                    <td class="border border-black px-2 py-1">{{ $jobOrder->service->name }} — {{ $jobOrder->device?->label() }}</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1 text-center">1</td>
                    <td class="border border-black px-2 py-1 text-right">{{ number_format($jobOrder->cost, 2) }}</td>
                    <td class="border border-black px-2 py-1 text-right">{{ number_format($jobOrder->cost, 2) }}</td>
                </tr>
                <tr class="h-6">
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                </tr>
                <tr class="h-6">
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                    <td class="border border-black px-2 py-1">&nbsp;</td>
                </tr>
            </table>

            {{-- Payment Details --}}
            <div class="grid grid-cols-4">
                <div class="col-span-3 flex flex-col">
                    <div class="flex items-center justify-between border border-black bg-[#4a6f90] px-2 py-1 text-[12px] font-bold uppercase tracking-widest text-white">
                        <span>Payment Details</span>
                    </div>
                    <table class="w-full flex-1 border-collapse text-[12px]">
                        <tr class="font-semibold uppercase">
                            <td class="w-[15%] border border-black px-1 py-1 text-center">Date</td>
                            <td class="w-[25%] border border-black px-1 py-1 text-center">
                                Ref. No.<br>
                                <span class="text-[12px] font-normal normal-case">
                                    SI&nbsp;<input type="checkbox" disabled class="h-2.5 w-2.5 rounded-none border-black align-middle">
                                    &nbsp;CR&nbsp;<input type="checkbox" disabled class="h-2.5 w-2.5 rounded-none border-black align-middle">
                                    &nbsp;CL&nbsp;<input type="checkbox" disabled class="h-2.5 w-2.5 rounded-none border-black align-middle">
                                </span>
                            </td>
                            <td class="w-[25%] border border-black px-1 py-1 text-center">Amount</td>
                            <td class="w-[35%] border border-black px-1 py-1 text-center">Mode of Payment</td>
                        </tr>
                        <tr>
                            <td class="border border-black px-1 py-1 text-center">No.</td>
                            <td class="border border-black px-1 py-1">&nbsp;</td>
                            <td class="border border-black px-1 py-1">Php&nbsp;</td>
                            <td class="border border-black px-1 py-1 text-[12px] leading-snug" rowspan="2">
                                <label class="flex items-center gap-1"><input type="checkbox" disabled class="h-2.5 w-2.5 rounded-none border-black">Cash</label>
                                <label class="flex items-center gap-1"><input type="checkbox" disabled class="h-2.5 w-2.5 rounded-none border-black">Bank Trans.</label>
                                <label class="flex items-center gap-1"><input type="checkbox" disabled class="h-2.5 w-2.5 rounded-none border-black">Cheque</label>
                                <label class="flex items-center gap-1"><input type="checkbox" disabled class="h-2.5 w-2.5 rounded-none border-black">Charge</label>
                            </td>
                        </tr>
                        <tr>
                            <td class="border border-black px-1 py-1 text-center">No.</td>
                            <td class="border border-black px-1 py-1">&nbsp;</td>
                            <td class="border border-black px-1 py-1">Php&nbsp;</td>
                        </tr>
                    </table>
                    <p class="border-x border-black px-2 py-1.5 text-center text-[12px] italic">
                        "I hereby acknowledge the receipt of the units as stated in this service report"
                    </p>
                    <div class="flex flex-1 flex-col items-center justify-end border-x border-b border-black px-2 pb-1">
                        <div class="mb-0.5 h-6 w-2/3 border-b border-black"></div>
                        <p class="text-[12px] uppercase tracking-wide text-slate-600">Signature over Printed Name</p>
                    </div>
                </div>

                <div class="col-span-1 flex flex-col border-r border-t border-b border-black">
                    <div class="flex items-center justify-center border-b border-black bg-white px-2 py-1 text-[12px] font-semibold uppercase">
                        Amount Due:&nbsp;{{ number_format($jobOrder->cost, 2) }}
                    </div>
                    <div class="flex flex-1 flex-col justify-between px-2 py-2 text-[12px]">
                        <div>
                            <p class="font-semibold">Approved for Release By:</p>
                            <div class="mt-3 border-b border-black">&nbsp;</div>
                        </div>
                        <div>
                            <p class="font-semibold">Released by:</p>
                            <div class="mt-3 border-b border-black">&nbsp;</div>
                        </div>
                        <div>
                            <p class="font-semibold">Signature:</p>
                            <div class="mt-3 border-b border-black">&nbsp;</div>
                        </div>
                        <div>
                            <p class="font-semibold">Date Claimed:</p>
                            <div class="mt-3 border-b border-black">&nbsp;</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            /* A zero @page margin stops the browser from reserving space for its own
               date/title/URL/page-number header & footer, so none of that gets printed.
               Our own spacing is applied to #report below instead. */
            @page {
                size: auto;
                margin: 0;
            }
            html, body {
                height: 100%;
                background: white;
            }
            #report {
                max-width: 100%;
                min-height: 100vh;
                padding: 10mm 8mm !important;
                display: flex;
                flex-direction: column;
                box-sizing: border-box;
            }
            /* Stretch the form to fill the whole printed page (whatever paper size was
               chosen) instead of leaving blank space below a short form. */
            #report > .border-2 {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            #report > .border-2 > div:last-child {
                flex: 1;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</body>
</html>
