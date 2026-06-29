/**
 * Application PWA Inventaire v2 - Workflow par Emplacement
 * Scan QR code emplacement → Scan QR biens → Calcul écarts
 */

console.log('[App v2] Démarrage...');

// ===========================================
// CONFIGURATION
// ===========================================

const CONFIG = {
    API_BASE_URL: window.location.origin + '/api/v1',
    STORAGE_KEY_TOKEN: 'inventaire_token_v2',
    STORAGE_KEY_USER: 'inventaire_user_v2',
    SESSION: {
        inactivityTimeoutMs: 2 * 60 * 60 * 1000 // 2h
    },
    SCANNER: {
        qr: {
            width: 960,
            height: 540,
            decodeIntervalMs: 80,
            decodeWidth: 400,
            decodeHeight: 300,
            detectCooldownMs: 1200
        },
        barcode: {
            width: 640,
            height: 480,
            frequency: 10,
            detectCooldownMs: 250
        }
    }
};

// ===========================================
// HAPTIC FEEDBACK (Vibrations) - Mobile
// ===========================================

class HapticFeedback {
    static isSupported() {
        return 'vibrate' in navigator;
    }

    static light() {
        if (this.isSupported()) {
            navigator.vibrate(10);
        }
    }

    static medium() {
        if (this.isSupported()) {
            navigator.vibrate(20);
        }
    }

    static heavy() {
        if (this.isSupported()) {
            navigator.vibrate(50);
        }
    }

    static success() {
        if (this.isSupported()) {
            navigator.vibrate([20, 50, 20]);
        }
    }

    static error() {
        if (this.isSupported()) {
            navigator.vibrate([50, 100, 50, 100, 50]);
        }
    }

    static warning() {
        if (this.isSupported()) {
            navigator.vibrate([30, 50, 30]);
        }
    }
}

// ===========================================
// STATE
// ===========================================

const AppState = {
    token: null,
    user: null,
    currentEmplacement: null,
    biensAttendus: [],
    biensScannés: [], // [{ num_ordre, etat_id, photo? }]
    etats: [], // [{ id, label, require_photo }] depuis API /etats
    scannerActive: false,
    modalBienEnCours: null, // bien en attente de confirmation (num_ordre, designation)
    qrProcessing: false,
    qrLastDecodedAt: 0,
    qrLastDetectedAt: 0,
    barcodeProcessing: false,
    barcodeLastDetectedAt: 0,
    barcodeLastCode: null,
    barcodeModalOpen: false,
    barcodeScannerActive: false,
    barcodeNativeLoopActive: false,
    barcodeInputMode: 'scan', // 'scan' | 'manual'
    biensAttendusIndex: new Map(), // key: num_ordre
    biensScannesIndex: new Map(),  // key: num_ordre
    lastToastKey: null,
    lastToastAt: 0,
    barcodeLastInvalidAt: 0,
    lastActivityAt: 0,
    emplacementInputMode: 'scan', // 'scan' | 'manual'
    empTorchEnabled: false,
    bienTorchEnabled: false,
};

// ===========================================
// NORMALISATION CODES SCANNES / NUM_ORDRE
// ===========================================

function normalizeNumOrdre(value) {
    if (value === null || value === undefined) return null;
    const num = Number.parseInt(String(value).trim(), 10);
    return Number.isFinite(num) && num > 0 ? num : null;
}

function extractNumOrdreFromBarcode(rawCode) {
    if (!rawCode) return null;

    // Nettoyer les caractères de contrôle parfois renvoyés par le scanner
    const cleaned = String(rawCode)
        .replace(/[\u0000-\u001F\u007F]/g, '')
        .trim();

    if (!cleaned) return null;

    // 1) Format numérique pur (ex: "12345")
    if (/^\d+$/.test(cleaned)) {
        return normalizeNumOrdre(cleaned);
    }

    // 2) Format type code inventaire "XXX/YYY/.../12345"
    const bySlash = cleaned.match(/\/(\d+)\s*$/);
    if (bySlash) {
        return normalizeNumOrdre(bySlash[1]);
    }

    // 3) Format préfixé type "GS12345"
    const bySuffix = cleaned.match(/(\d+)\s*$/);
    if (bySuffix) {
        return normalizeNumOrdre(bySuffix[1]);
    }

    return null;
}

function rebuildBiensIndexes() {
    AppState.biensAttendusIndex = new Map(
        (AppState.biensAttendus || [])
            .filter(b => b && b.num_ordre)
            .map(b => [b.num_ordre, b])
    );

    AppState.biensScannesIndex = new Map(
        (AppState.biensScannés || [])
            .filter(b => b && b.num_ordre)
            .map(b => [b.num_ordre, b])
    );
}

// ===========================================
// JSQR QR DECODER
// ===========================================

class QRDecoder {
    static hasJsQR() {
        return typeof window.jsQR === 'function';
    }

    static decodeImageData(imageData) {
        if (!this.hasJsQR()) {
            throw new Error('jsQR non chargé');
        }
        const result = window.jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'attemptBoth',
        });
        return result && result.data ? String(result.data) : null;
    }

    static isHarmlessError(error) {
        if (!error) return true;
        const msg = String(error.message || '');
        return msg.includes('jsQR') && msg.includes('non chargé');
    }
}

// ===========================================
// API HELPER
// ===========================================

class API {
    static async request(endpoint, options = {}) {
        const url = `${CONFIG.API_BASE_URL}${endpoint}`;
        const method = options.method || 'GET';
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        if (AppState.token) {
            defaultOptions.headers['Authorization'] = `Bearer ${AppState.token}`;
        }

        const finalOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {})
            }
        };

        try {
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                if (response.status === 401) {
                    AuthManager.logout();
                    throw new Error('Session expirée');
                }
                
                const errorText = await response.text();
                try {
                    const errorData = JSON.parse(errorText);
                    throw new Error(errorData.message || 'Erreur API');
                } catch {
                    throw new Error(`Erreur HTTP ${response.status}`);
                }
            }

            return await response.json();
        } catch (error) {
            console.error('[API] Erreur:', error);
            throw error;
        }
    }
}

// ===========================================
// AUTH MANAGER
// ===========================================

class AuthManager {
    static async login(username, password) {
        try {
            const response = await API.request('/login', {
                method: 'POST',
                body: JSON.stringify({ 
                    users: username, 
                    mdp: password 
                })
            });

            AppState.token = response.token;
            AppState.user = response.user;
            
            localStorage.setItem(CONFIG.STORAGE_KEY_TOKEN, response.token);
            localStorage.setItem(CONFIG.STORAGE_KEY_USER, JSON.stringify(response.user));

            await UI.loadEtats();

            HapticFeedback.success();
            UI.showView('scanner');
            UI.updateUserInfo();
            SessionSecurityManager.start();
            UI.showToast('✅ Connexion réussie', 'success');
        } catch (error) {
            HapticFeedback.error();
            throw error;
        }
    }

    static async logout(options = {}) {
        const reason = options.reason || 'manual';
        const showToast = options.showToast !== false;

        SessionSecurityManager.stop();
        ScannerManager.stopScanner();
        BarcodeScannerManager.stopBarcodeScanner();

        AppState.token = null;
        AppState.user = null;
        localStorage.removeItem(CONFIG.STORAGE_KEY_TOKEN);
        localStorage.removeItem(CONFIG.STORAGE_KEY_USER);
        await this.clearSensitiveClientData();
        
        HapticFeedback.medium();
        UI.showView('login');
        if (showToast) {
            if (reason === 'inactivity') {
                UI.showToast('🔒 Déconnexion automatique après 2h d\'inactivité', 'warning');
            } else {
                UI.showToast('👋 Déconnexion', 'info');
            }
        }
    }

    static checkAuth() {
        const token = localStorage.getItem(CONFIG.STORAGE_KEY_TOKEN);
        const userStr = localStorage.getItem(CONFIG.STORAGE_KEY_USER);

        if (token && userStr) {
            try {
                AppState.token = token;
                AppState.user = JSON.parse(userStr);
                UI.loadEtats().then(() => {});
                UI.showView('scanner');
                UI.updateUserInfo();
                SessionSecurityManager.start();
                return true;
            } catch {
                this.logout();
            }
        }
        return false;
    }

    static async clearSensitiveClientData() {
        try {
            localStorage.clear();
        } catch (_) {}

        try {
            sessionStorage.clear();
        } catch (_) {}

        // Supprime les cookies accessibles en JavaScript (HttpOnly non accessibles côté client)
        try {
            const host = window.location.hostname;
            const baseCookies = document.cookie ? document.cookie.split(';') : [];
            baseCookies.forEach(raw => {
                const cookieName = raw.split('=')[0].trim();
                if (!cookieName) return;
                const expires = 'expires=Thu, 01 Jan 1970 00:00:00 GMT';
                document.cookie = `${cookieName}=; ${expires}; path=/`;
                document.cookie = `${cookieName}=; ${expires}; path=/; domain=${host}`;
                if (host.includes('.')) {
                    document.cookie = `${cookieName}=; ${expires}; path=/; domain=.${host}`;
                }
            });
        } catch (_) {}

        // Note: on ne supprime PAS les caches PWA (service worker) ni IndexedDB
        // pour permettre à l'app de continuer à fonctionner hors-ligne après déconnexion.
    }
}

// ===========================================
// SESSION SECURITY MANAGER
// ===========================================

class SessionSecurityManager {
    static _timer = null;
    static _boundActivityHandler = null;
    static _boundVisibilityHandler = null;
    static _events = ['click', 'keydown', 'touchstart', 'pointerdown', 'scroll', 'mousemove'];

    static start() {
        if (!AppState.token) return;
        this.stop();
        AppState.lastActivityAt = Date.now();

        this._boundActivityHandler = () => this.registerActivity();
        this._boundVisibilityHandler = () => {
            if (document.visibilityState === 'visible') {
                this.checkNow();
            }
        };

        this._events.forEach(evt => {
            window.addEventListener(evt, this._boundActivityHandler, { passive: true });
        });
        document.addEventListener('visibilitychange', this._boundVisibilityHandler);

        this.schedule();
    }

    static stop() {
        clearTimeout(this._timer);
        this._timer = null;

        if (this._boundActivityHandler) {
            this._events.forEach(evt => {
                window.removeEventListener(evt, this._boundActivityHandler);
            });
        }
        if (this._boundVisibilityHandler) {
            document.removeEventListener('visibilitychange', this._boundVisibilityHandler);
        }

        this._boundActivityHandler = null;
        this._boundVisibilityHandler = null;
    }

    static registerActivity() {
        if (!AppState.token) return;
        AppState.lastActivityAt = Date.now();
        this.schedule();
    }

    static schedule() {
        clearTimeout(this._timer);
        if (!AppState.token) return;

        const elapsed = Date.now() - (AppState.lastActivityAt || 0);
        const remaining = CONFIG.SESSION.inactivityTimeoutMs - elapsed;
        if (remaining <= 0) {
            this.handleInactivityTimeout();
            return;
        }

        this._timer = setTimeout(() => this.checkNow(), remaining);
    }

    static checkNow() {
        if (!AppState.token) return;
        const elapsed = Date.now() - (AppState.lastActivityAt || 0);
        if (elapsed >= CONFIG.SESSION.inactivityTimeoutMs) {
            this.handleInactivityTimeout();
            return;
        }
        this.schedule();
    }

    static handleInactivityTimeout() {
        AuthManager.logout({ reason: 'inactivity', showToast: true });
    }
}

// ===========================================
// SCANNER MANAGER - QR Code pour Emplacement
// ===========================================

class ScannerManager {
    static async startQRScanner() {
        if (AppState.emplacementInputMode === 'manual') {
            return;
        }

        // Vérifier que jsQR est disponible
        if (!QRDecoder.hasJsQR()) {
            console.error('[Scanner] jsQR n\'est pas chargé');
            HapticFeedback.error();
            UI.showToast('❌ Erreur: Bibliothèque QR code non chargée. Rechargez la page.', 'error');
            return;
        }

        const container = document.getElementById('scanner-container');
        this.stopScanner();
        container.innerHTML = `
            <video id="qr-video" class="w-full h-full object-cover" autoplay playsinline muted></video>
            <button id="emp-torch-toggle" class="hidden absolute top-2 right-2 z-20 px-3 py-1.5 rounded-full text-xs font-semibold bg-black/60 text-white backdrop-blur-sm">
                🔦 Torche off
            </button>
            <div class="scan-overlay">
                <div class="scan-reticle">
                    <span class="scan-corner tl"></span>
                    <span class="scan-corner tr"></span>
                    <span class="scan-corner bl"></span>
                    <span class="scan-corner br"></span>
                </div>
                <span class="scan-overlay-hint">Placez le QR dans le cadre</span>
            </div>
        `;

        try {
            // Configuration optimisée pour mobile Android et iOS
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment', // Caméra arrière
                    width: { ideal: CONFIG.SCANNER.qr.width, max: 1280 },
                    height: { ideal: CONFIG.SCANNER.qr.height, max: 720 },
                    aspectRatio: { ideal: 16 / 9 },
                    frameRate: { ideal: 24, max: 30 }
                },
                audio: false
            });

            const video = document.getElementById('qr-video');
            video.srcObject = stream;
            this._videoTrack = stream.getVideoTracks()[0] || null;
            this._setupTorchToggle();

            await new Promise(resolve => {
                video.addEventListener('loadedmetadata', resolve, { once: true });
            });
            await video.play();

            console.log('[Scanner] Caméra prête:', video.videoWidth, 'x', video.videoHeight);
            AppState.scannerActive = true;
            HapticFeedback.light();
            this.detectQRCode(video);

        } catch (error) {
            console.error('[Scanner] Erreur caméra:', error);
            HapticFeedback.error();
            
            let errorMessage = '❌ Impossible d\'accéder à la caméra';
            if (error.name === 'NotAllowedError') {
                errorMessage = '❌ Permission caméra refusée. Autorisez l\'accès dans les paramètres.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = '❌ Aucune caméra trouvée';
            } else if (error.name === 'NotReadableError') {
                errorMessage = '❌ Caméra déjà utilisée par une autre application';
            }
            
            UI.showToast(errorMessage, 'error');
        }
    }

    static detectQRCode(video) {
        if (!QRDecoder.hasJsQR()) {
            console.error('[Scanner] jsQR n\'est pas chargé');
            HapticFeedback.error();
            UI.showToast('❌ Erreur: Bibliothèque QR code non chargée', 'error');
            return;
        }

        this._ensureCanvas();
        clearTimeout(this._loopTimer);

        const dw = CONFIG.SCANNER.qr.decodeWidth;
        const dh = CONFIG.SCANNER.qr.decodeHeight;

        const tick = () => {
            if (!AppState.scannerActive) return;

            if (AppState.qrProcessing) {
                this._loopTimer = setTimeout(tick, CONFIG.SCANNER.qr.decodeIntervalMs);
                return;
            }

            if (video.readyState >= video.HAVE_CURRENT_DATA && video.videoWidth > 0) {
                const vw = video.videoWidth;
                const vh = video.videoHeight;
                let decodedText = null;

                // Passe 1 : frame complète downscalée
                this._ctx.drawImage(video, 0, 0, dw, dh);
                const fullData = this._ctx.getImageData(0, 0, dw, dh);
                decodedText = QRDecoder.decodeImageData(fullData);

                // Passe 2 : crop central 60% (zoom sur le centre, meilleur pour petits QR)
                if (!decodedText) {
                    const r2 = 0.6;
                    const sw2 = vw * r2, sh2 = vh * r2;
                    this._ctx.drawImage(video, (vw - sw2) / 2, (vh - sh2) / 2, sw2, sh2, 0, 0, dw, dh);
                    decodedText = QRDecoder.decodeImageData(this._ctx.getImageData(0, 0, dw, dh));
                }

                if (decodedText) {
                    console.log('[Scanner] QR Code détecté:', decodedText);
                    this.handleQRCodeDetected(decodedText);
                    return;
                }
            }

            this._loopTimer = setTimeout(tick, CONFIG.SCANNER.qr.decodeIntervalMs);
        };

        tick();
    }

    static _ensureCanvas() {
        if (!this._canvas || !this._ctx) {
            this._canvas = document.createElement('canvas');
            this._canvas.width = CONFIG.SCANNER.qr.decodeWidth;
            this._canvas.height = CONFIG.SCANNER.qr.decodeHeight;
            this._ctx = this._canvas.getContext('2d', { willReadFrequently: true });
        }
    }

    static _setupTorchToggle() {
        const btn = document.getElementById('emp-torch-toggle');
        const track = this._videoTrack;
        AppState.empTorchEnabled = false;

        if (!btn || !track || typeof track.getCapabilities !== 'function') {
            if (btn) btn.classList.add('hidden');
            return;
        }

        const caps = track.getCapabilities();
        if (!caps || !caps.torch) {
            btn.classList.add('hidden');
            return;
        }

        btn.classList.remove('hidden');
        this._updateTorchButton(btn, false);
        btn.onclick = () => this._toggleTorch(btn);
    }

    static async _toggleTorch(btn) {
        const track = this._videoTrack;
        if (!track) return;

        const next = !AppState.empTorchEnabled;
        try {
            await track.applyConstraints({ advanced: [{ torch: next }] });
            AppState.empTorchEnabled = next;
            this._updateTorchButton(btn, next);
        } catch (_) {
            AppState.empTorchEnabled = false;
            this._updateTorchButton(btn, false);
            UI.showToast('⚠️ Torche non supportée sur cet appareil', 'warning');
        }
    }

    static _updateTorchButton(btn, enabled) {
        btn.textContent = enabled ? '🔦 Torche on' : '🔦 Torche off';
        btn.classList.toggle('bg-emerald-600/90', enabled);
        btn.classList.toggle('bg-black/60', !enabled);
    }

    static async handleQRCodeDetected(data) {
        const now = Date.now();
        if (AppState.qrProcessing || (now - AppState.qrLastDetectedAt) < CONFIG.SCANNER.qr.detectCooldownMs) {
            return;
        }
        AppState.qrProcessing = true;
        AppState.qrLastDetectedAt = now;

        console.log('[Scanner] QR Code détecté:', data);
        
        let idEmplacement = null;

        // Format 1: EMP-{id} (format standard)
        const matchEmp = data.match(/^EMP-(\d+)$/);
        if (matchEmp) {
            idEmplacement = parseInt(matchEmp[1], 10);
            console.log('[Scanner] Format EMP-{id} détecté, ID:', idEmplacement);
        }

        // Format 2: JSON {"type":"...","id":...,"code":"..."}
        if (!idEmplacement) {
            try {
                const json = JSON.parse(data);
                if (json && json.id) {
                    if (json.type === 'emplacement') {
                        idEmplacement = parseInt(json.id, 10);
                        console.log('[Scanner] Format JSON emplacement détecté, ID:', idEmplacement);
                    } else if (json.type === 'localisation') {
                        console.log('[Scanner] Format JSON localisation détecté, ID:', json.id);
                        HapticFeedback.warning();
                        UI.showToast(`⚠️ Ceci est un QR de localisation, pas d'emplacement. Utilisez la PWA v1 ou générez des QR codes d'emplacements.`, 'warning');
                        return;
                    } else {
                        idEmplacement = parseInt(json.id, 10);
                        console.log('[Scanner] Format JSON type "' + json.type + '" détecté, tentative avec ID:', idEmplacement);
                    }
                }
            } catch (e) {
                // Pas du JSON, continuer
            }
        }

        // Format 3: Nombre seul (juste l'ID)
        if (!idEmplacement && /^\d+$/.test(data.trim())) {
            idEmplacement = parseInt(data.trim(), 10);
            console.log('[Scanner] Format nombre seul détecté, ID:', idEmplacement);
        }

        if (!idEmplacement) {
            console.warn('[Scanner] Format QR Code non reconnu:', data);
            HapticFeedback.warning();
            UI.showToast(`⚠️ QR Code non reconnu. Formats acceptés: EMP-{id} ou JSON avec id.`, 'warning');
            AppState.qrProcessing = false;
            return;
        }

        console.log('[Scanner] ID Emplacement extrait:', idEmplacement);
        
        this.stopScanner();
        
        HapticFeedback.success();
        UI.showToast('🔍 Chargement de l\'emplacement...', 'info');

        try {
            const response = await API.request(`/emplacements/${idEmplacement}/biens`);
            console.log('[Scanner] Réponse API:', response);
            
            const dejaScannesSet = new Set(
                (response.biens_deja_scannes || []).map(scan => normalizeNumOrdre(scan.num_ordre))
            );

            AppState.currentEmplacement = response.emplacement;
            // En réouverture, ne garder que les biens restants à scanner
            AppState.biensAttendus = (response.biens || [])
                .map(bien => ({
                    ...bien,
                    num_ordre: normalizeNumOrdre(bien.num_ordre)
                }))
                .filter(bien => bien.num_ordre && !dejaScannesSet.has(bien.num_ordre));
            AppState.biensScannés = (response.biens_deja_scannes || []).map(scan => ({
                num_ordre: normalizeNumOrdre(scan.num_ordre),
                etat_id: null, // Historique existant sans correspondance fiable idEtat
                photo: null,
                designation: null,
                categorie: null,
                statut: 'present',
                emplacement_initial: null,
                is_preloaded_expected: true
            }));
            rebuildBiensIndexes();

            HapticFeedback.medium();
            UI.showEmplacementView();

            const invLoc = response.inventaire_localisation;
            if (invLoc?.reopened) {
                UI.showToast(`🔄 Emplacement réouvert: ${invLoc.biens_restants} bien(s) restant(s) à scanner`, 'info');
            } else if (AppState.biensScannés.length > 0) {
                UI.showToast(`ℹ️ ${AppState.biensScannés.length} bien(s) déjà scanné(s) chargés`, 'info');
            }

        } catch (error) {
            console.error('[Scanner] Erreur API:', error);
            HapticFeedback.error();
            UI.showToast('❌ Erreur: ' + error.message, 'error');
        } finally {
            AppState.qrProcessing = false;
        }
    }

    static stopScanner() {
        AppState.scannerActive = false;
        clearTimeout(this._loopTimer);
        this._loopTimer = null;
        AppState.empTorchEnabled = false;
        this._videoTrack = null;
        const video = document.getElementById('qr-video');
        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
        }
    }
}

// ===========================================
// QR SCANNER BIENS - QR code (jsQR + BarcodeDetector)
// ===========================================

class BarcodeScannerManager {
    static async startBarcodeScanner() {
        const container = document.getElementById('barcode-scanner-container');
        this.stopBarcodeScanner();
        container.innerHTML = `
            <video id="bien-qr-video" class="w-full h-full object-cover" autoplay playsinline muted></video>
            <button id="bien-torch-toggle" class="hidden absolute top-2 right-2 z-20 px-3 py-1.5 rounded-full text-xs font-semibold bg-black/60 text-white backdrop-blur-sm">
                🔦 Torche off
            </button>
            <div class="scan-overlay">
                <div class="scan-reticle">
                    <span class="scan-corner tl"></span>
                    <span class="scan-corner tr"></span>
                    <span class="scan-corner bl"></span>
                    <span class="scan-corner br"></span>
                </div>
                <span class="scan-overlay-hint">Placez le QR dans le cadre</span>
            </div>
        `;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: CONFIG.SCANNER.qr.width, max: 1280 },
                    height: { ideal: CONFIG.SCANNER.qr.height, max: 720 },
                    aspectRatio: { ideal: 16 / 9 },
                    frameRate: { ideal: 24, max: 30 }
                },
                audio: false
            });

            const video = document.getElementById('bien-qr-video');
            video.srcObject = stream;
            this._videoTrack = stream.getVideoTracks()[0] || null;
            this._setupTorchToggle();

            await new Promise(resolve => {
                video.addEventListener('loadedmetadata', resolve, { once: true });
            });
            await video.play();

            console.log('[QR Biens] Caméra prête:', video.videoWidth, 'x', video.videoHeight);
            AppState.barcodeScannerActive = true;
            AppState.barcodeNativeLoopActive = true;
            HapticFeedback.light();
            this.startQrDetectionLoop(video);
        } catch (error) {
            console.error('[QR Biens] Erreur caméra:', error);
            HapticFeedback.error();
            UI.showToast('❌ Impossible d\'accéder à la caméra pour scanner les QR', 'error');
        }
    }

    static startQrDetectionLoop(video) {
        if (!QRDecoder.hasJsQR()) {
            console.error('[QR Biens] jsQR n\'est pas chargé');
            HapticFeedback.error();
            UI.showToast('❌ Erreur: Bibliothèque QR code non chargée', 'error');
            return;
        }

        this._setupNativeDetector();
        this._ensureCanvas();
        clearTimeout(this._loopTimer);

        const dw = this._canvas.width;
        const dh = this._canvas.height;

        const tick = () => {
            if (!AppState.barcodeNativeLoopActive || !AppState.barcodeScannerActive) return;

            if (AppState.barcodeModalOpen || AppState.barcodeProcessing) {
                this._loopTimer = setTimeout(tick, CONFIG.SCANNER.qr.decodeIntervalMs);
                return;
            }

            if (video.readyState >= video.HAVE_CURRENT_DATA && video.videoWidth > 0) {
                // Passe 1 : frame complète (QR peut être n'importe où)
                this._drawDecodeFrame(video, null);

                if (this._nativeDetector && !this._nativeDetectPending) {
                    this._nativeDetectPending = true;
                    this._nativeDetector.detect(this._canvas)
                        .then(results => {
                            if (!results || results.length === 0) return;
                            const raw = String(results[0].rawValue || '').trim();
                            this.consumeDetectedValue(raw);
                        })
                        .catch(() => {})
                        .finally(() => { this._nativeDetectPending = false; });
                }

                let decodedText = null;
                try {
                    decodedText = QRDecoder.decodeImageData(this._ctx.getImageData(0, 0, dw, dh));
                } catch (e) {
                    console.error('[QR Biens] jsQR erreur passe 1:', e);
                }

                // Passe 2 : crop central 60% (zoom sur petit QR centré)
                if (!decodedText) {
                    try {
                        this._drawDecodeFrame(video, 0.6);
                        decodedText = QRDecoder.decodeImageData(this._ctx.getImageData(0, 0, dw, dh));
                    } catch (e) {
                        console.error('[QR Biens] jsQR erreur passe 2:', e);
                    }
                }

                if (decodedText) {
                    const rawValue = String(decodedText).trim();
                    this.consumeDetectedValue(rawValue);
                }
            }

            this._loopTimer = setTimeout(tick, CONFIG.SCANNER.qr.decodeIntervalMs);
        };

        tick();
    }

    static consumeDetectedValue(rawValue) {
        if (!rawValue || AppState.barcodeModalOpen || AppState.barcodeProcessing) return false;
        const now = Date.now();
        if (
            AppState.barcodeLastCode === rawValue &&
            (now - AppState.barcodeLastDetectedAt) < CONFIG.SCANNER.barcode.detectCooldownMs
        ) {
            return false;
        }
        AppState.barcodeLastCode = rawValue;
        AppState.barcodeLastDetectedAt = now;
        this.handleBarcodeDetected(rawValue);
        return true;
    }

    static _ensureCanvas() {
        if (!this._canvas || !this._ctx) {
            this._canvas = document.createElement('canvas');
            this._canvas.width = CONFIG.SCANNER.qr.decodeWidth;
            this._canvas.height = CONFIG.SCANNER.qr.decodeHeight;
            this._ctx = this._canvas.getContext('2d', { willReadFrequently: true });
        }
    }

    static _drawDecodeFrame(video, cropRatio) {
        const srcW = video.videoWidth || 0;
        const srcH = video.videoHeight || 0;
        if (srcW <= 0 || srcH <= 0) return;

        if (!cropRatio || cropRatio >= 1) {
            this._ctx.drawImage(video, 0, 0, this._canvas.width, this._canvas.height);
            return;
        }

        const sw = srcW * cropRatio;
        const sh = srcH * cropRatio;
        const sx = (srcW - sw) / 2;
        const sy = (srcH - sh) / 2;
        this._ctx.drawImage(video, sx, sy, sw, sh, 0, 0, this._canvas.width, this._canvas.height);
    }

    static _setupNativeDetector() {
        this._nativeDetector = null;
        this._nativeDetectPending = false;
        if (typeof window.BarcodeDetector === 'undefined') return;
        try {
            this._nativeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
        } catch (_) {
            this._nativeDetector = null;
        }
    }

    static _setupTorchToggle() {
        const btn = document.getElementById('bien-torch-toggle');
        const track = this._videoTrack;
        AppState.bienTorchEnabled = false;

        if (!btn || !track || typeof track.getCapabilities !== 'function') {
            if (btn) btn.classList.add('hidden');
            return;
        }

        const caps = track.getCapabilities();
        if (!caps || !caps.torch) {
            btn.classList.add('hidden');
            return;
        }

        btn.classList.remove('hidden');
        this._updateTorchButton(btn, false);
        btn.onclick = () => this._toggleTorch(btn);
    }

    static async _toggleTorch(btn) {
        const track = this._videoTrack;
        if (!track) return;

        const next = !AppState.bienTorchEnabled;
        try {
            await track.applyConstraints({ advanced: [{ torch: next }] });
            AppState.bienTorchEnabled = next;
            this._updateTorchButton(btn, next);
        } catch (_) {
            AppState.bienTorchEnabled = false;
            this._updateTorchButton(btn, false);
            UI.showToast('⚠️ Torche non supportée sur cet appareil', 'warning');
        }
    }

    static _updateTorchButton(btn, enabled) {
        btn.textContent = enabled ? '🔦 Torche on' : '🔦 Torche off';
        btn.classList.toggle('bg-emerald-600/90', enabled);
        btn.classList.toggle('bg-black/60', !enabled);
    }

    static async handleBarcodeDetected(codeBarre) {
        if (AppState.barcodeProcessing) return;
        AppState.barcodeProcessing = true;

        console.log('[Barcode] Détecté:', codeBarre);

        const numOrdre = extractNumOrdreFromBarcode(codeBarre);
        if (!numOrdre) {
            HapticFeedback.warning();
            const now = Date.now();
            // Eviter de saturer l'UI avec des toasts d'erreur sur faux positifs
            if ((now - AppState.barcodeLastInvalidAt) > 1500) {
                UI.showToast('⚠️ Code-barres invalide ou format non reconnu', 'warning');
                AppState.barcodeLastInvalidAt = now;
            }
            AppState.barcodeProcessing = false;
            return;
        }

        // Vérifier si déjà scanné
        if (AppState.biensScannesIndex.has(numOrdre)) {
            HapticFeedback.warning();
            UI.showToast('⚠️ Déjà scanné', 'warning');
            AppState.barcodeProcessing = false;
            return;
        }

        // Chercher dans les biens attendus de cet emplacement
        const bien = AppState.biensAttendusIndex.get(numOrdre);

        if (bien) {
            HapticFeedback.light();
            UI.showModalEtatBien(bien);
        } else {
            // Bien pas dans cet emplacement → accepter comme "non attendu"
            // La détection de l'emplacement d'origine se fera au clic "Terminer" côté serveur
            console.log('[Barcode] Bien non attendu dans cet emplacement, N°', numOrdre);
            HapticFeedback.warning();
            UI.showToast(`⚠️ Bien N°${numOrdre} non attendu dans cet emplacement`, 'warning');
            
            const bienNonAttendu = {
                num_ordre: numOrdre,
                designation: `Bien non attendu`,
                categorie: '-',
                etat: '-',
                statut: 'non_attendu',
                emplacement_initial: null
            };
            UI.showModalEtatBien(bienNonAttendu);
        }

        // Le reset est géré à la fermeture/confirmation de la modale
    }

    static stopBarcodeScanner() {
        AppState.barcodeScannerActive = false;
        AppState.barcodeNativeLoopActive = false;
        clearTimeout(this._loopTimer);
        this._loopTimer = null;
        this._nativeDetector = null;
        this._nativeDetectPending = false;
        AppState.bienTorchEnabled = false;
        this._videoTrack = null;
        const video = document.getElementById('bien-qr-video');
        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
        }
        console.log('[QR Biens] Scanner arrêté');
    }
}

// ===========================================
// UI MANAGER
// ===========================================

class UI {
    static async loadEtats() {
        if (!AppState.token) return;
        try {
            const response = await API.request('/etats');
            AppState.etats = response.etats || [];
        } catch (error) {
            console.warn('[UI] Erreur chargement états:', error);
            AppState.etats = [];
        }
    }

    static showView(viewName) {
        document.querySelectorAll('[id^="view-"]').forEach(view => {
            view.classList.add('hidden');
        });

        const view = document.getElementById(`view-${viewName}`);
        if (view) {
            view.classList.remove('hidden');
        }

        // Cacher le header sur login
        const header = document.getElementById('app-header');
        if (viewName === 'login') {
            header.classList.add('hidden');
        } else {
            header.classList.remove('hidden');
        }

        const startScannerBtn = document.getElementById('start-scanner-btn');

        // Gestion lifecycle caméra scanner emplacement
        if (viewName === 'scanner') {
            if (startScannerBtn) startScannerBtn.style.display = 'none';
            this.setEmplacementInputMode(AppState.emplacementInputMode);
        } else {
            ScannerManager.stopScanner();
            if (startScannerBtn) startScannerBtn.style.display = '';
        }

        // Fermer le scanner biens dès qu'on quitte la vue emplacement
        if (viewName !== 'emplacement-biens') {
            BarcodeScannerManager.stopBarcodeScanner();
        }
    }

    static updateUserInfo() {
        if (AppState.user) {
            document.getElementById('user-name').textContent = AppState.user.name || 'Agent';
            document.getElementById('menu-user-name').textContent = AppState.user.name || 'Agent';
        }
    }

    static showEmplacementView() {
        this.showView('emplacement-biens');

        const emp = AppState.currentEmplacement;
        document.getElementById('emplacement-nom').textContent = emp.nom;
        
        let details = emp.code;
        if (emp.localisation) details += ` • ${emp.localisation.nom}`;
        if (emp.affectation) details += ` • ${emp.affectation.nom}`;
        document.getElementById('emplacement-details').textContent = details;

        document.getElementById('biens-count').textContent = `${AppState.biensAttendus.length} bien(s)`;

        this.updateBiensList();
        this.updateProgress();
        this.setBienInputMode('scan');
    }

    static setEmplacementInputMode(mode) {
        const scanBtn = document.getElementById('tab-emp-scan');
        const manualBtn = document.getElementById('tab-emp-manual');
        const scanPanel = document.getElementById('emp-mode-scan');
        const manualPanel = document.getElementById('emp-mode-manual');
        const manualInput = document.getElementById('manual-emp-input');

        AppState.emplacementInputMode = mode === 'manual' ? 'manual' : 'scan';

        if (AppState.emplacementInputMode === 'manual') {
            scanPanel.classList.add('hidden');
            manualPanel.classList.remove('hidden');
            scanBtn.classList.remove('bg-indigo-600', 'text-white');
            scanBtn.classList.add('bg-white', 'text-gray-700');
            manualBtn.classList.remove('bg-white', 'text-gray-700');
            manualBtn.classList.add('bg-indigo-600', 'text-white');
            ScannerManager.stopScanner();
            setTimeout(() => manualInput?.focus(), 50);
            return;
        }

        manualPanel.classList.add('hidden');
        scanPanel.classList.remove('hidden');
        manualBtn.classList.remove('bg-indigo-600', 'text-white');
        manualBtn.classList.add('bg-white', 'text-gray-700');
        scanBtn.classList.remove('bg-white', 'text-gray-700');
        scanBtn.classList.add('bg-indigo-600', 'text-white');

        const scannerViewVisible = !document.getElementById('view-scanner')?.classList.contains('hidden');
        if (scannerViewVisible) {
            ScannerManager.startQRScanner();
        }
    }

    static submitManualEmplacementCode() {
        const input = document.getElementById('manual-emp-input');
        const raw = String(input?.value || '').trim();
        if (!raw) {
            HapticFeedback.warning();
            this.showToast('⚠️ Veuillez saisir un code/ID emplacement', 'warning');
            input?.focus();
            return;
        }
        ScannerManager.handleQRCodeDetected(raw);
        if (input) {
            input.value = '';
            input.focus();
        }
    }

    static setBienInputMode(mode) {
        const scanBtn = document.getElementById('tab-bien-scan');
        const manualBtn = document.getElementById('tab-bien-manual');
        const scanPanel = document.getElementById('bien-mode-scan');
        const manualPanel = document.getElementById('bien-mode-manual');
        const manualInput = document.getElementById('manual-num-ordre-input');

        AppState.barcodeInputMode = mode === 'manual' ? 'manual' : 'scan';

        if (AppState.barcodeInputMode === 'manual') {
            scanPanel.classList.add('hidden');
            manualPanel.classList.remove('hidden');
            scanBtn.classList.remove('bg-indigo-600', 'text-white');
            scanBtn.classList.add('bg-white', 'text-gray-700');
            manualBtn.classList.remove('bg-white', 'text-gray-700');
            manualBtn.classList.add('bg-indigo-600', 'text-white');

            BarcodeScannerManager.stopBarcodeScanner();
            setTimeout(() => manualInput?.focus(), 50);
            return;
        }

        manualPanel.classList.add('hidden');
        scanPanel.classList.remove('hidden');
        manualBtn.classList.remove('bg-indigo-600', 'text-white');
        manualBtn.classList.add('bg-white', 'text-gray-700');
        scanBtn.classList.remove('bg-white', 'text-gray-700');
        scanBtn.classList.add('bg-indigo-600', 'text-white');

        if (AppState.currentEmplacement) {
            BarcodeScannerManager.startBarcodeScanner();
        }
    }

    static submitManualNumOrdre() {
        const input = document.getElementById('manual-num-ordre-input');
        const raw = String(input?.value || '').trim();
        const numOrdre = normalizeNumOrdre(raw);

        if (!numOrdre) {
            HapticFeedback.warning();
            this.showToast('⚠️ Veuillez saisir un numéro d\'ordre valide', 'warning');
            input?.focus();
            return;
        }

        if (AppState.barcodeProcessing || AppState.barcodeModalOpen) {
            this.showToast('⏳ Traitement en cours, veuillez patienter...', 'info');
            return;
        }

        BarcodeScannerManager.handleBarcodeDetected(String(numOrdre));
        input.value = '';
        input.focus();
    }

    static updateBiensList() {
        const list = document.getElementById('biens-list');
        list.innerHTML = '';
        const scansByNum = AppState.biensScannesIndex;

        // Afficher les biens attendus
        AppState.biensAttendus.forEach(bien => {
            const scanData = scansByNum.get(bien.num_ordre);
            const isScanned = !!scanData;
            const etatObj = scanData && scanData.etat_id ? AppState.etats.find(e => e.id === scanData.etat_id) : null;
            const isDefectueux = etatObj && etatObj.require_photo;
            const etatLabel = etatObj ? etatObj.label : '';
            
            const item = document.createElement('div');
            item.className = `p-3 ${isScanned ? (isDefectueux ? 'bg-amber-50' : 'bg-green-50') : 'bg-white'}`;
            item.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 text-sm">${bien.designation}</p>
                        <p class="text-xs text-gray-600">N° ${bien.num_ordre} • ${bien.categorie}${isScanned ? ' • ' + etatLabel : ''}</p>
                    </div>
                    <div class="ml-3">
                        ${isScanned ? 
                            '<svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' :
                            '<svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/></svg>'
                        }
                    </div>
                </div>
            `;
            list.appendChild(item);
        });

        // Afficher uniquement les biens réellement non attendus/déplacés
        const biensNonAttendus = AppState.biensScannés.filter(
            s => s.statut === 'non_attendu' || s.statut === 'deplace'
        );
        if (biensNonAttendus.length > 0) {
            const separator = document.createElement('div');
            separator.className = 'p-2 bg-amber-100 text-center';
            separator.innerHTML = '<p class="text-xs font-semibold text-amber-700">⚠️ Biens non attendus dans cet emplacement</p>';
            list.appendChild(separator);

            biensNonAttendus.forEach(scanData => {
                const etatObj = scanData.etat_id ? AppState.etats.find(e => e.id === scanData.etat_id) : null;
                const etatLabel = etatObj ? etatObj.label : '';

                const item = document.createElement('div');
                item.className = 'p-3 bg-amber-50';
                item.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-amber-900 text-sm">Bien N°${scanData.num_ordre}</p>
                            <p class="text-xs text-amber-700">Non attendu${etatLabel ? ' • ' + etatLabel : ''}</p>
                        </div>
                        <div class="ml-3">
                            <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
                `;
                list.appendChild(item);
            });
        }
    }

    static updateProgress() {
        const total = AppState.biensAttendus.length;
        const scannedAttendus = Array.from(AppState.biensScannesIndex.keys())
            .filter(num => AppState.biensAttendusIndex.has(num)).length;
        const scannedDeplaces = AppState.biensScannés.filter(
            s => s.statut === 'non_attendu' || s.statut === 'deplace'
        ).length;
        const percent = total > 0 ? Math.round((scannedAttendus / total) * 100) : 0;

        let progressText = `${scannedAttendus}/${total} biens scannés`;
        if (scannedDeplaces > 0) {
            progressText += ` + ${scannedDeplaces} non attendu(s)`;
        }
        document.getElementById('progress-text').textContent = progressText;
        document.getElementById('progress-percent').textContent = `${percent}%`;
        document.getElementById('progress-bar').style.width = `${percent}%`;
    }

    static async showResultatsView() {
        BarcodeScannerManager.stopBarcodeScanner();

        UI.showToast('📊 Calcul des écarts...', 'info');

        try {
            // Format: [{ num_ordre, etat_id, photo? }] - utilise table etat
            const biensPayload = AppState.biensScannés.map(b => ({
                num_ordre: b.num_ordre,
                etat_id: b.etat_id || null,
                photo: b.photo || null
            }));

            const response = await API.request(
                `/emplacements/${AppState.currentEmplacement.id}/terminer`,
                {
                    method: 'POST',
                    body: JSON.stringify({
                        biens_scannes: biensPayload
                    })
                }
            );

            this.showView('resultats');
            this.displayResultats(response);

        } catch (error) {
            UI.showToast('❌ Erreur: ' + error.message, 'error');
        }
    }

    static showModalEtatBien(bien) {
        AppState.modalBienEnCours = bien;
        AppState.barcodeModalOpen = true;
        const modal = document.getElementById('modal-etat-bien');
        document.getElementById('modal-etat-designation').textContent = `${bien.designation} (N° ${bien.num_ordre})`;
        
        // Boutons dynamiques depuis API /etats
        const container = document.getElementById('modal-etat-buttons');
        container.innerHTML = '';
        if (AppState.etats.length > 0) {
            AppState.etats.forEach(etat => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'modal-etat-btn touch-target py-3 px-4 rounded-xl border-2 border-gray-200 text-sm font-medium text-gray-700 hover:border-indigo-500 hover:bg-indigo-50 transition';
                btn.dataset.etatId = etat.id;
                btn.dataset.requirePhoto = etat.require_photo ? '1' : '0';
                btn.textContent = etat.label;
                container.appendChild(btn);
            });
        } else {
            // Fallback si états non chargés (envoie null -> API utilise 'bon')
            const fallback = [{ id: null, label: 'Bon', require_photo: false }];
            fallback.forEach(etat => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'modal-etat-btn touch-target py-3 px-4 rounded-xl border-2 border-gray-200 text-sm font-medium text-gray-700 hover:border-indigo-500 hover:bg-indigo-50 transition';
                btn.dataset.etatId = etat.id ?? '';
                btn.dataset.requirePhoto = etat.require_photo ? '1' : '0';
                btn.textContent = etat.label;
                container.appendChild(btn);
            });
        }
        
        // Reset modal state
        document.getElementById('modal-etat-photo-section').classList.add('hidden');
        document.getElementById('modal-etat-photo-input').value = '';
        document.getElementById('modal-etat-photo-preview').classList.add('hidden');
        document.getElementById('modal-etat-confirmer').disabled = true;
        
        modal.classList.remove('hidden');
    }

    static hideModalEtatBien() {
        AppState.modalBienEnCours = null;
        AppState.barcodeModalOpen = false;
        AppState.barcodeProcessing = false;
        document.getElementById('modal-etat-bien').classList.add('hidden');
    }

    static confirmModalEtatBien(etatId, photoBase64) {
        if (!AppState.modalBienEnCours) return;
        
        const bien = AppState.modalBienEnCours;
        AppState.biensScannés.push({
            num_ordre: bien.num_ordre,
            etat_id: etatId ? parseInt(etatId, 10) : null,
            photo: photoBase64 || null,
            designation: bien.designation || null,
            categorie: bien.categorie || null,
            statut: bien.statut || 'present',
            emplacement_initial: bien.emplacement_initial || null
        });
        rebuildBiensIndexes();
        
        const isNonAttendu = bien.statut === 'non_attendu' || bien.statut === 'deplace';
        HapticFeedback.success();
        UI.showToast(`${isNonAttendu ? '⚠️' : '✅'} ${bien.designation} (N°${bien.num_ordre})`, 'success');
        UI.updateBiensList();
        UI.updateProgress();
        UI.hideModalEtatBien();
    }

    static displayResultats(data) {
        const stats = data.statistiques;

        document.getElementById('stat-scannes').textContent = stats.total_scanne;
        document.getElementById('stat-manquants').textContent = stats.total_manquant;
        document.getElementById('stat-deplaces').textContent = stats.total_en_trop || 0;
        
        document.getElementById('conformite-bar').style.width = `${stats.taux_conformite}%`;
        document.getElementById('conformite-text').textContent = `${stats.taux_conformite}%`;

        // Biens manquants
        if (data.biens_manquants && data.biens_manquants.length > 0) {
            document.getElementById('section-manquants').classList.remove('hidden');
            const listManquants = document.getElementById('list-manquants');
            listManquants.innerHTML = '';

            data.biens_manquants.forEach(bien => {
                const item = document.createElement('div');
                item.className = 'bg-red-50 border-l-4 border-red-500 p-3 rounded';
                item.innerHTML = `
                    <p class="font-medium text-red-800">${bien.designation}</p>
                    <p class="text-sm text-red-600">N° ${bien.num_ordre} • ${bien.categorie}</p>
                `;
                listManquants.appendChild(item);
            });
        } else {
            document.getElementById('section-manquants').classList.add('hidden');
        }

        // Biens déplacés (scannés ici mais enregistrés dans un autre emplacement)
        if (data.biens_en_trop && data.biens_en_trop.length > 0) {
            document.getElementById('section-deplaces').classList.remove('hidden');
            const listDeplaces = document.getElementById('list-deplaces');
            listDeplaces.innerHTML = '';

            data.biens_en_trop.forEach(bien => {
                const empInit = bien.emplacement_initial;
                const origin = empInit ? `${empInit.nom || ''} ${empInit.affectation ? '• ' + empInit.affectation : ''}` : 'Inconnu';
                const item = document.createElement('div');
                item.className = 'bg-amber-50 border-l-4 border-amber-500 p-3 rounded';
                item.innerHTML = `
                    <p class="font-medium text-amber-800">${bien.designation}</p>
                    <p class="text-sm text-amber-700">N° ${bien.num_ordre} • ${bien.categorie}</p>
                    <p class="text-xs text-amber-600 mt-1">📍 Emplacement d'origine: ${origin}</p>
                `;
                listDeplaces.appendChild(item);
            });
        } else {
            document.getElementById('section-deplaces').classList.add('hidden');
        }

        // Biens introuvables (code-barres ne correspondant à aucun bien en BD)
        if (data.biens_introuvables && data.biens_introuvables.length > 0) {
            const sectionDeplaces = document.getElementById('section-deplaces');
            sectionDeplaces.classList.remove('hidden');
            const listDeplaces = document.getElementById('list-deplaces');

            const separatorIntrouvable = document.createElement('div');
            separatorIntrouvable.className = 'bg-gray-100 p-2 rounded text-center mt-3';
            separatorIntrouvable.innerHTML = '<p class="text-xs font-semibold text-gray-600">❓ Biens introuvables en base de données</p>';
            listDeplaces.appendChild(separatorIntrouvable);

            data.biens_introuvables.forEach(numOrdre => {
                const item = document.createElement('div');
                item.className = 'bg-gray-50 border-l-4 border-gray-400 p-3 rounded';
                item.innerHTML = `
                    <p class="font-medium text-gray-800">Bien N°${numOrdre}</p>
                    <p class="text-sm text-gray-600">Non trouvé dans la base de données</p>
                `;
                listDeplaces.appendChild(item);
            });
        }
    }

    static showToast(message, type = 'info') {
        const now = Date.now();
        const toastKey = `${type}:${message}`;
        if (AppState.lastToastKey === toastKey && (now - AppState.lastToastAt) < 900) {
            return;
        }
        AppState.lastToastKey = toastKey;
        AppState.lastToastAt = now;

        const container = document.getElementById('toast-container');
        
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const toast = document.createElement('div');
        toast.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// ===========================================
// EVENT LISTENERS
// ===========================================

// Login
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('login-users').value;
    const password = document.getElementById('login-mdp').value;
    const btn = document.getElementById('login-btn');
    const btnText = document.getElementById('login-btn-text');
    const spinner = document.getElementById('login-spinner');
    const errorDiv = document.getElementById('login-error');

    btn.disabled = true;
    btnText.textContent = 'Connexion...';
    spinner.classList.remove('hidden');
    errorDiv.classList.add('hidden');

    try {
        await AuthManager.login(username, password);
    } catch (error) {
        errorDiv.classList.remove('hidden');
        errorDiv.querySelector('p').textContent = error.message || 'Erreur de connexion';
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Se connecter';
        spinner.classList.add('hidden');
    }
});

// Menu
document.getElementById('menu-btn').addEventListener('click', () => {
    document.getElementById('menu-drawer').classList.remove('hidden');
});

document.getElementById('close-menu').addEventListener('click', () => {
    document.getElementById('menu-drawer').classList.add('hidden');
});

document.getElementById('menu-overlay').addEventListener('click', () => {
    document.getElementById('menu-drawer').classList.add('hidden');
});

document.getElementById('nav-logout').addEventListener('click', () => {
    AuthManager.logout();
    document.getElementById('menu-drawer').classList.add('hidden');
});

// Scanner
document.getElementById('start-scanner-btn').addEventListener('click', () => {
    ScannerManager.startQRScanner();
    document.getElementById('start-scanner-btn').style.display = 'none';
});

// Onglets scan / saisie manuelle emplacement
document.getElementById('tab-emp-scan').addEventListener('click', () => {
    UI.setEmplacementInputMode('scan');
});

document.getElementById('tab-emp-manual').addEventListener('click', () => {
    UI.setEmplacementInputMode('manual');
});

document.getElementById('manual-emp-submit').addEventListener('click', () => {
    UI.submitManualEmplacementCode();
});

document.getElementById('manual-emp-input').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        UI.submitManualEmplacementCode();
    }
});

// Fermer les caméras quand la page est masquée/fermée
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState !== 'visible') {
        ScannerManager.stopScanner();
        BarcodeScannerManager.stopBarcodeScanner();
        return;
    }

    if (!AppState.token) return;

    const scannerViewVisible = !document.getElementById('view-scanner')?.classList.contains('hidden');
    const emplacementViewVisible = !document.getElementById('view-emplacement-biens')?.classList.contains('hidden');

    if (scannerViewVisible && AppState.emplacementInputMode !== 'manual') {
        ScannerManager.startQRScanner();
    } else if (emplacementViewVisible && AppState.barcodeInputMode !== 'manual') {
        BarcodeScannerManager.startBarcodeScanner();
    }
});

window.addEventListener('pagehide', () => {
    ScannerManager.stopScanner();
    BarcodeScannerManager.stopBarcodeScanner();
});

// Terminer scan emplacement
document.getElementById('btn-terminer-emplacement').addEventListener('click', () => {
    if (confirm('Terminer le scan de cet emplacement ?')) {
        UI.showResultatsView();
    }
});

// Nouveau scan
document.getElementById('btn-nouveau-scan').addEventListener('click', () => {
    AppState.currentEmplacement = null;
    AppState.biensAttendus = [];
    AppState.biensScannés = [];
    AppState.barcodeModalOpen = false;
    AppState.barcodeProcessing = false;
    AppState.barcodeInputMode = 'scan';
    AppState.emplacementInputMode = 'scan';
    rebuildBiensIndexes();
    UI.showView('scanner');
});

// Onglets scan / saisie manuelle des biens
document.getElementById('tab-bien-scan').addEventListener('click', () => {
    UI.setBienInputMode('scan');
});

document.getElementById('tab-bien-manual').addEventListener('click', () => {
    UI.setBienInputMode('manual');
});

document.getElementById('manual-num-ordre-submit').addEventListener('click', () => {
    UI.submitManualNumOrdre();
});

document.getElementById('manual-num-ordre-input').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        UI.submitManualNumOrdre();
    }
});

// Modal État du bien
let modalEtatSelectionne = null;
let modalPhotoBase64 = null;

document.getElementById('modal-etat-buttons').addEventListener('click', (e) => {
    const btn = e.target.closest('.modal-etat-btn');
    if (!btn) return;
    
    document.querySelectorAll('.modal-etat-btn').forEach(b => b.classList.remove('border-indigo-600', 'bg-indigo-50'));
    btn.classList.add('border-indigo-600', 'bg-indigo-50');
    modalEtatSelectionne = btn.dataset.etatId;
    const requirePhoto = btn.dataset.requirePhoto === '1';
    
    const photoSection = document.getElementById('modal-etat-photo-section');
    const confirmBtn = document.getElementById('modal-etat-confirmer');
    
    if (requirePhoto) {
        photoSection.classList.remove('hidden');
        confirmBtn.disabled = !modalPhotoBase64;
    } else {
        photoSection.classList.add('hidden');
        modalPhotoBase64 = null;
        confirmBtn.disabled = false;
    }
});

document.getElementById('modal-etat-photo-input').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (event) => {
        modalPhotoBase64 = event.target.result;
        document.getElementById('modal-etat-photo-img').src = modalPhotoBase64;
        document.getElementById('modal-etat-photo-preview').classList.remove('hidden');
        document.getElementById('modal-etat-confirmer').disabled = false;
    };
    reader.readAsDataURL(file);
});

document.getElementById('modal-etat-photo-retake').addEventListener('click', () => {
    modalPhotoBase64 = null;
    document.getElementById('modal-etat-photo-input').value = '';
    document.getElementById('modal-etat-photo-preview').classList.add('hidden');
    const btn = document.querySelector('.modal-etat-btn.border-indigo-600');
    const requirePhoto = btn && btn.dataset.requirePhoto === '1';
    document.getElementById('modal-etat-confirmer').disabled = requirePhoto;
});

document.getElementById('modal-etat-annuler').addEventListener('click', () => {
    UI.hideModalEtatBien();
    modalEtatSelectionne = null;
    modalPhotoBase64 = null;
});

document.getElementById('modal-etat-overlay').addEventListener('click', () => {
    UI.hideModalEtatBien();
    modalEtatSelectionne = null;
    modalPhotoBase64 = null;
});

document.getElementById('modal-etat-confirmer').addEventListener('click', () => {
    if (!modalEtatSelectionne) return;
    const btn = document.querySelector('.modal-etat-btn.border-indigo-600');
    const requirePhoto = btn && btn.dataset.requirePhoto === '1';
    if (requirePhoto && !modalPhotoBase64) {
        UI.showToast('📷 Veuillez prendre une photo du bien défectueux', 'warning');
        return;
    }
    UI.confirmModalEtatBien(modalEtatSelectionne, modalPhotoBase64);
    modalEtatSelectionne = null;
    modalPhotoBase64 = null;
});

// ===========================================
// INIT
// ===========================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('[App v2] Init...');
    
    if (!AuthManager.checkAuth()) {
        UI.showView('login');
    }
});
