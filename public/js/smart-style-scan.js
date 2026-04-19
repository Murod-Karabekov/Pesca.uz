document.addEventListener('DOMContentLoaded', () => {
    const configScript = document.querySelector('script[src*="smart-style-scan.js"][data-analyze-url][data-results-url]');
    const ANALYZE_URL = configScript?.dataset.analyzeUrl || '';
    const RESULTS_URL = configScript?.dataset.resultsUrl || '';

    // Elements
    const stepOccasion = document.getElementById('step-occasion');
    const stepStyle = document.getElementById('step-style');
    const stepSeason = document.getElementById('step-season');
    const stepMeasurements = document.getElementById('step-measurements');
    const stepGender = document.getElementById('step-gender');
    const stepCamera = document.getElementById('step-camera');
    const stepLoading = document.getElementById('step-loading');
    const stepError = document.getElementById('step-error');

    const cameraPreview = document.getElementById('camera-preview');
    const imagePreview = document.getElementById('image-preview');
    const placeholder = document.getElementById('camera-placeholder');
    const cameraButtons = document.getElementById('camera-buttons');
    const captureButtons = document.getElementById('capture-buttons');
    const analyzeButtons = document.getElementById('analyze-buttons');

    const progressBar = document.getElementById('progress-bar');
    const loadingText = document.getElementById('loading-text');
    const errorText = document.getElementById('error-text');

    const occasionButtons = document.querySelectorAll('.occasion-btn');
    const styleButtons = document.querySelectorAll('.style-btn');
    const seasonButtons = document.querySelectorAll('.season-btn');

    const heightInput = document.getElementById('height-cm');
    const shoulderInput = document.getElementById('shoulder-cm');
    const chestInput = document.getElementById('chest-cm');
    const waistInput = document.getElementById('waist-cm');
    const hipInput = document.getElementById('hip-cm');

    const canvas = document.getElementById('processing-canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    let stream = null;
    let capturedImageData = null;
    let selectedGender = null;

    const styleContext = {
        occasion: null,
        occasions: [],
        styleIntent: null,
        styleIntents: [],
        season: null,
        seasons: [],
        heightCm: null,
        shoulderCm: null,
        chestCm: null,
        waistCm: null,
        hipCm: null,
    };

    const selectedOccasions = new Set();
    const selectedStyleIntents = new Set();
    const selectedSeasons = new Set();
    const measureMenuButtons = document.querySelectorAll('.measure-menu-btn');
    const measureStages = document.querySelectorAll('.measure-stage');

    function bindMultiToggle(buttons, stateSet) {
        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const value = btn.dataset.value;
                if (stateSet.has(value)) {
                    stateSet.delete(value);
                    btn.classList.remove('is-selected');
                } else {
                    stateSet.add(value);
                    btn.classList.add('is-selected');
                }
            });
        });
    }

    bindMultiToggle(occasionButtons, selectedOccasions);
    bindMultiToggle(styleButtons, selectedStyleIntents);
    bindMultiToggle(seasonButtons, selectedSeasons);

    function bindMeasureMenu() {
        if (!measureMenuButtons.length || !measureStages.length) {
            return;
        }

        measureMenuButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.target;

                measureMenuButtons.forEach((b) => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                measureStages.forEach((stage) => {
                    if (stage.id === targetId) {
                        stage.classList.remove('hidden');
                        stage.classList.add('is-active');
                    } else {
                        stage.classList.add('hidden');
                        stage.classList.remove('is-active');
                    }
                });
            });
        });
    }

    bindMeasureMenu();

    function goToStep(currentStep, nextStep) {
        currentStep.classList.add('hidden');
        nextStep.classList.remove('hidden');
    }

    // Step Flow
    document.getElementById('btn-next-occasion').addEventListener('click', () => {
        styleContext.occasions = Array.from(selectedOccasions);
        styleContext.occasion = styleContext.occasions[0] || null;
        goToStep(stepOccasion, stepStyle);
    });

    document.getElementById('btn-skip-occasion').addEventListener('click', () => {
        styleContext.occasion = null;
        styleContext.occasions = [];
        selectedOccasions.clear();
        occasionButtons.forEach((btn) => btn.classList.remove('is-selected'));
        goToStep(stepOccasion, stepStyle);
    });

    document.getElementById('btn-next-style').addEventListener('click', () => {
        styleContext.styleIntents = Array.from(selectedStyleIntents);
        styleContext.styleIntent = styleContext.styleIntents[0] || null;
        goToStep(stepStyle, stepSeason);
    });

    document.getElementById('btn-skip-style').addEventListener('click', () => {
        styleContext.styleIntent = null;
        styleContext.styleIntents = [];
        selectedStyleIntents.clear();
        styleButtons.forEach((btn) => btn.classList.remove('is-selected'));
        goToStep(stepStyle, stepSeason);
    });

    document.getElementById('btn-next-season').addEventListener('click', () => {
        styleContext.seasons = Array.from(selectedSeasons);
        styleContext.season = styleContext.seasons[0] || null;
        goToStep(stepSeason, stepMeasurements);
    });

    document.getElementById('btn-skip-season').addEventListener('click', () => {
        styleContext.season = null;
        styleContext.seasons = [];
        selectedSeasons.clear();
        seasonButtons.forEach((btn) => btn.classList.remove('is-selected'));
        goToStep(stepSeason, stepMeasurements);
    });

    document.getElementById('btn-next-measurements').addEventListener('click', () => {
        styleContext.heightCm = heightInput.value || null;
        styleContext.shoulderCm = shoulderInput.value || null;
        styleContext.chestCm = chestInput.value || null;
        styleContext.waistCm = waistInput.value || null;
        styleContext.hipCm = hipInput.value || null;
        goToStep(stepMeasurements, stepCamera);
    });

    document.getElementById('btn-skip-measurements').addEventListener('click', () => {
        styleContext.heightCm = null;
        styleContext.shoulderCm = null;
        styleContext.chestCm = null;
        styleContext.waistCm = null;
        styleContext.hipCm = null;
        goToStep(stepMeasurements, stepCamera);
    });

    // Gender Selection (mandatory, step 1)
    document.querySelectorAll('.gender-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            selectedGender = btn.dataset.gender;
            document.querySelectorAll('.gender-btn').forEach((b) => {
                b.classList.remove('border-app-ink', 'bg-app-canvas', 'ring-2', 'ring-app-ink/10');
                b.classList.add('border-app-hairline', 'bg-white');
            });
            btn.classList.remove('border-app-hairline');
            btn.classList.add('border-app-ink', 'bg-app-canvas', 'ring-2', 'ring-app-ink/10');

            setTimeout(() => {
                goToStep(stepGender, stepOccasion);
            }, 250);
        });
    });

    // Camera
    document.getElementById('btn-camera').addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: 640, height: 640 },
            });
            cameraPreview.srcObject = stream;
            cameraPreview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            cameraButtons.classList.add('hidden');
            captureButtons.classList.remove('hidden');
        } catch (err) {
            alert('Kameraga ruxsat berilmadi. Iltimos, galereyadan rasm yuklang.');
        }
    });

    // Capture photo
    document.getElementById('btn-capture').addEventListener('click', () => {
        canvas.width = cameraPreview.videoWidth;
        canvas.height = cameraPreview.videoHeight;
        ctx.drawImage(cameraPreview, 0, 0);
        capturedImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

        // Show captured image
        imagePreview.src = canvas.toDataURL('image/jpeg');
        imagePreview.classList.remove('hidden');
        cameraPreview.classList.add('hidden');
        stopCamera();

        captureButtons.classList.add('hidden');
        analyzeButtons.classList.remove('hidden');
    });

    // Cancel camera
    document.getElementById('btn-cancel-camera').addEventListener('click', () => {
        stopCamera();
        cameraPreview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        captureButtons.classList.add('hidden');
        cameraButtons.classList.remove('hidden');
    });

    // File upload
    document.getElementById('file-input').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                capturedImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

                imagePreview.src = event.target.result;
                imagePreview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                cameraButtons.classList.add('hidden');
                analyzeButtons.classList.remove('hidden');
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Retake
    document.getElementById('btn-retake').addEventListener('click', () => {
        resetToStart();
    });

    // Retry
    document.getElementById('btn-retry').addEventListener('click', () => {
        errorText.innerHTML = '';
        resetErrorPanel();
        stepError.classList.add('hidden');
        stepOccasion.classList.remove('hidden');
        stepStyle.classList.add('hidden');
        stepSeason.classList.add('hidden');
        stepMeasurements.classList.add('hidden');
        stepGender.classList.add('hidden');
        selectedGender = null;
        selectedOccasions.clear();
        selectedStyleIntents.clear();
        selectedSeasons.clear();
        occasionButtons.forEach((btn) => btn.classList.remove('is-selected'));
        styleButtons.forEach((btn) => btn.classList.remove('is-selected'));
        seasonButtons.forEach((btn) => btn.classList.remove('is-selected'));
        styleContext.occasion = null;
        styleContext.occasions = [];
        styleContext.styleIntent = null;
        styleContext.styleIntents = [];
        styleContext.season = null;
        styleContext.seasons = [];
        styleContext.heightCm = null;
        styleContext.shoulderCm = null;
        styleContext.chestCm = null;
        styleContext.waistCm = null;
        styleContext.hipCm = null;
        document.querySelectorAll('.gender-btn').forEach((b) => {
            b.classList.remove('border-app-ink', 'bg-app-canvas', 'ring-2', 'ring-app-ink/10');
            b.classList.add('border-app-hairline', 'bg-white');
        });
        resetToStart();
    });

    // Analyze
    document.getElementById('btn-analyze').addEventListener('click', async () => {
        if (!ANALYZE_URL || !RESULTS_URL) {
            showError('Konfiguratsiya xatosi: analiz yo\'llari topilmadi.');
            return;
        }

        stepCamera.classList.add('hidden');
        stepLoading.classList.remove('hidden');
        updateProgress(10, 'Model yuklanmoqda...');

        try {
            await tf.setBackend('webgl');
            await tf.ready();

            const model = faceLandmarksDetection.SupportedModels.MediaPipeFaceMesh;
            const detector = await faceLandmarksDetection.createDetector(model, {
                runtime: 'tfjs',
                refineLandmarks: true,
                maxFaces: 1,
            });
            updateProgress(40, 'Yuz aniqlanmoqda...');

            canvas.width = capturedImageData.width;
            canvas.height = capturedImageData.height;
            ctx.putImageData(capturedImageData, 0, 0);

            const predictions = await detector.estimateFaces(canvas);

            if (!predictions || predictions.length === 0) {
                showError('Yuz aniqlanmadi. Iltimos, yuzingiz aniq ko\'rinadigan rasm ishlating.');
                return;
            }

            updateProgress(60, 'Yuz shakli aniqlanmoqda...');
            const face = predictions[0];
            const keypoints = face.keypoints;

            const faceShape = calculateFaceShape(keypoints);
            updateProgress(80, 'Teri rangi aniqlanmoqda...');

            const skinTone = calculateSkinTone(keypoints, capturedImageData);
            updateProgress(95, 'Natijalar saqlanmoqda...');

            const response = await fetch(ANALYZE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    skinTone,
                    faceShape,
                    gender: selectedGender,
                    occasion: styleContext.occasion,
                    occasions: styleContext.occasions,
                    styleIntent: styleContext.styleIntent,
                    styleIntents: styleContext.styleIntents,
                    season: styleContext.season,
                    seasons: styleContext.seasons,
                    heightCm: styleContext.heightCm,
                    shoulderCm: styleContext.shoulderCm,
                    chestCm: styleContext.chestCm,
                    waistCm: styleContext.waistCm,
                    hipCm: styleContext.hipCm,
                }),
            });

            const result = await response.json();

            if (result.success) {
                updateProgress(100, 'Tayyor!');
                setTimeout(() => {
                    window.location.href = RESULTS_URL;
                }, 500);
            } else if (result.code === 'smart_style_monthly_limit') {
                showMonthlyLimitError(result);
            } else {
                showError(result.error || 'Serverda xatolik yuz berdi.');
            }
        } catch (err) {
            console.error('SmartStyle error:', err);
            showError('Tahlil qilishda xatolik: ' + err.message);
        }
    });

    function calculateFaceShape(keypoints) {
        const jawLeft = keypoints[234];
        const jawRight = keypoints[454];
        const chin = keypoints[152];
        const forehead = keypoints[10];
        const cheekLeft = keypoints[123];
        const cheekRight = keypoints[352];
        const foreheadLeft = keypoints[67];
        const foreheadRight = keypoints[297];

        const faceHeight = distance(forehead, chin);
        const jawWidth = distance(jawLeft, jawRight);
        const foreheadWidth = distance(foreheadLeft, foreheadRight);
        const cheekWidth = distance(cheekLeft, cheekRight);

        const ratio = faceHeight / jawWidth;
        const jawToForehead = jawWidth / foreheadWidth;

        if (ratio > 1.6) return 'oblong';
        if (ratio < 1.1 && jawToForehead > 0.9) return 'round';
        if (jawToForehead > 1.05 && ratio < 1.4) return 'square';
        if (foreheadWidth > jawWidth * 1.15) return 'heart';
        if (cheekWidth > foreheadWidth * 1.1 && cheekWidth > jawWidth * 1.1) return 'diamond';
        return 'oval';
    }

    function calculateSkinTone(keypoints, imageData) {
        const data = imageData.data;
        const width = imageData.width;

        const samplePoints = [keypoints[117], keypoints[346], keypoints[187], keypoints[411]];

        let totalR = 0;
        let totalG = 0;
        let totalB = 0;
        let count = 0;

        samplePoints.forEach((point) => {
            const x = Math.round(point.x);
            const y = Math.round(point.y);

            for (let dx = -2; dx <= 2; dx++) {
                for (let dy = -2; dy <= 2; dy++) {
                    const px = x + dx;
                    const py = y + dy;
                    if (px >= 0 && px < width && py >= 0 && py < imageData.height) {
                        const idx = (py * width + px) * 4;
                        totalR += data[idx];
                        totalG += data[idx + 1];
                        totalB += data[idx + 2];
                        count++;
                    }
                }
            }
        });

        const avgR = totalR / count;
        const avgG = totalG / count;
        const avgB = totalB / count;

        const brightness = (avgR * 299 + avgG * 587 + avgB * 114) / 1000;
        const warmth = avgR - avgB;

        if (brightness > 180) return 'light';
        if (brightness > 130) {
            return warmth > 25 ? 'warm_medium' : 'cool_medium';
        }
        return 'dark';
    }

    function distance(a, b) {
        return Math.sqrt(Math.pow(a.x - b.x, 2) + Math.pow(a.y - b.y, 2));
    }

    function updateProgress(percent, text) {
        progressBar.style.width = percent + '%';
        loadingText.textContent = text;
    }

    const errorTitleEl = document.getElementById('error-title');
    const btnTariffs = document.getElementById('btn-tariffs');
    const btnRetry = document.getElementById('btn-retry');

    function resetErrorPanel() {
        if (errorTitleEl) {
            errorTitleEl.textContent = 'Xatolik';
        }
        if (btnRetry) {
            btnRetry.classList.remove('hidden');
        }
        if (btnTariffs) {
            btnTariffs.classList.add('hidden');
        }
    }

    function showError(message) {
        stepLoading.classList.add('hidden');
        stepError.classList.remove('hidden');
        resetErrorPanel();
        errorText.textContent = message;
    }

    function showMonthlyLimitError(result) {
        stepLoading.classList.add('hidden');
        stepError.classList.remove('hidden');
        if (errorTitleEl) {
            errorTitleEl.textContent = 'Limit tugadi';
        }
        errorText.innerHTML = '';
        const msg = document.createElement('p');
        msg.className = 'text-sm text-app-inkMuted mb-4 leading-relaxed';
        msg.textContent = result.error || 'Bu oy uchun SmartStyle limiti tugadi.';
        errorText.appendChild(msg);
        const link = document.createElement('a');
        link.href = result.upgradeUrl || '/hamkorlik';
        link.className = 'btn-primary inline-flex justify-center min-h-[48px] px-5 mb-2 w-full sm:w-auto';
        link.textContent = 'Hamkorlik — tariflar';
        errorText.appendChild(link);
        if (btnRetry) {
            btnRetry.classList.add('hidden');
        }
        if (btnTariffs) {
            btnTariffs.classList.add('hidden');
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            stream = null;
        }
    }

    function resetToStart() {
        imagePreview.classList.add('hidden');
        cameraPreview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        analyzeButtons.classList.add('hidden');
        captureButtons.classList.add('hidden');
        cameraButtons.classList.remove('hidden');
        capturedImageData = null;
        stopCamera();
    }
});
