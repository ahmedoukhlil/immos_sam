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
        @page {
            margin: 5mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, sans-serif;
        }
        .page {
            width: 100%;
            display: grid;
            /* 3 colonnes : (210mm - 10mm marges) / 3 = ~66.67mm par colonne */
            /* Avec espacement de 2mm entre colonnes : (200mm - 4mm) / 3 = ~65.33mm */
            grid-template-columns: repeat(3, 1fr);
            /* 7 lignes : (297mm - 10mm marges) / 7 = ~41mm par ligne */
            /* Avec espacement de 2mm entre lignes : (287mm - 12mm) / 7 = ~39.29mm */
            grid-template-rows: repeat(7, 1fr);
            gap: 2mm;
            page-break-after: always;
            height: 287mm; /* 297mm - 10mm marges */
        }
        .etiquette {
            width: 100%;
            height: 100%;
            padding: 1mm;
            border: 0.5px solid #ddd;
            page-break-inside: avoid;
            box-sizing: border-box;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: white;
        }
        .barcode-wrapper {
            width: 100%;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5mm;
            overflow: hidden;
            min-height: 20mm;
        }
        .barcode-container {
            width: 100%;
            min-width: 37.3mm; /* Largeur minimale Code 128 */
            max-width: 100%;
            text-align: center;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .code-text {
            width: 100%;
            text-align: center;
            font-size: 5pt;
            font-weight: normal;
            font-family: 'Courier New', monospace;
            color: #000;
            padding: 0.5mm 0;
            letter-spacing: 0.1pt;
            line-height: 1.1;
            word-break: break-all;
        }
        svg {
            width: 100% !important;
            min-width: 37.3mm !important;
            height: auto !important;
            min-height: 12.7mm !important;
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
        }
        table {
            margin: 0 auto !important;
            width: auto !important;
            max-width: 100% !important;
            border-collapse: collapse !important;
        }
        table td {
            padding: 0 !important;
            border: 0 !important;
            margin: 0 !important;
        }
        div[style*="display: inline-block"] {
            display: inline-block !important;
        }
        .header-info {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 1mm;
            color: #333;
        }
    </style>
</head>
<body>
    @php
        // Grouper les biens par page (21 étiquettes par page)
        $biensChunked = $biens->chunk(21);
    @endphp
    
    @foreach($biensChunked as $pageIndex => $chunk)
        <div class="page">
            @if($pageIndex === 0 && $emplacement)
                {{-- En-tête sur la première page --}}
                <div style="grid-column: 1 / -1; text-align: center; padding: 2mm; font-size: 10pt; font-weight: bold; border-bottom: 1px solid #ddd; margin-bottom: 1mm;">
                    <div>Emplacement: {{ $emplacement->Emplacement ?? 'N/A' }}</div>
                    @if($emplacement->localisation)
                        <div style="font-size: 8pt; font-weight: normal; margin-top: 1mm;">
                            Localisation: {{ $emplacement->localisation->Localisation ?? 'N/A' }}
                        </div>
                    @endif
                    @if($emplacement->affectation)
                        <div style="font-size: 8pt; font-weight: normal;">
                            Affectation: {{ $emplacement->affectation->Affectation ?? 'N/A' }}
                        </div>
                    @endif
                    <div style="font-size: 7pt; font-weight: normal; margin-top: 1mm; color: #666;">
                        Total: {{ $biens->count() }} immobilisation(s) | Page {{ $pageIndex + 1 }} / {{ $biensChunked->count() }}
                    </div>
                </div>
            @endif
            
            @foreach($chunk as $bien)
                <div class="etiquette">
                    {{-- Code-barres centré --}}
                    <div class="barcode-wrapper">
                        <div class="barcode-container">
                            @php
                                // Le code-barres est généré côté client, on affiche juste le code formaté
                                // Pour l'impression PDF, on génère un SVG simple ou on utilise le code formaté
                                $codeFormate = $bien->code_formate ?? $bien->NumOrdre;
                                
                                // Si le bien a un code-barres stocké, on l'utilise
                                if ($bien->code && $bien->code->barcode) {
                                    $barcodeData = $bien->code->barcode;
                                    
                                    // Vérifier le format
                                    if (str_starts_with($barcodeData, '<div') || str_starts_with($barcodeData, '<table')) {
                                        $htmlContent = $barcodeData;
                                    } elseif (str_starts_with($barcodeData, '<svg') || str_starts_with($barcodeData, '<?xml')) {
                                        $svgContent = $barcodeData;
                                        $svgContent = preg_replace('/width="[^"]*"/', '', $svgContent);
                                        $svgContent = preg_replace('/height="[^"]*"/', '', $svgContent);
                                        if (!str_contains($svgContent, 'style=')) {
                                            $svgContent = preg_replace('/<svg/', '<svg style="width: 100%; height: auto; max-width: 100%; display: block;"', $svgContent, 1);
                                        }
                                    } elseif (str_contains($barcodeData, '/') && !str_starts_with($barcodeData, '/9j/') && !str_starts_with($barcodeData, 'iVBORw0KGgo')) {
                                        $imagePath = public_path('storage/' . $barcodeData);
                                    } else {
                                        $base64Image = $barcodeData;
                                    }
                                } else {
                                    // Pas de code-barres stocké, on affiche juste le code
                                    $noBarcode = true;
                                }
                            @endphp
                            
                            @if(isset($htmlContent))
                                <div style="width: 100%; text-align: center; overflow: hidden;">
                                    {!! $htmlContent !!}
                                </div>
                            @elseif(isset($svgContent))
                                <div style="width: 100%; display: flex; align-items: center; justify-content: center;">
                                    {!! $svgContent !!}
                                </div>
                            @elseif(isset($imagePath) && file_exists($imagePath))
                                <img src="{{ $imagePath }}" alt="Code-barres Code 128" style="width: 100%; height: auto; max-height: 100%; object-fit: contain; display: block;">
                            @elseif(isset($base64Image))
                                <img src="data:image/png;base64,{{ $base64Image }}" alt="Code-barres Code 128" style="width: 100%; height: auto; max-height: 100%; object-fit: contain; display: block;">
                            @else
                                <div style="text-align: center; color: #999; font-size: 8pt; padding: 2mm;">
                                    Code-barres à générer
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Code en texte clair en bas --}}
                    <div class="code-text">
                        {{ $codeFormate }}
                    </div>
                </div>
            @endforeach
            
            {{-- Remplir les cases vides si nécessaire (pour avoir exactement 21 étiquettes) --}}
            @for($i = $chunk->count(); $i < 21; $i++)
                <div class="etiquette" style="border: 0.5px dashed #eee; background: #f9f9f9;">
                    <div style="text-align: center; color: #ccc; font-size: 7pt;">
                        Vide
                    </div>
                </div>
            @endfor
        </div>
    @endforeach
</body>
</html>
