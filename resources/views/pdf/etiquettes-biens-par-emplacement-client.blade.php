<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquettes — {{ $emplacement->Emplacement ?? 'N/A' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <div class="max-w-5xl mx-auto px-4 py-6 space-y-6" x-data="etiquettesPDF()" x-cloak>

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                    <a href="{{ route('biens.index') }}" class="hover:text-indigo-600 transition-colors">Immobilisations</a>
                    <span>/</span>
                    <span class="text-gray-700 font-medium">Impression des étiquettes</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $emplacement->Emplacement ?? 'Emplacement' }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-500">
                    @if($emplacement->localisation)
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $emplacement->localisation->Localisation }}
                        </span>
                    @endif
                    @if($emplacement->affectation)
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ $emplacement->affectation->Affectation }}
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                        @php
                            $includeQr = $includeEmplacementQr ?? true;
                            $totalEtiquettes = count($biensData) + ($includeQr ? 1 : 0);
                        @endphp
                        {{ $totalEtiquettes }} étiquette{{ $totalEtiquettes > 1 ? 's' : '' }}
                        @if($includeQr)
                            (1 QR emplacement + {{ count($biensData) }} bien{{ count($biensData) > 1 ? 's' : '' }})
                        @else
                            ({{ count($biensData) }} bien{{ count($biensData) > 1 ? 's' : '' }})
                        @endif
                        &middot; {{ ceil($totalEtiquettes / 33) }} page{{ ceil($totalEtiquettes / 33) > 1 ? 's' : '' }}
                    </span>
                </div>
            </div>

            {{-- Boutons d'actions --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('biens.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Retour
                </a>

                <button
                    x-show="!generated"
                    @click="generate()"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <span x-text="loading ? 'Génération…' : 'Générer le PDF'"></span>
                </button>

                <template x-if="generated">
                    <div class="flex items-center gap-2">
                        <button @click="download()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Télécharger
                        </button>
                        <button @click="print()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Imprimer
                        </button>
                        <button @click="generate()" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Regénérer
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Statut --}}
        <div x-show="statusText" class="rounded-lg border px-4 py-3 text-sm flex items-center gap-2"
             :class="{
                 'bg-blue-50 border-blue-200 text-blue-700': statusType === 'info',
                 'bg-yellow-50 border-yellow-200 text-yellow-700': statusType === 'loading',
                 'bg-green-50 border-green-200 text-green-700': statusType === 'success',
                 'bg-red-50 border-red-200 text-red-700': statusType === 'error'
             }" x-transition>
            <svg x-show="statusType === 'loading'" class="w-4 h-4 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
            <svg x-show="statusType === 'success'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="statusType === 'error'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="statusType === 'info'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="statusText"></span>
        </div>

        {{-- Progression --}}
        <div x-show="loading" x-transition class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progression</span>
                <span class="text-sm text-gray-500" x-text="progressCurrent + ' / ' + progressTotal"></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-200" :style="'width: ' + progressPercent + '%'"></div>
            </div>
        </div>

        {{-- Prévisualisation PDF --}}
        <div x-show="generated" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <iframe id="pdfContainer" class="w-full border-0" style="height: 70vh; min-height: 500px;"></iframe>
        </div>

        {{-- Tableau des biens --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Liste des immobilisations</h3>
                <span class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">{{ count($biensData) }} bien{{ count($biensData) > 1 ? 's' : '' }}</span>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">#</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Ordre</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code formaté</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Désignation</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($biensData as $index => $bien)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 font-semibold text-indigo-600">{{ $bien['NumOrdre'] }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $bien['code_formate'] ?: '—' }}</td>
                            <td class="px-4 py-2 text-gray-700 max-w-xs truncate">{{ $bien['designation'] ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Bibliothèques JS --}}
    <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

    <script>
        function etiquettesPDF() {
            return {
                biensData: @json($biensData),
                emplacementName: @json($emplacement->Emplacement ?? 'Emplacement'),
                emplacementId: @json($emplacement->idEmplacement),
                localisationName: @json($emplacement->localisation->Localisation ?? ''),
                qrDataUri: @json($qrDataUri),

                loading: false,
                generated: false,
                statusType: 'info',
                statusText: @json(
                    ($includeEmplacementQr ?? true)
                        ? 'Prêt à générer ' . (count($biensData) + 1) . ' étiquette' . ((count($biensData) + 1) > 1 ? 's' : '') . ' (1 QR emplacement + ' . count($biensData) . ' bien' . (count($biensData) > 1 ? 's' : '') . ').'
                        : 'Prêt à générer ' . count($biensData) . ' étiquette' . (count($biensData) > 1 ? 's' : '') . ' (' . count($biensData) . ' bien' . (count($biensData) > 1 ? 's' : '') . ').'
                ),
                progressCurrent: 0,
                includeEmplacementQr: @json($includeEmplacementQr ?? true),
                progressTotal: {{ count($biensData) + (($includeEmplacementQr ?? true) ? 1 : 0) }},
                progressPercent: 0,
                pdfBlobUrl: null,

                // ── Dimensions fixes : étiquettes 70mm × 24.4mm ──
                // 1 mm = 2.83465 pts
                MM: 2.83465,
                A4_W: 595.28,    // 210mm
                A4_H: 841.89,    // 297mm
                LABEL_W_MM: 70,
                LABEL_H_MM: 24.4,
                COLS: 3,         // 3 × 70mm = 210mm = largeur A4
                MARGIN_TOP_MM: 7,    // marge haute 7mm
                MARGIN_BOTTOM_MM: 7, // marge basse 7mm

                get LABEL_W() { return this.LABEL_W_MM * this.MM; },
                get LABEL_H() { return this.LABEL_H_MM * this.MM; },
                get MARGIN_LEFT() { return (this.A4_W - this.COLS * this.LABEL_W) / 2; },
                get MARGIN_TOP()  { return this.MARGIN_TOP_MM * this.MM; },
                get MARGIN_BOTTOM() { return this.MARGIN_BOTTOM_MM * this.MM; },
                // Espace disponible entre marges haute et basse
                get AVAILABLE_H() { return this.A4_H - this.MARGIN_TOP - this.MARGIN_BOTTOM; },
                // Nombre max de lignes
                get ROWS() { return Math.floor(this.AVAILABLE_H / this.LABEL_H); },
                get TOTAL() { return this.COLS * this.ROWS; },
                // Espacement vertical entre lignes pour répartir uniformément
                // (espace restant / nombre d'intervalles entre lignes)
                get ROW_GAP() {
                    const usedH = this.ROWS * this.LABEL_H;
                    const remaining = this.AVAILABLE_H - usedH;
                    return this.ROWS > 1 ? remaining / (this.ROWS - 1) : 0;
                },
                // Pas vertical : hauteur étiquette + espacement
                get ROW_PITCH() { return this.LABEL_H + this.ROW_GAP; },

                setStatus(type, text) { this.statusType = type; this.statusText = text; },

                async dataUriToEmbeddedPng(pdfDoc, dataUri, size = 360) {
                    return await new Promise((resolve, reject) => {
                        const img = new Image();
                        img.onload = async () => {
                            const c = document.createElement('canvas');
                            c.width = size;
                            c.height = size;
                            const ctx = c.getContext('2d');
                            ctx.fillStyle = '#fff';
                            ctx.fillRect(0, 0, size, size);
                            ctx.drawImage(img, 0, 0, size, size);
                            try {
                                const embedded = await pdfDoc.embedPng(c.toDataURL('image/png'));
                                resolve(embedded);
                            } catch (e) {
                                reject(e);
                            }
                        };
                        img.onerror = () => reject(new Error('QR bien non lisible'));
                        img.src = dataUri;
                    });
                },

                async generate() {
                    this.loading = true;
                    this.generated = false;
                    this.progressCurrent = 0;
                    this.progressPercent = 0;
                    this.setStatus('loading', 'Génération du PDF en cours…');

                    try {
                        const { PDFDocument } = PDFLib;
                        const pdfDoc = await PDFDocument.create();
                        const font = await pdfDoc.embedFont(PDFLib.StandardFonts.Helvetica);
                        const fontBold = await pdfDoc.embedFont(PDFLib.StandardFonts.HelveticaBold);

                        // Total d'emplacements sur la grille : N biens (+ 1 QR emplacement si activé)
                        const totalSlots = this.biensData.length + (this.includeEmplacementQr ? 1 : 0);
                        const totalPages = Math.ceil(totalSlots / this.TOTAL);

                        const mm = this.MM;
                        // Layout horizontal: QR à gauche, texte à droite
                        const INNER_PAD = 1.5 * mm;
                        const QR_TEXT_GAP = 2.0 * mm;
                        const QR_SIZE = 16.0 * mm;
                        const CODE_Y_OFFSET = 10.0 * mm;
                        const DESIG_Y_OFFSET = 15.5 * mm;
                        const FS_CODE = 6.5;
                        const FS_DESIG = 5;

                        let qrContent = null;
                        let qrImg = null;
                        if (this.includeEmplacementQr) {
                            // ── Convertir le QR code SVG (généré côté serveur) en PNG ──
                            qrContent = `EMP-${this.emplacementId}`;
                            qrImg = await new Promise((resolve, reject) => {
                                const img = new Image();
                                img.onload = async () => {
                                    const c = document.createElement('canvas');
                                    c.width = 360; c.height = 360;
                                    const ctx = c.getContext('2d');
                                    ctx.fillStyle = '#fff';
                                    ctx.fillRect(0, 0, 360, 360);
                                    ctx.drawImage(img, 0, 0, 360, 360);
                                    try {
                                        const embedded = await pdfDoc.embedPng(c.toDataURL('image/png'));
                                        resolve(embedded);
                                    } catch (e) { reject(e); }
                                };
                                img.onerror = () => reject(new Error('Impossible de charger le QR code SVG'));
                                img.src = this.qrDataUri;
                            });
                        }

                        for (let pi = 0; pi < totalPages; pi++) {
                            const page = pdfDoc.addPage([this.A4_W, this.A4_H]);
                            const slotStart = pi * this.TOTAL;
                            const slotEnd = Math.min(slotStart + this.TOTAL, totalSlots);

                            for (let slot = slotStart; slot < slotEnd; slot++) {
                                const posOnPage = slot - slotStart;
                                const col = posOnPage % this.COLS;
                                const row = Math.floor(posOnPage / this.COLS);

                                const labelX = this.MARGIN_LEFT + col * this.LABEL_W;
                                const labelTopY = this.A4_H - this.MARGIN_TOP - row * this.ROW_PITCH;

                                if (this.includeEmplacementQr && slot === 0) {
                                    // ══════ PREMIÈRE ÉTIQUETTE : QR CODE EMPLACEMENT ══════
                                    // Même layout que les QR biens : image en haut,
                                    // texte 1 centré à 15.5mm, texte 2 centré à 19mm
                                    const qrX = labelX + INNER_PAD;
                                    const qrY = labelTopY - ((this.LABEL_H - QR_SIZE) / 2) - QR_SIZE;
                                    page.drawImage(qrImg, { x: qrX, y: qrY, width: QR_SIZE, height: QR_SIZE });

                                    const textLeft = qrX + QR_SIZE + QR_TEXT_GAP;
                                    const textMaxW = labelX + this.LABEL_W - INNER_PAD - textLeft;

                                    // Nom de l'emplacement (ligne 1, à droite du QR)
                                    let empText = this.emplacementName;
                                    while (fontBold.widthOfTextAtSize(empText, FS_CODE) > textMaxW && empText.length > 1) {
                                        empText = empText.slice(0, -1);
                                    }
                                    if (empText.length < this.emplacementName.length) empText += '…';
                                    page.drawText(empText, {
                                        x: textLeft,
                                        y: labelTopY - CODE_Y_OFFSET,
                                        size: FS_CODE, font: fontBold,
                                        color: PDFLib.rgb(0, 0, 0)
                                    });

                                    // Code EMP-id (ligne 2)
                                    let empCodeText = qrContent;
                                    while (font.widthOfTextAtSize(empCodeText, FS_DESIG) > textMaxW && empCodeText.length > 1) {
                                        empCodeText = empCodeText.slice(0, -1);
                                    }
                                    if (empCodeText.length < qrContent.length) empCodeText += '…';
                                    page.drawText(empCodeText, {
                                        x: textLeft,
                                        y: labelTopY - DESIG_Y_OFFSET,
                                        size: FS_DESIG, font,
                                        color: PDFLib.rgb(0.3, 0.3, 0.3)
                                    });

                                    continue;
                                }

                                // ══════ ÉTIQUETTES BIENS (décalées de 1) ══════
                                const bienIndex = this.includeEmplacementQr ? slot - 1 : slot;
                                const b = this.biensData[bienIndex];
                                if (!b) continue;

                                const val = String(b.barcode_value || b.NumOrdre).trim();
                                const code = String(b.code_formate || '').trim();
                                const desig = String(b.designation || '').trim();
                                if (!val) continue;

                                let qrDrawn = false;
                                if (b.qr_data_uri) {
                                    const img = await this.dataUriToEmbeddedPng(pdfDoc, String(b.qr_data_uri), 320);
                                    const qrX = labelX + INNER_PAD;
                                    const qrY = labelTopY - ((this.LABEL_H - QR_SIZE) / 2) - QR_SIZE;
                                    page.drawImage(img, { x: qrX, y: qrY, width: QR_SIZE, height: QR_SIZE });
                                    qrDrawn = true;
                                }

                                const textLeft = labelX + INNER_PAD + (qrDrawn ? (QR_SIZE + QR_TEXT_GAP) : 0);
                                const textMaxW = labelX + this.LABEL_W - INNER_PAD - textLeft;

                                if (code) {
                                    let codeTxt = code;
                                    while (font.widthOfTextAtSize(codeTxt, FS_CODE) > textMaxW && codeTxt.length > 1) {
                                        codeTxt = codeTxt.slice(0, -1);
                                    }
                                    if (codeTxt.length < code.length) codeTxt += '…';
                                    page.drawText(codeTxt, {
                                        x: textLeft,
                                        y: labelTopY - CODE_Y_OFFSET,
                                        size: FS_CODE, font,
                                        color: PDFLib.rgb(0, 0, 0)
                                    });
                                }

                                if (desig) {
                                    let txt = desig;
                                    while (font.widthOfTextAtSize(txt, FS_DESIG) > textMaxW && txt.length > 0) {
                                        txt = txt.slice(0, -1);
                                    }
                                    if (txt.length < desig.length) txt += '…';
                                    page.drawText(txt, {
                                        x: textLeft,
                                        y: labelTopY - DESIG_Y_OFFSET,
                                        size: FS_DESIG, font,
                                        color: PDFLib.rgb(0, 0, 0)
                                    });
                                }

                                this.progressCurrent = Math.min(slot + 1, this.progressTotal);
                                this.progressPercent = Math.round((this.progressCurrent / this.progressTotal) * 100);
                            }
                        }

                        const bytes = await pdfDoc.save();
                        if (this.pdfBlobUrl) URL.revokeObjectURL(this.pdfBlobUrl);
                        this.pdfBlobUrl = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
                        document.getElementById('pdfContainer').src = this.pdfBlobUrl;

                        this.loading = false;
                        this.generated = true;
                        this.setStatus(
                            'success',
                            this.includeEmplacementQr
                                ? `PDF généré — ${totalPages} page${totalPages > 1 ? 's' : ''}, ${totalSlots} étiquette${totalSlots > 1 ? 's' : ''} (1 QR emplacement + ${this.biensData.length} bien${this.biensData.length > 1 ? 's' : ''}).`
                                : `PDF généré — ${totalPages} page${totalPages > 1 ? 's' : ''}, ${totalSlots} étiquette${totalSlots > 1 ? 's' : ''} (${this.biensData.length} bien${this.biensData.length > 1 ? 's' : ''}).`
                        );
                    } catch (err) {
                        console.error(err);
                        this.loading = false;
                        this.setStatus('error', 'Erreur : ' + err.message);
                    }
                },

                download() {
                    if (!this.pdfBlobUrl) return;
                    const a = document.createElement('a');
                    a.href = this.pdfBlobUrl;
                    a.download = 'etiquettes-' + this.emplacementName.replace(/[^a-zA-Z0-9]/g, '_') + '.pdf';
                    a.click();
                },

                print() {
                    const frame = document.getElementById('pdfContainer');
                    if (frame && frame.contentWindow) {
                        try { frame.contentWindow.print(); } catch {
                            const w = window.open(this.pdfBlobUrl, '_blank');
                            if (w) w.onload = () => w.print();
                        }
                    }
                }
            }
        }
    </script>
</body>
</html>
