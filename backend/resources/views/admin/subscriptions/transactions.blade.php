@extends('adminlte::page')

@section('title', 'Transactions for #' . $subscription->id . ' subscription')

@section('content_header')
    <h1>Transactions for #{{ $subscription->id }} subscription <small>{{ $user->email }}</small></h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transactions list</h3>
                    <a href="{{ request()->back_url ? request()->back_url : route('admin.subscriptions.index') }}" class="btn btn-secondary float-right">Back</a>
                </div>
                <div class="card-body">
                    <table id="transactions-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Created at</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->id }}</td>
                                <td>{{ $transaction->amount }} {{ strtoupper($transaction->currency) }}</td>
                                <td>
                                    @php($methodLabels = [
                                        'credit_card' => 'Credit Card',
                                        'crypto' => 'Crypto',
                                        'free' => 'Free',
                                    ])
                                    {{ $methodLabels[$transaction->payment_method] ?? ucfirst(str_replace('_',' ', $transaction->payment_method)) }}
                                </td>
                                <td data-order="{{ strtotime($subscription->created_at) }}">
                                    {{ \Carbon\Carbon::parse($subscription->created_at)->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#transactions-table').DataTable({
                "order": [[0, "desc"]]
            });
        });
    </script>
@endsection
