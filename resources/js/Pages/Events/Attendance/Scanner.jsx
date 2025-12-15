import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { 
    QrCodeIcon, 
    CameraIcon, 
    UserIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    SpeakerWaveIcon,
    SpeakerXMarkIcon
} from '@heroicons/react/24/outline';
import { useState, useEffect, useRef } from 'react';
import jsQR from 'jsqr';
import axios from 'axios';

export default function Scanner({ auth, event, statistics }) {
    const { flash } = usePage().props;
    const [isScanning, setIsScanning] = useState(false);
    const [lastScan, setLastScan] = useState(null);
    const [lastScanTime, setLastScanTime] = useState(0);
    const [scanResult, setScanResult] = useState(null);
    const [stats, setStats] = useState(statistics);
    const [recentAttendances, setRecentAttendances] = useState(statistics.recent_attendances || []);
    const [soundEnabled, setSoundEnabled] = useState(true);
    const [manualMode, setManualMode] = useState(false);
    const [manualEmployeeNumber, setManualEmployeeNumber] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [showConfirmationDialog, setShowConfirmationDialog] = useState(false);
    const [pendingGuest, setPendingGuest] = useState(null);
    
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const streamRef = useRef(null);
    const scanIntervalRef = useRef(null);
    const animationFrameRef = useRef(null);
    const lastProcessedQrRef = useRef(null);
    const lastProcessedTimeRef = useRef(0);

    // Constante de cooldown: 3 segundos mínimo entre escaneos
    const SCAN_COOLDOWN_MS = 3000;

    // Manejar mensajes flash de Laravel
    useEffect(() => {
        if (flash?.success || flash?.error || flash?.warning) {
            const type = flash.success ? 'success' : (flash.warning ? 'warning' : 'error');
            const message = flash.success || flash.warning || flash.error;
            
            setScanResult({
                success: type === 'success',
                type: type,
                message: message
            });
            
            playSound(type === 'success' ? 'success' : 'error');
            
            // Limpiar el mensaje después de 5 segundos
            setTimeout(() => setScanResult(null), 5000);
        }
    }, [flash]);

    // Configurar axios con el token CSRF
    useEffect(() => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    }, []);

    // Sonidos
    const playSound = (type) => {
        if (!soundEnabled) return;
        
        const context = new AudioContext();
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        
        oscillator.connect(gain);
        gain.connect(context.destination);
        
        if (type === 'success') {
            oscillator.frequency.setValueAtTime(800, context.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(400, context.currentTime + 0.1);
        } else if (type === 'error') {
            oscillator.frequency.setValueAtTime(300, context.currentTime);
        }
        
        gain.gain.setValueAtTime(0.1, context.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.3);
        
        oscillator.start(context.currentTime);
        oscillator.stop(context.currentTime + 0.3);
    };

    // Inicializar cámara
    const startCamera = async () => {
        try {
            // Verificar si la API está disponible
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Tu navegador no soporta acceso a la cámara. Usa un navegador moderno.');
            }

            // Esperar un momento para que React monte los elementos
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const video = videoRef.current;
            const canvas = canvasRef.current;
            
            if (!video || !canvas) {
                console.error('❌ Refs no disponibles:', { video, canvas });
                throw new Error('Elementos video/canvas no disponibles');
            }
            
            console.log('✅ Elementos disponibles:', { video, canvas });

            // Solicitar stream de video
            console.log('🎥 Solicitando acceso a la cámara...');
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });
            
            console.log('✅ Stream obtenido');
            const track = stream.getVideoTracks()[0];
            const settings = track.getSettings();
            console.log('📹 Configuración del stream:', settings);
            
            streamRef.current = stream;
            
            // Configurar video (oculto, solo para obtener frames)
            video.srcObject = stream;
            video.muted = true;
            video.playsInline = true;
            
            // Esperar a que el video tenga metadata
            await new Promise((resolve, reject) => {
                video.onloadedmetadata = () => {
                    console.log('✅ Metadata cargada:', video.videoWidth, 'x', video.videoHeight);
                    resolve();
                };
                video.onerror = reject;
                setTimeout(() => reject(new Error('Timeout esperando metadata')), 5000);
            });
            
            // Iniciar reproducción
            await video.play();
            console.log('✅ Video reproduciendo');
            
            // Configurar canvas con las dimensiones del video
            const width = video.videoWidth || settings.width || 1280;
            const height = video.videoHeight || settings.height || 720;
            
            canvas.width = width;
            canvas.height = height;
            console.log('✅ Canvas configurado:', width, 'x', height);
            
            setIsScanning(true);
            
            // Iniciar bucle de renderizado y escaneo
            startScanning();
            
        } catch (error) {
            console.error('❌ Error al acceder a la cámara:', error);
            
            let errorMessage = 'No se pudo acceder a la cámara. ';
            
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                errorMessage += 'Permisos denegados. Ve a la configuración de tu navegador y permite el acceso a la cámara para este sitio.';
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                errorMessage += 'No se detectó ninguna cámara en tu dispositivo.';
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                errorMessage += 'La cámara está siendo usada por otra aplicación. Cierra otras apps que usen la cámara.';
            } else if (error.name === 'OverconstrainedError') {
                errorMessage += 'No se pudo configurar la cámara con los requisitos solicitados.';
            } else if (error.name === 'SecurityError') {
                errorMessage += 'Verifica que estás usando HTTPS y que el sitio tiene permisos de cámara.';
            } else {
                errorMessage += error.message || 'Error desconocido.';
            }
            
            alert(errorMessage);
            
            // Si falló, mostrar el modo manual
            setManualMode(true);
        }
    };

    // Detener cámara
    const stopCamera = () => {
        console.log('🛑 Deteniendo cámara...');
        
        // Cancelar animación
        if (animationFrameRef.current) {
            cancelAnimationFrame(animationFrameRef.current);
            animationFrameRef.current = null;
        }
        
        if (streamRef.current) {
            streamRef.current.getTracks().forEach(track => track.stop());
            streamRef.current = null;
        }
        
        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
        }
        
        setIsScanning(false);
    };

    // Iniciar escaneo continuo con renderizado
    const startScanning = () => {
        console.log('🔍 Iniciando bucle de renderizado y escaneo...');
        
        const video = videoRef.current;
        const canvas = canvasRef.current;
        
        if (!video || !canvas) {
            console.error('❌ Video o canvas no disponibles');
            return;
        }
        
        const ctx = canvas.getContext('2d');
        let frameCount = 0;
        
        // Función de renderizado que se ejecuta en cada frame
        const tick = () => {
            if (!video || video.paused || video.ended) {
                console.log('⏹️ Video no disponible o detenido');
                return;
            }
            
            try {
                // Dibujar el frame actual del video en el canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Cada 10 frames (~300ms a 60fps), intentar escanear QR
                if (frameCount % 10 === 0 && !isProcessing) {
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: "dontInvert",
                    });
                    
                    if (code && code.data) {
                        console.log('📱 QR detectado:', code.data.substring(0, 50) + '...');
                        processScan(code.data);
                        
                        // Dibujar rectángulo alrededor del QR detectado
                        ctx.strokeStyle = '#00FF00';
                        ctx.lineWidth = 4;
                        ctx.strokeRect(
                            code.location.topLeftCorner.x,
                            code.location.topLeftCorner.y,
                            code.location.bottomRightCorner.x - code.location.topLeftCorner.x,
                            code.location.bottomRightCorner.y - code.location.topLeftCorner.y
                        );
                    }
                }
                
                frameCount++;
            } catch (error) {
                console.error('❌ Error en tick:', error);
            }
            
            // Solicitar el siguiente frame
            animationFrameRef.current = requestAnimationFrame(tick);
        };
        
        // Iniciar el bucle
        tick();
        console.log('✅ Bucle de renderizado iniciado');
    };

    // Procesar código QR con debounce mejorado
    const processScan = async (qrData) => {
        if (isProcessing || !qrData) return;
        
        const now = Date.now();
        
        // PROTECCIÓN 1: Verificar cooldown de tiempo
        if (now - lastProcessedTimeRef.current < SCAN_COOLDOWN_MS) {
            console.log(`⏭️ Cooldown activo. Faltan ${Math.ceil((SCAN_COOLDOWN_MS - (now - lastProcessedTimeRef.current)) / 1000)}s`);
            return;
        }
        
        // PROTECCIÓN 2: Verificar si es el mismo QR
        if (lastProcessedQrRef.current === qrData) {
            console.log('⏭️ QR duplicado ignorado (mismo código)');
            return;
        }
        
        // PROTECCIÓN 3: Verificar si ya está procesando
        if (lastScan === qrData) {
            console.log('⏭️ QR en procesamiento (debounce)');
            return;
        }
        
        // Actualizar referencias ANTES de procesar
        lastProcessedQrRef.current = qrData;
        lastProcessedTimeRef.current = now;
        
        setIsProcessing(true);
        setLastScan(qrData);
        setLastScanTime(now);
        
        console.log(`🔒 QR bloqueado por ${SCAN_COOLDOWN_MS/1000}s:`, qrData.substring(0, 20) + '...');
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await axios.post(route('events.attendance.scan', event.id), {
                qr_data: qrData
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                }
            });

            const result = response.data;
            
            setScanResult(result);
            
            if (result.success) {
                playSound('success');
                
                // DETENER INMEDIATAMENTE el escáner para evitar escaneos duplicados
                stopCamera();
                console.log('🛑 Escáner detenido inmediatamente después de registro exitoso');
                
                // Actualizar estadísticas
                if (result.statistics) {
                    setStats(prev => ({
                        ...prev,
                        ...result.statistics
                    }));
                }
                
                // Agregar nueva asistencia a la lista (al principio)
                if (result.guest) {
                    const newAttendance = {
                        id: Date.now(),
                        guest_name: result.guest.name,
                        employee_number: result.guest.employee_number,
                        work_area: result.guest.work_area,
                        attended_at: new Date().toLocaleTimeString('es-ES'),
                        time_ago: 'Justo ahora',
                        scan_count: result.guest.scan_count || 1,
                        exceeded_limit: result.exceeded_limit || false
                    };
                    setRecentAttendances(prev => [newAttendance, ...prev.slice(0, 9)]);
                }
                
                // Limpiar mensaje después de 5 segundos
                setTimeout(() => {
                    setIsProcessing(false);
                    setScanResult(null);
                    setLastScan(null);
                    console.log('💬 Mensaje limpiado - Usuario puede reactivar escáner manualmente');
                }, 5000);
            } else {
                playSound('error');
                // Si no fue exitoso, pausar y continuar escaneando
                console.log('⏸️ Pausando escaneo por 3 segundos (error)...');
                setTimeout(() => {
                    setIsProcessing(false);
                    setScanResult(null);
                    setLastScan(null);
                    // Resetear refs para permitir nuevo escaneo
                    lastProcessedQrRef.current = null;
                    console.log('▶️ Reanudando escaneo');
                }, 3000);
            }
            
            
        } catch (error) {
            console.error('Error processing scan:', error);
            const errorMessage = error.response?.data?.message || 'Error de conexión. Intenta nuevamente.';
            setScanResult({
                success: false,
                message: errorMessage,
                type: 'error'
            });
            playSound('error');
            // Pausar 5 segundos también en errores de conexión
            console.log('⏸️ Pausando escaneo por 5 segundos (error de conexión)...');
            setTimeout(() => {
                setIsProcessing(false);
                setScanResult(null);
                setLastScan(null);
                console.log('▶️ Reanudando escaneo');
            }, 5000);
        }
    };

    // Registro manual
    const handleManualRegister = async (e) => {
        e.preventDefault();
        if (!manualEmployeeNumber.trim()) return;
        
        setIsProcessing(true);
        setScanResult(null);
        
        try {
            // Buscar invitado por número de empleado
            const response = await axios.get(route('events.guests.find-by-employee', event.id), {
                params: { employee_number: manualEmployeeNumber.trim() }
            });
            
            if (response.data.success && response.data.guest) {
                // Mostrar diálogo de confirmación con los datos del invitado
                setPendingGuest(response.data.guest);
                setShowConfirmationDialog(true);
            } else {
                // Invitado no encontrado
                setScanResult({
                    success: false,
                    type: 'error',
                    message: 'No se encontró ningún invitado con ese número de empleado'
                });
                playSound('error');
            }
        } catch (error) {
            console.error('Error al buscar invitado:', error);
            setScanResult({
                success: false,
                type: 'error',
                message: error.response?.data?.message || 'Error al buscar el invitado'
            });
            playSound('error');
        } finally {
            setIsProcessing(false);
        }
    };

    // Confirmar registro manual
    const confirmManualRegister = () => {
        setIsProcessing(true);
        setShowConfirmationDialog(false);
        
        router.post(
            route('events.attendance.manual', event.id),
            { 
                employee_number: pendingGuest.numero_empleado,
                reason: 'Registro manual desde escáner'
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setManualEmployeeNumber('');
                    setPendingGuest(null);
                },
                onFinish: () => {
                    setIsProcessing(false);
                }
            }
        );
    };

    // Cancelar registro manual
    const cancelManualRegister = () => {
        setShowConfirmationDialog(false);
        setPendingGuest(null);
        setManualEmployeeNumber('');
    };

    // Cleanup al desmontar
    useEffect(() => {
        return () => {
            stopCamera();
        };
    }, []);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-0">
                        <Link
                            href={route('events.show', event.id)}
                            className="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white text-sm sm:mr-4"
                        >
                            ← Volver
                        </Link>
                        <div>
                            <h2 className="font-semibold text-lg sm:text-xl text-gray-800 dark:text-white leading-tight">
                                Escáner QR - {event.name}
                            </h2>
                            <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-300">{event.location}</p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => setSoundEnabled(!soundEnabled)}
                            className={`p-2 rounded-md ${soundEnabled ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500'}`}
                        >
                            {soundEnabled ? <SpeakerWaveIcon className="w-4 h-4 sm:w-5 sm:h-5" /> : <SpeakerXMarkIcon className="w-4 h-4 sm:w-5 sm:h-5" />}
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Escáner QR - ${event.name}`} />

            <div className="py-4 sm:py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        
                        {/* Panel principal del escáner */}
                        <div className="lg:col-span-2">
                            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-4 sm:p-6">
                                    {/* Controles del escáner */}
                                    <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-3">
                                        <h3 className="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100">
                                            Control de Asistencia
                                        </h3>
                                        <div className="flex flex-wrap gap-2">
                                            {!isScanning ? (
                                                <>
                                                    <button
                                                        onClick={async () => {
                                                            // Diagnóstico de cámara
                                                            try {
                                                                const devices = await navigator.mediaDevices.enumerateDevices();
                                                                const cameras = devices.filter(d => d.kind === 'videoinput');
                                                                
                                                                let msg = `🎥 DIAGNÓSTICO DE CÁMARA\n\n`;
                                                                msg += `✅ HTTPS: ${window.location.protocol === 'https:' ? 'Sí' : '❌ NO'}\n`;
                                                                msg += `✅ API disponible: ${navigator.mediaDevices ? 'Sí' : '❌ NO'}\n`;
                                                                msg += `✅ Cámaras detectadas: ${cameras.length}\n\n`;
                                                                
                                                                if (cameras.length > 0) {
                                                                    msg += `Cámaras:\n`;
                                                                    cameras.forEach((cam, i) => {
                                                                        msg += `${i + 1}. ${cam.label || 'Cámara sin nombre'}\n`;
                                                                    });
                                                                } else {
                                                                    msg += `❌ No se detectaron cámaras.\n`;
                                                                    msg += `Verifica que tu dispositivo tenga cámara.`;
                                                                }
                                                                
                                                                alert(msg);
                                                            } catch (error) {
                                                                alert(`Error en diagnóstico: ${error.message}`);
                                                            }
                                                        }}
                                                        className="inline-flex items-center px-2 sm:px-3 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition"
                                                        title="Ver información de cámara"
                                                    >
                                                        ℹ️
                                                    </button>
                                                    <button
                                                        onClick={startCamera}
                                                        className="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                    >
                                                        <CameraIcon className="w-4 h-4 sm:mr-2" />
                                                        <span className="hidden sm:inline">Iniciar Escáner</span>
                                                    </button>
                                                </>
                                            ) : (
                                                <button
                                                    onClick={stopCamera}
                                                    className="inline-flex items-center px-3 sm:px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                >
                                                    <XCircleIcon className="w-4 h-4 sm:mr-2" />
                                                    <span className="hidden sm:inline">Detener</span>
                                                </button>
                                            )}
                                            
                                            <button
                                                onClick={() => setManualMode(!manualMode)}
                                                className="inline-flex items-center px-3 sm:px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150 flex-1 sm:flex-none justify-center"
                                            >
                                                <span className="sm:hidden">Manual</span>
                                                <span className="hidden sm:inline">Registro Manual</span>
                                            </button>
                                        </div>
                                    </div>

                                    {/* Área del escáner */}
                                    <div className="relative">
                                        {/* Video y Canvas - siempre en el DOM */}
                                        <video
                                            ref={videoRef}
                                            style={{ display: 'none' }}
                                            autoPlay
                                            playsInline
                                            muted
                                        />
                                        <canvas
                                            ref={canvasRef}
                                            className="w-full h-auto rounded-lg"
                                            style={{ 
                                                minHeight: window.innerWidth < 768 ? '240px' : '300px',
                                                maxHeight: window.innerWidth < 768 ? '480px' : '600px',
                                                width: '100%',
                                                display: isScanning ? 'block' : 'none',
                                                backgroundColor: '#000'
                                            }}
                                        />
                                        
                                        {isScanning ? (
                                            <div className="relative">
                                                {/* Overlay de escaneo */}
                                                <div className="absolute inset-0 border-4 border-blue-500 rounded-lg pointer-events-none">
                                                    <div className="absolute top-4 left-4 w-8 h-8 border-l-4 border-t-4 border-white"></div>
                                                    <div className="absolute top-4 right-4 w-8 h-8 border-r-4 border-t-4 border-white"></div>
                                                    <div className="absolute bottom-4 left-4 w-8 h-8 border-l-4 border-b-4 border-white"></div>
                                                    <div className="absolute bottom-4 right-4 w-8 h-8 border-r-4 border-b-4 border-white"></div>
                                                </div>
                                                
                                                {/* Línea de escaneo animada */}
                                                <div className="absolute inset-x-0 top-1/2 h-0.5 bg-red-500 shadow-lg animate-pulse"></div>
                                                
                                                {/* Indicador de estado */}
                                                <div className="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full flex items-center">
                                                    <span className="animate-pulse mr-1">●</span>
                                                    Escaneando...
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="flex items-center justify-center h-48 sm:h-64 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                                <div className="text-center px-4">
                                                    <QrCodeIcon className="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400 dark:text-gray-500" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        Escáner QR Detenido
                                                    </h3>
                                                    <p className="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                                        Toca "Iniciar Escáner" para comenzar
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                        
                                        {/* Input manual para simulación */}
                                        {/* {isScanning && (
                                            <div className="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                                <p className="text-sm text-yellow-800 mb-2">
                                                    <strong>Modo Simulación:</strong> Ingresa datos de QR para probar
                                                </p>
                                                <div className="flex space-x-2">
                                                    <input
                                                        type="text"
                                                        placeholder='{"guest_id":1,"event_id":1,"employee_number":"EMP001","full_name":"Juan Pérez","hash":"..."}'
                                                        className="flex-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                                        onKeyDown={(e) => {
                                                            if (e.key === 'Enter' && e.target.value.trim()) {
                                                                processScan(e.target.value.trim());
                                                                e.target.value = '';
                                                            }
                                                        }}
                                                    />
                                                    <button
                                                        onClick={() => {
                                                            const input = document.querySelector('input[placeholder*="guest_id"]');
                                                            if (input && input.value.trim()) {
                                                                processScan(input.value.trim());
                                                                input.value = '';
                                                            }
                                                        }}
                                                        className="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700"
                                                    >
                                                        Simular Escaneo
                                                    </button>
                                                </div>
                                            </div>
                                        )} */}
                                    </div>

                                    {/* Registro manual */}
                                    {manualMode && (
                                        <div className="mt-4 p-3 sm:p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                            <h4 className="text-sm sm:text-md font-medium text-gray-900 dark:text-gray-100 mb-2">
                                                Registro Manual
                                            </h4>
                                            
                                            {/* Mensajes de retroalimentación */}
                                            {scanResult && (
                                                <div className={`mb-3 p-3 rounded-md border ${
                                                    scanResult.success 
                                                        ? 'bg-green-50 border-green-300 text-green-800' 
                                                        : scanResult.type === 'warning'
                                                        ? 'bg-yellow-50 border-yellow-300 text-yellow-800'
                                                        : 'bg-red-50 border-red-300 text-red-800'
                                                }`}>
                                                    <div className="flex items-start">
                                                        {scanResult.success ? (
                                                            <CheckCircleIcon className="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
                                                        ) : (
                                                            <ExclamationTriangleIcon className="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
                                                        )}
                                                        <p className="text-sm font-medium">{scanResult.message}</p>
                                                    </div>
                                                </div>
                                            )}
                                            
                                            <form onSubmit={handleManualRegister} className="flex flex-col sm:flex-row gap-2">
                                                <input
                                                    type="text"
                                                    value={manualEmployeeNumber}
                                                    onChange={(e) => setManualEmployeeNumber(e.target.value)}
                                                    placeholder="Número de empleado"
                                                    className="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    disabled={isProcessing}
                                                />
                                                <button
                                                    type="submit"
                                                    disabled={isProcessing || !manualEmployeeNumber.trim()}
                                                    className="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 disabled:opacity-50 w-full sm:w-auto"
                                                >
                                                    {isProcessing ? 'Registrando...' : 'Registrar'}
                                                </button>
                                            </form>
                                        </div>
                                    )}

                                    {/* Resultado del escaneo */}
                                    {scanResult && (
                                        <div className={`mt-4 p-6 rounded-lg border-2 shadow-lg ${
                                            scanResult.success 
                                                ? 'bg-green-50 border-green-400' 
                                                : 'bg-red-50 border-red-400'
                                        }`}>
                                            <div className="flex items-start">
                                                {scanResult.success ? (
                                                    <CheckCircleIcon className="w-8 h-8 text-green-600 mr-3 flex-shrink-0" />
                                                ) : (
                                                    <ExclamationTriangleIcon className="w-8 h-8 text-red-600 mr-3 flex-shrink-0" />
                                                )}
                                                <div className="flex-1">
                                                    <p className={`font-bold text-lg mb-2 ${
                                                        scanResult.success ? 'text-green-900' : 'text-red-900'
                                                    }`}>
                                                        {scanResult.message}
                                                    </p>
                                                    
                                                    {scanResult.guest && (
                                                        <div className="mt-3 space-y-1 text-sm font-medium text-gray-800">
                                                            <p className="text-base"><strong>Nombre:</strong> {scanResult.guest.name}</p>
                                                            <p><strong>Empleado:</strong> {scanResult.guest.employee_number}</p>
                                                            <p><strong>Área:</strong> {scanResult.guest.work_area}</p>
                                                            {scanResult.guest.attended_at && (
                                                                <p><strong>Registrado:</strong> {scanResult.guest.attended_at}</p>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Panel de estadísticas */}
                        <div className="space-y-4 sm:space-y-6">
                            {/* Stats en tiempo real */}
                            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-4 sm:p-6">
                                    <h3 className="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 mb-3 sm:mb-4">
                                        Estadísticas en Vivo
                                    </h3>
                                    
                                    <div className="space-y-3 sm:space-y-4">
                                        <div className="flex justify-between items-center">
                                            <span className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Total Invitados</span>
                                            <span className="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {stats?.total_guests || 0}
                                            </span>
                                        </div>
                                        
                                        <div className="flex justify-between items-center">
                                            <span className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Asistencias</span>
                                            <span className="text-base sm:text-lg font-semibold text-green-600 dark:text-green-400">
                                                {stats?.total_attendances || 0}
                                            </span>
                                        </div>
                                        
                                        <div className="flex justify-between items-center">
                                            <span className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Porcentaje</span>
                                            <span className="text-base sm:text-lg font-semibold text-blue-600 dark:text-blue-400">
                                                {stats?.attendance_rate || 0}%
                                            </span>
                                        </div>
                                        
                                        <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 sm:h-3">
                                            <div 
                                                className="bg-gradient-to-r from-green-500 to-blue-500 h-2 sm:h-3 rounded-full transition-all duration-500"
                                                style={{ width: `${stats?.attendance_rate || 0}%` }}
                                            ></div>
                                        </div>
                                        
                                        {stats?.latest_scan && (
                                            <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                                                <span className="text-xs text-gray-500 dark:text-gray-500">Último escaneo</span>
                                                <p className="text-xs sm:text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {stats.latest_scan}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Instrucciones */}
                            <div className="bg-blue-50 dark:bg-blue-900/30 overflow-hidden shadow-sm sm:rounded-lg border border-blue-200 dark:border-blue-700">
                                <div className="p-4 sm:p-6">
                                    <h3 className="text-base sm:text-lg font-medium text-blue-900 dark:text-blue-300 mb-2 sm:mb-3">
                                        Instrucciones
                                    </h3>
                                    <div className="space-y-1 sm:space-y-2 text-xs sm:text-sm text-blue-800 dark:text-blue-200">
                                        <p>• Posiciona el código QR frente a la cámara</p>
                                        <p>• El escaneo es automático</p>
                                        <p>• Los sonidos confirman el registro</p>
                                        <p>• Usa registro manual si hay problemas</p>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Últimas Asistencias */}
                            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-4 sm:p-6">
                                    <h3 className="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 mb-3 sm:mb-4">
                                        Últimas Asistencias
                                    </h3>
                                    <div className="space-y-2 sm:space-y-3">
                                        {recentAttendances.length > 0 ? (
                                            recentAttendances.map((attendance, index) => (
                                                <div 
                                                    key={attendance.id || index}
                                                    className={`flex items-start sm:items-center justify-between p-2 sm:p-3 rounded-lg border transition-colors ${
                                                        attendance.exceeded_limit 
                                                            ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700 hover:bg-red-100 dark:hover:bg-red-900/30' 
                                                            : attendance.scan_count === 2
                                                            ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-700 hover:bg-yellow-100 dark:hover:bg-yellow-900/30'
                                                            : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'
                                                    }`}
                                                >
                                                    <div className="flex items-start space-x-2 sm:space-x-3 flex-1 min-w-0">
                                                        <div className="flex-shrink-0 mt-0.5">
                                                            {attendance.exceeded_limit ? (
                                                                <ExclamationTriangleIcon className="w-4 h-4 sm:w-5 sm:h-5 text-red-600" />
                                                            ) : (
                                                                <CheckCircleIcon className={`w-4 h-4 sm:w-5 sm:h-5 ${
                                                                    attendance.scan_count === 2 ? 'text-yellow-600' : 'text-green-500'
                                                                }`} />
                                                            )}
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className={`text-xs sm:text-sm font-medium truncate ${
                                                                attendance.exceeded_limit ? 'text-red-900 dark:text-red-300' : 'text-gray-900 dark:text-gray-100'
                                                            }`}>
                                                                {attendance.guest_name}
                                                                {attendance.scan_count > 1 && (
                                                                    <span className={`ml-1 sm:ml-2 text-[10px] sm:text-xs px-1 sm:px-2 py-0.5 rounded-full whitespace-nowrap ${
                                                                        attendance.exceeded_limit 
                                                                            ? 'bg-red-200 text-red-800' 
                                                                            : 'bg-yellow-200 text-yellow-800'
                                                                    }`}>
                                                                        {attendance.scan_count}×
                                                                    </span>
                                                                )}
                                                            </p>
                                                            <p className="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">
                                                                {attendance.employee_number} • {attendance.work_area}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="text-right flex-shrink-0 ml-2">
                                                        <p className="text-[10px] sm:text-xs text-gray-600 dark:text-gray-400">
                                                            {attendance.attended_at}
                                                        </p>
                                                        <p className="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 hidden sm:block">
                                                            {attendance.time_ago}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="text-center py-6 sm:py-8 text-gray-500 dark:text-gray-400">
                                                <UserIcon className="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                                                <p className="text-xs sm:text-sm">Aún no hay asistencias registradas</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Diálogo de confirmación de registro manual */}
            {showConfirmationDialog && pendingGuest && (
                <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        {/* Fondo oscuro */}
                        <div 
                            className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                            aria-hidden="true"
                            onClick={cancelManualRegister}
                        ></div>

                        {/* Centrado */}
                        <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        {/* Modal */}
                        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                        <UserIcon className="h-6 w-6 text-blue-600 dark:text-blue-400" aria-hidden="true" />
                                    </div>
                                    <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                            Confirmar Registro de Asistencia
                                        </h3>
                                        <div className="mt-4 space-y-3">
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                Por favor, verifica que los datos del invitado sean correctos antes de confirmar:
                                            </p>
                                            <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
                                                <div>
                                                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre:</span>
                                                    <p className="text-base font-semibold text-gray-900 dark:text-gray-100 mt-1">
                                                        {pendingGuest.nombre_completo || `${pendingGuest.nombre} ${pendingGuest.apellidos}`}
                                                    </p>
                                                </div>
                                                <div>
                                                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Compañía:</span>
                                                    <p className="text-base font-semibold text-gray-900 dark:text-gray-100 mt-1">
                                                        {pendingGuest.compania}
                                                    </p>
                                                </div>
                                                <div>
                                                    <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Número de Empleado:</span>
                                                    <p className="text-base font-semibold text-gray-900 dark:text-gray-100 mt-1">
                                                        {pendingGuest.numero_empleado}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                                <button
                                    type="button"
                                    disabled={isProcessing}
                                    onClick={confirmManualRegister}
                                    className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {isProcessing ? 'Registrando...' : 'Confirmar Registro'}
                                </button>
                                <button
                                    type="button"
                                    disabled={isProcessing}
                                    onClick={cancelManualRegister}
                                    className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}