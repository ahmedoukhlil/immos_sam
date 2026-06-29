<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }
        .header .date {
            font-size: 10pt;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }
        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 8pt;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7pt;
        }
        .badge-neuf, .badge-bon {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-moyen {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-mauvais {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-reforme {
            background-color: #e2e3e5;
            color: #383d41;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Liste des Immobilisations - Inventaire</h1>
        <div class="date">Généré le {{ $date->format('d/m/Y à H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Code</th>
                <th style="width: 25%;">Désignation</th>
                <th style="width: 8%;">Nature</th>
                <th style="width: 15%;">Localisation</th>
                <th style="width: 12%;">Service</th>
                <th style="width: 10%;" class="text-right">Valeur (MRU)</th>
                <th style="width: 8%;">État</th>
                <th style="width: 12%;">Date Acquisition</th>
            </tr>
        </thead>
        <tbody>
            @foreach($biens as $bien)
                <tr>
                    <td>{{ $bien->code_inventaire }}</td>
                    <td>{{ Str::limit($bien->designation, 40) }}</td>
                    <td>{{ ucfirst($bien->nature) }}</td>
                    <td>
                        @if($bien->localisation)
                            {{ $bien->localisation->code }} - {{ Str::limit($bien->localisation->designation, 20) }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $bien->service_usager }}</td>
                    <td class="text-right">{{ number_format($bien->valeur_acquisition, 0, ',', ' ') }}</td>
                    <td>
                        <span class="badge badge-{{ $bien->etat }}">{{ ucfirst($bien->etat) }}</span>
                    </td>
                    <td>{{ $bien->date_acquisition->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Page <span class="page"></span> - Total: {{ $biens->count() }} immobilisation(s)
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("Arial");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() / 2) - $width;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>

