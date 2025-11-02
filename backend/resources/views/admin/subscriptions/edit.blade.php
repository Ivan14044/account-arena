@extends('adminlte::page')

@section('title', 'Edit subscription #' . $subscription->id)

@section('content_header')
    <h1>Edit subscription #{{ $subscription->id }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Subscription data</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="{{ \App\Models\Subscription::STATUS_ACTIVE }}" {{ old('status', $subscription->status) == \App\Models\Subscription::STATUS_ACTIVE ? 'selected' : '' }}>Active</option>
                                <option value="{{ \App\Models\Subscription::STATUS_CANCELED }}" {{ old('status', $subscription->status) == \App\Models\Subscription::STATUS_CANCELED ? 'selected' : '' }}>Canceled</option>
                                <option value="{{ \App\Models\Subscription::STATUS_ENDED }}" {{ old('status', $subscription->status) == \App\Models\Subscription::STATUS_ENDED ? 'selected' : '' }}>Ended</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="service_id">Service</label>
                            <select name="service_id" id="service_id" class="form-control @error('service_id') is-invalid @enderror">
                                @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ old('service_id', $subscription->service_id) == $service->id ? 'selected' : '' }}>{{ $service->code }}</option>
                                @endforeach
                            </select>
                            @error('service_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(request()->back_url)
                            <input type="hidden" name="return_url" value="{{ request()->back_url }}">
                        @endif

                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payments data</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Action</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Created at</td>
                            <td>{{ \Carbon\Carbon::parse($subscription->created_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Next payment at</td>
                            <td>{{ \Carbon\Carbon::parse($subscription->next_payment_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                        @php
                            $last = $subscription->transactions->sortByDesc('created_at')->first();
                        @endphp
                        <tr>
                            <td>Last payment at</td>
                            <td>{{ $last?->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
