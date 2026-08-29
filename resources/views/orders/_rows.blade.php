@foreach ($orders as $order)
    @php
        $isDelivered = $order->delivery_status === 'Delivered';
        $isCancelled = $order->delivery_status === 'Cancelled';

        $rowClass = match(true) {
            $isDelivered => 'opacity-50 hover:opacity-100 bg-zinc-50/60',
            $isCancelled => 'opacity-50 hover:opacity-100 bg-rose-50/40 border-l-4 border-l-rose-400 sm:border-l-2',
            default      => 'hover:bg-zinc-50/80 bg-white sm:bg-transparent',
        };
    @endphp

    <tr class="block sm:table-row cursor-pointer transition-all p-3.5 mb-3 rounded-xl border border-zinc-200/80 sm:border-0 sm:p-0 sm:mb-0 sm:rounded-none shadow-2xs sm:shadow-none {{ $rowClass }}"
        onclick="window.location='{{ route('orders.show', $order) }}'">
        
        {{-- Order Number & Date --}}
        <td class="block sm:table-cell py-0.5 sm:py-2.5 text-zinc-500">
            <div class="flex items-center justify-between sm:block">
                <span class="font-mono font-medium text-zinc-600 sm:text-zinc-500">#{{ $order->order_number }}</span>
                <span class="text-[11px] text-zinc-400 sm:hidden">{{ $order->order_date->format('d M Y') }}</span>
            </div>
        </td>

        {{-- Date (Desktop only) --}}
        <td class="hidden sm:table-cell py-2.5 text-zinc-500">
            {{ $order->order_date->format('d M') }}
        </td>

        {{-- Customer Handle & Total Price (Mobile header row) --}}
        <td class="block sm:table-cell py-0.5 sm:py-2.5 font-semibold {{ $isCancelled ? 'line-through text-rose-900/70' : 'text-zinc-800' }}">
            <div class="flex items-center justify-between sm:block">
                <span>{{ $order->customer_handle }}</span>
                <span class="font-semibold text-zinc-900 sm:hidden">${{ number_format($order->total_price, 2) }}</span>
            </div>
        </td>

        {{-- Design & Collection --}}
        <td class="block sm:table-cell py-0.5 sm:py-2.5 text-zinc-600 {{ $isCancelled ? 'line-through text-zinc-400' : '' }}">
            <span class="font-medium sm:font-normal">{{ $order->design->name }}</span>
            <span class="text-zinc-400 sm:text-zinc-500"> — {{ $order->design->collection->name }}</span>
        </td>

        {{-- Size & Color --}}
        <td class="block sm:table-cell py-0.5 sm:py-2.5 text-zinc-500">
            <span class="inline-block sm:hidden text-zinc-400 mr-1">Specs:</span>{{ $order->size }} / {{ $order->color }}
        </td>

        {{-- Readiness Badge --}}
        <td class="inline-block sm:table-cell pt-2 sm:pt-0 py-1 sm:py-2.5 mr-1 sm:mr-0">
            <span class="badge {{ $order->getReadinessBadge()['class'] }}">{{ $order->getReadinessBadge()['label'] }}</span>
        </td>

        {{-- Delivery Status Badge --}}
        <td class="inline-block sm:table-cell py-1 sm:py-2.5 mr-1 sm:mr-0">
            <span class="badge {{ $order->getDeliveryBadgeClass() }}">{{ $order->delivery_status }}</span>
        </td>

        {{-- Payment Status Badge --}}
        <td class="inline-block sm:table-cell py-1 sm:py-2.5">
            <span class="badge {{ $order->getPaymentBadgeClass() }}">{{ $order->payment_status }}</span>
        </td>

        {{-- Total Price (Desktop only) --}}
        <td class="hidden sm:table-cell py-2.5 text-right font-semibold text-zinc-900">
            ${{ number_format($order->total_price, 2) }}
        </td>
    </tr>
@endforeach