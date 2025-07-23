<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche Navette #{{ $ficheNavette->FNnumber }}</title>
    <style>
        /*
        IMPORTANT: CSS in PDFs often needs to be embedded directly
        or referenced via absolute paths. Tailwind classes won't work
        unless you compile them with something like PurgeCSS for this specific view.
        For simplicity, I've converted your Tailwind-ish styles to raw CSS.
        */
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Use a font that supports Arabic/special chars if needed */
            font-size: 10pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            width: 100%;
            color: #2c3e50;
        }
        .container {
            width: 100%;
            max-width: 80mm;
            margin: 0 auto; /* Center the content on the page */
            padding: 20px 10px 20px 10px;
        }
        .header-image {
            margin-top: 10px;
            width: 90%;
            max-height: 70px;
            margin-bottom: 10px;
            display: block; /* Ensure it takes its own line */
            margin-left: auto; /* Center image */
            margin-right: auto; /* Center image */
        }
        .ticket-header {
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 12pt;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            text-align: center;
        }
        .ticket-content {
            margin: 1px 0;
            padding: 0;
        }
        .ticket-row {
            margin: 5px 0;
            padding: 3px 0;
            border-bottom: 1px dotted #ccc;
        }
        .ticket-label {
            font-weight: bold;
            color: #34495e;
            display: inline-block; /* Keep label and value on same line */
            min-width: 120px; /* Adjust as needed for alignment */
        }
        .prestations-list {
            margin-left: 0px;
            font-size: 9pt;
            margin-bottom: 10px;
        }
        .prestations-list table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .prestations-list th, .prestations-list td {
            border: 1px solid #eee;
            padding: 4px;
            text-align: left;
        }
        .prestations-list th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .ticket-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 8pt;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        .footer-item {
            display: inline-block; /* For simple horizontal arrangement */
            width: 48%; /* Adjust based on desired spacing */
            text-align: left;
        }
        .footer-item.right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center;">
            {{-- Use asset() helper for public assets, which generates a full URL --}}
            <img src="{{ asset('storage/ENTETE.png') }}" class="header-image" alt="En-tête">
        </div>

        <div class="ticket-content">
            <div class="ticket-row">
                <span class="ticket-label">Patient :</span>
                {{ strtoupper($ficheNavette->patient->Firstname ?? 'N/A') }} {{ strtoupper($ficheNavette->patient->Lastname ?? 'N/A') }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Téléphone Patient :</span>
                {{ $ficheNavette->patient->phone ?? 'N/A' }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Assuré :</span>
                {{ strtoupper($ficheNavette->last_name_beneficiary ?? 'N/A') }} {{ strtoupper($ficheNavette->first_name_beneficiary ?? 'N/A') }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Téléphone Assuré :</span>
                {{ $ficheNavette->phone_beneficiary ?? 'N/A' }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">FN Numéro :</span>
                {{ $ficheNavette->FNnumber ?? 'N/A' }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Date :</span>
                {{ $ficheNavette->fiche_date ?? 'N/A' }}
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Phone :</span>
                029 23 99 99 / 05 55 88 99 97
            </div>

            @if ($ficheNavette->items->count() > 0)
                <div class="ticket-row">
                    <span class="ticket-label">Détails des Prestations :</span>
                </div>
                <div class="prestations-list">
                    <table>
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th>Charge Ent.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ficheNavette->items as $item)
                                <tr>
                                    <td>{{ $item->convention->designation_prestation ?? '-' }}</td>
                                    <td>{{ number_format($item->convention->montant_prise_charge_entreprise ?? 0, 2, ',', '.') }} DZD</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ticket-row">
                    <span class="ticket-label">Prestations :</span> Aucune prestation associée.
                </div>
            @endif

            <div class="ticket-row">
                <span class="ticket-label">Total Charge Bén. (Fiche) :</span>
                {{ number_format($ficheNavette->organisme_share ?? 0, 2, ',', '.') }} DZD
            </div>
        </div>

        <div class="ticket-footer">
            <div class="footer-item">
                Imprimé le : {{ $currentDateTime }}
            </div>
            <div class="footer-item right">
                Imprimé par : {{ $userName }}
            </div>
        </div>
    </div>
</body>
</html>