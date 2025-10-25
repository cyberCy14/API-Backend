<div x-data="qrScannerSimple()" class="space-y-4">
    <button type="button" @click="toggleScanner()"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        <svg x-show="!isScanning" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
            </path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z">
            </path>
        </svg>
        <svg x-show="isScanning" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        <span x-text="isScanning ? 'Stop Camera' : 'Start Camera Scanner'"></span>
    </button>

    <!-- QR Reader Container with proper sizing -->
    <div x-show="isScanning" class="w-full">
        <div id="qr-reader" class="border-4 border-blue-500 rounded-lg overflow-hidden bg-black"></div>
    </div>

    <div x-show="message" x-transition class="p-4 rounded-lg text-sm font-medium" :class="{
             'bg-green-100 text-green-800 border border-green-300': messageType === 'success',
             'bg-blue-100 text-blue-800 border border-blue-300': messageType === 'info',
             'bg-red-100 text-red-800 border border-red-300': messageType === 'error'
         }" x-text="message">
    </div>

    <div x-show="!isScanning" class="text-sm text-gray-600 dark:text-gray-400">
        <p class="font-semibold mb-2">How to use:</p>
        <ol class="list-decimal list-inside space-y-1">
            <li>Click "Start Camera Scanner"</li>
            <li>Allow camera access when prompted</li>
            <li>Point camera at customer's QR code</li>
            <li>System will verify and auto-fill customer data</li>
        </ol>
        <p
            class="mt-3 text-xs bg-yellow-50 dark:bg-yellow-900 p-2 rounded border border-yellow-200 dark:border-yellow-700">
            <strong>⚠️ Security:</strong> Customer must exist in database. Mismatched ID/email will be rejected.
        </p>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
    #qr-reader {
        width: 100% !important;
        max-width: 600px !important;
        margin: 0 auto;
    }

    #qr-reader video {
        width: 100% !important;
        height: auto !important;
        display: block !important;
        border-radius: 0.5rem;
    }

    #qr-reader canvas {
        display: none !important;
    }

    #qr-reader__dashboard,
    #qr-reader__dashboard_section {
        display: none !important;
    }
</style>

<script>
    function qrScannerSimple() {
        return {
            isScanning: false,
            html5QrCode: null,
            message: '',
            messageType: 'info',
            scanInProgress: false,

            toggleScanner() {
                if (this.isScanning) {
                    this.stopScanning();
                } else {
                    this.startScanning();
                }
            },

            async startScanning() {
                try {
                    this.html5QrCode = new Html5Qrcode("qr-reader");

                    const cameras = await Html5Qrcode.getCameras();

                    if (cameras && cameras.length > 0) {
                        const cameraId = cameras.length > 1 ? cameras[cameras.length - 1].id : cameras[0].id;

                        await this.html5QrCode.start(
                            cameraId,
                            {
                                fps: 10,
                                qrbox: function (viewfinderWidth, viewfinderHeight) {
                                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                                    const qrboxSize = Math.floor(minEdge * 0.7);
                                    return {
                                        width: qrboxSize,
                                        height: qrboxSize
                                    };
                                },
                                aspectRatio: 1.777778,
                                videoConstraints: {
                                    width: { ideal: 1280 },
                                    height: { ideal: 720 }
                                }
                            },
                            (decodedText, decodedResult) => {
                                if (!this.scanInProgress) {
                                    this.onScanSuccess(decodedText);
                                }
                            },
                            (errorMessage) => {
                                // Continuous scanning errors - ignore
                            }
                        );

                        this.isScanning = true;
                        this.showMessage('📹 Camera active! Point at customer QR code...', 'info');
                    } else {
                        this.showMessage('No cameras found on this device', 'error');
                    }
                } catch (err) {
                    console.error('Scanner error:', err);
                    this.showMessage('Camera access denied or unavailable: ' + err.message, 'error');
                }
            },

            stopScanning() {
                if (this.html5QrCode && this.isScanning) {
                    this.html5QrCode.stop()
                        .then(() => {
                            this.html5QrCode.clear();
                            this.isScanning = false;
                            this.scanInProgress = false;
                            if (!this.message || this.messageType !== 'success') {
                                this.message = '';
                            }
                        })
                        .catch((err) => {
                            console.error('Stop error:', err);
                            this.isScanning = false;
                        });
                }
            },

            onScanSuccess(decodedText) {
                if (this.scanInProgress) return;
                this.scanInProgress = true;

                console.log('QR Code Scanned:', decodedText);

                this.stopScanning();

                this.showMessage('✅ QR Code detected! Verifying customer...', 'success');

                // Validate QR data format
                let isValid = false;
                try {
                    const parsed = JSON.parse(decodedText);
                    if (parsed.customer_id || parsed.customer_email || parsed.id || parsed.email) {
                        isValid = true;
                    }
                } catch (e) {
                    if (decodedText.includes('customer_id') || decodedText.includes('customer_email') ||
                        decodedText.includes('"id"') || decodedText.includes('"email"')) {
                        isValid = true;
                    }
                }

                if (!isValid) {
                    this.showMessage('❌ Invalid QR code format. Must contain customer identification', 'error');
                    this.scanInProgress = false;
                    return;
                }

                // Call Livewire to process and verify (no textarea needed)
                @this.call('processScannedQr', decodedText)
                    .then(() => {
                        console.log('Customer verified successfully');
                        this.scanInProgress = false;
                    })
                    .catch((err) => {
                        console.error('Verification error:', err);
                        this.showMessage('Customer verification failed: ' + err.message, 'error');
                        this.scanInProgress = false;
                    });
            },

            showMessage(msg, type) {
                this.message = msg;
                this.messageType = type;

                if (type === 'info') {
                    setTimeout(() => {
                        if (this.messageType === 'info') {
                            this.message = '';
                        }
                    }, 5000);
                }
            }
        }
    }
</script>