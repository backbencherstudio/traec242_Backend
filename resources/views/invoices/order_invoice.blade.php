<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        .invoice {
            width: 100%;
            padding: 20px;
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .invoice-details {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ccc;
        }

        .total {
            margin-top: 20px;
            text-align: right;
        }

        h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        p {
            font-size: 14px;
        }

        .address {
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="invoice">
        <div class="invoice-header">
            <h2>Invoice</h2>
            <p>Date: {{ $date }}</p>
            <p>Order ID: {{ $order->id }}</p>
        </div>

        <div class="invoice-details">
            <h3>Customer Details</h3>
            <p>Name: {{ $order->first_name }} {{ $order->last_name }}</p>
            <p>Email: {{ $user->email }}</p>
            <p>Phone: {{ $order->phone }}</p>
            <p>Address: {{ $order->address }}</p>
        </div>

        <div class="invoice-details">
            <h3>Event Details</h3>
            <table>
                <tr>
                    <th>Event Name</th>
                    <td>{{ $order->event_name }}</td>
                </tr>
                <tr>
                    <th>Event Date</th>
                    <td>{{ $order->event_start_date }} to {{ $order->event_end_date }}</td>
                </tr>
                <tr>
                    <th>Guest Count</th>
                    <td>{{ $order->guest_count }}</td>
                </tr>
                <tr>
                    <th>Event Description</th>
                    <td>{{ $order->event_description }}</td>
                </tr>
            </table>
        </div>

        <div class="invoice-details">
            <h3>Service Details</h3>
            <table>
                <tr>
                    <th>Service Title</th>
                    <td>{{ $service->title }}</td>
                </tr>
                <tr>
                    <th>Service Price</th>
                    <td>${{ number_format($pricing->price, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="invoice-details">
            <h3>Payment Details</h3>
            <p>Transaction ID: {{ $transaction_id }}</p>
            <p>Payment Method: {{ ucfirst($payment_method) }}</p>
            <p>Payment Status: {{ ucfirst($payment_status) }}</p>
            <p>Total Amount: ${{ number_format($total_amount, 2) }}</p>
        </div>

        <div class="total">
            <h3>Total: ${{ number_format($total_amount, 2) }}</h3>
        </div>
    </div>

</body>

</html>
