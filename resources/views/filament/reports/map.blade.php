<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <h3 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">Location</h3>

    @if ($address)
        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ $address }}</p>
    @endif

    @if ($lat && $lng)
        @php
            $query = $lat.','.$lng;
            $src = filled($mapsKey)
                ? 'https://www.google.com/maps/embed/v1/place?key='.urlencode($mapsKey).'&q='.urlencode($query)
                : 'https://maps.google.com/maps?q='.urlencode($query).'&z=16&output=embed';
        @endphp

        <iframe
            title="Report location"
            class="h-80 w-full rounded-lg border-0"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            src="{{ $src }}"
        ></iframe>
        <p class="mt-2 text-xs text-gray-500">
            {{ $lat }}, {{ $lng }}
        </p>
    @endif
</div>
