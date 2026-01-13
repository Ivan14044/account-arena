@foreach($notifications as $key => $notification)
    @php
        $itemClass = $notification['read']
            ? 'dropdown-item'
            : 'dropdown-item bg-light-primary fw-bold';
    @endphp

    <a href="{{ $notification['url'] }}" class="{{ $itemClass }}">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div><i class="{{ $notification['icon'] }} mr-2"></i> {{ $notification['text'] }}</div>
            <span class="text-muted text-xs">{{ $notification['time'] }}</span>
        </div>
    </a>

    @if($key < count($notifications) - 1)
        <div class="dropdown-divider"></div>
    @endif
@endforeach
