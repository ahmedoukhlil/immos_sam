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
            margin: 10mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, sans-serif;
        }
        .page {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(4, 50mm); /* 4 colonnes de 50mm */
            grid-template-rows: repeat(10, 20mm);   /* 10 lignes de 20mm */
            gap: 2mm;
            page-break-after: always;
        }
        .etiquette {
            width: 50mm;
            height: 20mm;
            padding: 0;
            border: 0;
            page-break-inside: avoid;
            box-sizing: border-box;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .barcode-wrapper {
            width: 100%;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1mm 2mm;
            overflow: hidden;
            min-height: 12.7mm; /* Hauteur minimale Code 128 */
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
            font-size: 6pt;
            font-weight: normal;
            font-family: 'Courier New', monospace;
            color: #000;
            padding: 0.5mm 0;
            letter-spacing: 0.2pt;
            line-height: 1.2;
        }
        svg {
            width: 100% !important;
            min-width: 37.3mm !important; /* Largeur minimale Code 128 */
            height: auto !important;
            min-height: 12.7mm !important; /* Hauteur minimale Code 128 */
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
        }
        /* Styles pour le code-barres HTML */
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
    </style>
</head>
<body>
    @php
        $biensChunked = $biens->chunk(10);
    @endphp
    
    @foreach($biensChunked as $chunk)
        <div class="page">
            @foreach($chunk as $bien)
                <div class="etiquette">
                    @if($bien->code && $bien->code->barcode)
                        {{-- Code-barres centré --}}
                        <div class="barcode-wrapper">
                            <div class="barcode-container">
                                @php
                                    $barcodeData = $bien->code->barcode;
                                    
                                    // Vérifier le format : HTML (divs/table), SVG, chemin de fichier, ou base64 PNG
                                    if (str_starts_with($barcodeData, '<div') || str_starts_with($barcodeData, '<table')) {
                                        // HTML généré par BarcodeGeneratorHTML (format principal)
                                        $htmlContent = $barcodeData;
                                    } elseif (str_starts_with($barcodeData, '<svg') || str_starts_with($barcodeData, '<?xml')) {
                                        // SVG
                                        $svgContent = $barcodeData;
                                        $svgContent = preg_replace('/width="[^"]*"/', '', $svgContent);
                                        $svgContent = preg_replace('/height="[^"]*"/', '', $svgContent);
                                        if (!str_contains($svgContent, 'style=')) {
                                            $svgContent = preg_replace('/<svg/', '<svg style="width: 100%; height: auto; max-width: 100%; display: block;"', $svgContent, 1);
                                        }
                                    } elseif (str_contains($barcodeData, '/') && !str_starts_with($barcodeData, '/9j/') && !str_starts_with($barcodeData, 'iVBORw0KGgo')) {
                                        // Probablement un chemin de fichier
                                        $imagePath = public_path('storage/' . $barcodeData);
                                    } else {
                                        // Base64 PNG
                                        $base64Image = $barcodeData;
                                    }
                                @endphp
                                
                                @if(isset($htmlContent))
                                    <div style="width: 100%; text-align: center; overflow: hidden;">
                                        {!! $htmlContent !!}
                                    </div>
                                @elseif(isset($svgContent))
                                    @php
                                        // Nettoyer et optimiser le SVG pour DomPDF
                                        $svgContent = preg_replace('/width="[^"]*"/', '', $svgContent);
                                        $svgContent = preg_replace('/height="[^"]*"/', '', $svgContent);
                                        
                                        // Ajouter viewBox si absent
                                        if (!preg_match('/viewBox=/', $svgContent)) {
                                            if (preg_match('/width="([^"]*)"/', $barcodeData, $widthMatch) && 
                                                preg_match('/height="([^"]*)"/', $barcodeData, $heightMatch)) {
                                                $width = floatval($widthMatch[1]);
                                                $height = floatval($heightMatch[1]);
                                                $svgContent = preg_replace('/<svg/', '<svg viewBox="0 0 ' . $width . ' ' . $height . '"', $svgContent, 1);
                                            } else {
                                                $svgContent = preg_replace('/<svg/', '<svg viewBox="0 0 200 40"', $svgContent, 1);
                                            }
                                        }
                                        
                                        // Ajouter preserveAspectRatio
                                        if (!preg_match('/preserveAspectRatio=/', $svgContent)) {
                                            $svgContent = preg_replace('/<svg/', ' preserveAspectRatio="xMidYMid meet"', $svgContent, 1);
                                        }
                                        
                                        // Ajouter les styles
                                        if (!str_contains($svgContent, 'style=')) {
                                            $svgContent = preg_replace('/<svg/', '<svg style="width: 100%; height: auto; max-width: 100%; display: block; margin: 0 auto;"', $svgContent, 1);
                                        } else {
                                            $svgContent = preg_replace('/style="([^"]*)"/', 'style="$1 width: 100%; height: auto; max-width: 100%; display: block; margin: 0 auto;"', $svgContent, 1);
                                        }
                                    @endphp
                                    <div style="width: 100%; display: flex; align-items: center; justify-content: center;">
                                        {!! $svgContent !!}
                                    </div>
                                @elseif(isset($imagePath) && file_exists($imagePath))
                                    <img src="{{ $imagePath }}" alt="Code-barres Code 128" style="width: 100%; height: auto; max-height: 100%; object-fit: contain; display: block;">
                                @elseif(isset($base64Image))
                                    <img src="data:image/png;base64,{{ $base64Image }}" alt="Code-barres Code 128" style="width: 100%; height: auto; max-height: 100%; object-fit: contain; display: block;">
                                @else
                                    <div style="text-align: center; color: #999; font-size: 10pt;">
                                        Code-barres non disponible
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Code en texte clair en bas --}}
                        <div class="code-text">
                            {{ $bien->code_formate ?? '' }}
                        </div>
                    @else
                        <div style="text-align: center; color: #999; font-size: 10pt; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <div>Code-barres non disponible</div>
                            <div class="code-text" style="margin-top: 2mm;">
                                {{ $bien->code_formate ?? $bien->NumOrdre }}
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
