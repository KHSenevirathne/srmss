<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SRMSS Operational Report</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            color: #1f2937;
            font-size: 12px;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 2px;
        }

        h2 {
            font-size: 14px;
            margin: 18px 0 6px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        .muted {
            color: #6b7280;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th,
        td {
            text-align: left;
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f9fafb;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
        }

        .cards td {
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .cards .num {
            font-size: 18px;
            font-weight: bold;
        }

        .cards .lbl {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <h1>SRMSS : Operational Report</h1>
    <div class="muted">Range: {{ $from }} to {{ $to }} · Generated {{ $generatedAt }}</div>

    <h2>Trip completion</h2>
    <table class="cards">
        <tr>
            <td>
                <div class="num">{{ $tripCompletion['total'] }}</div>
                <div class="lbl">Total</div>
            </td>
            <td>
                <div class="num">{{ $tripCompletion['completed'] }}</div>
                <div class="lbl">Completed</div>
            </td>
            <td>
                <div class="num">{{ $tripCompletion['on_time'] }}</div>
                <div class="lbl">On time</div>
            </td>
            <td>
                <div class="num">{{ $tripCompletion['delayed'] }}</div>
                <div class="lbl">Delayed</div>
            </td>
            <td>
                <div class="num">{{ $tripCompletion['scheduled'] }}</div>
                <div class="lbl">Scheduled</div>
            </td>
            <td>
                <div class="num">{{ $tripCompletion['completion_rate'] }}%</div>
                <div class="lbl">Completion</div>
            </td>
        </tr>
    </table>

    <h2>Cost summary</h2>
    <table class="cards">
        <tr>
            <td>
                <div class="num">{{ number_format($costSummary['fuel'], 2) }}</div>
                <div class="lbl">Fuel</div>
            </td>
            <td>
                <div class="num">{{ number_format($costSummary['maintenance'], 2) }}</div>
                <div class="lbl">Maintenance</div>
            </td>
            <td>
                <div class="num">{{ number_format($costSummary['total'], 2) }}</div>
                <div class="lbl">Total</div>
            </td>
        </tr>
    </table>

    <h2>Route performance</h2>
    <table>
        <thead>
            <tr>
                <th>Route</th>
                <th>Trips</th>
                <th>Completed</th>
                <th>Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($routePerformance as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['trips'] }}</td>
                    <td>{{ $row['completed'] }}</td>
                    <td>{{ $row['rate'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No trips in this range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Vehicle utilisation</h2>
    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Trips</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vehicleUtilization as $row)
                <tr>
                    <td>{{ $row['vehicle'] }}</td>
                    <td>{{ $row['trips'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No trips in this range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Driver utilisation</h2>
    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Trips</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($driverUtilization as $row)
                <tr>
                    <td>{{ $row['driver'] }}</td>
                    <td>{{ $row['trips'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No trips in this range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Fuel consumption trend</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Litres</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($fuelTrend as $row)
                <tr>
                    <td>{{ $row['period'] }}</td>
                    <td>{{ number_format($row['liters'], 2) }}</td>
                    <td>{{ number_format($row['cost'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No fuel logs in this range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Maintenance summary</h2>
    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Service</th>
                <th>Repair</th>
                <th>Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($maintenanceSummary as $row)
                <tr>
                    <td>{{ $row['vehicle'] }}</td>
                    <td>{{ $row['routine'] }}</td>
                    <td>{{ $row['corrective'] }}</td>
                    <td>{{ number_format($row['cost'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No maintenance in this range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
