import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
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
    const [isScanning, setIsScanning] = useState(false);
    const [lastScan, setLastScan] = useState(null);
    const [scanResult, setScanResult] = useState(null);
    const [stats, setStats] = useState(statistics);
    const [recentAttendances, setRecentAttendances] = useState(statistics.recent_attendances || []);
    const [soundEnabled, setSoundEnabled] = useState(true);
    const [manualMode, setManualMode] = useState(false);
    const [manualEmployeeNumber, setManualEmployeeNumber] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const streamRef = useRef(null);
    const scanIntervalRef = useRef(null);
    const animationFrameRef = useRef(null);

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

    // Procesar código QR
    const processScan = async (qrData) => {
        if (isProcessing || !qrData) return;
        
        setIsProcessing(true);
        setLastScan(qrData);
        
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
                        id: Date.now(), // Temporal hasta que se recargue
                        guest_name: result.guest.name,
                        employee_number: result.guest.employee_number,
                        work_area: result.guest.work_area,
                        attended_at: new Date().toLocaleTimeString('es-ES'),
                        time_ago: 'Justo ahora'
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
                // Si no fue exitoso, pausar 5 segundos y continuar escaneando
                console.log('⏸️ Pausando escaneo por 5 segundos (error)...');
                setTimeout(() => {
                    setIsProcessing(false);
                    setScanResult(null);
                    setLastScan(null);
                    console.log('▶️ Reanudando escaneo');
                }, 5000);
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
        
        try {
            const response = await fetch(route('events.attendance.manual', event.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ 
                    employee_number: manualEmployeeNumber.trim(),
                    reason: 'QR no funciona'
                })
            });

            if (response.ok) {
                playSound('success');
                setManualEmployeeNumber('');
                setManualMode(false);
                // Refrescar página para ver cambios
                router.reload({ only: ['statistics'] });
            } else {
                const errorData = await response.json();
                alert(errorData.message || 'Error al registrar asistencia');
                playSound('error');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión');
            playSound('error');
        } finally {
            setIsProcessing(false);
        }
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
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <Link
                            href={route('events.show', event.id)}
                            className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            ← Volver al evento
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Escáner QR - {event.name}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300">{event.location}</p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => setSoundEnabled(!soundEnabled)}
                            className={`p-2 rounded-md ${soundEnabled ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500'}`}
                        >
                            {soundEnabled ? <SpeakerWaveIcon className="w-5 h-5" /> : <SpeakerXMarkIcon className="w-5 h-5" />}
                        </button>
                        
                        {/* <Link
                            href={route('events.attendance.index', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Ver Asistencias
                        </Link> */}
                    </div>
                </div>
            }
        >
            <Head title={`Escáner QR - ${event.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {/* Panel principal del escáner */}
                        <div className="lg:col-span-2">
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    {/* Controles del escáner */}
                                    <div className="flex justify-between items-center mb-4">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Control de Asistencia
                                        </h3>
                                        <div className="flex space-x-2">
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
                                                        className="inline-flex items-center px-3 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition"
                                                        title="Ver información de cámara"
                                                    >
                                                        ℹ️
                                                    </button>
                                                    <button
                                                        onClick={startCamera}
                                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                    >
                                                        <CameraIcon className="w-4 h-4 mr-2" />
                                                        Iniciar Escáner
                                                    </button>
                                                </>
                                            ) : (
                                                <button
                                                    onClick={stopCamera}
                                                    className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                >
                                                    <XCircleIcon className="w-4 h-4 mr-2" />
                                                    Detener
                                                </button>
                                            )}
                                            
                                            <button
                                                onClick={() => setManualMode(!manualMode)}
                                                className="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                            >
                                                Registro Manual
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
                                                minHeight: '400px',
                                                maxHeight: '600px',
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
                                            <div className="flex items-center justify-center h-64 bg-gray-100 rounded-lg">
                                                <div className="text-center">
                                                    <QrCodeIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                                                        Escáner QR Detenido
                                                    </h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        Haz clic en "Iniciar Escáner" para comenzar
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
                                        <div className="mt-4 p-4 bg-gray-50 rounded-lg border">
                                            <h4 className="text-md font-medium text-gray-900 mb-2">
                                                Registro Manual
                                            </h4>
                                            <form onSubmit={handleManualRegister} className="flex space-x-2">
                                                <input
                                                    type="text"
                                                    value={manualEmployeeNumber}
                                                    onChange={(e) => setManualEmployeeNumber(e.target.value)}
                                                    placeholder="Número de empleado"
                                                    className="flex-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    disabled={isProcessing}
                                                />
                                                <button
                                                    type="submit"
                                                    disabled={isProcessing || !manualEmployeeNumber.trim()}
                                                    className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 disabled:opacity-50"
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
                        <div className="space-y-6">
                            {/* Stats en tiempo real */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Estadísticas en Vivo
                                    </h3>
                                    
                                    <div className="space-y-4">
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600">Total Invitados</span>
                                            <span className="text-lg font-semibold text-gray-900">
                                                {stats?.total_guests || 0}
                                            </span>
                                        </div>
                                        
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600">Asistencias</span>
                                            <span className="text-lg font-semibold text-green-600">
                                                {stats?.total_attendances || 0}
                                            </span>
                                        </div>
                                        
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600">Porcentaje</span>
                                            <span className="text-lg font-semibold text-blue-600">
                                                {stats?.attendance_rate || 0}%
                                            </span>
                                        </div>
                                        
                                        <div className="w-full bg-gray-200 rounded-full h-3">
                                            <div 
                                                className="bg-gradient-to-r from-green-500 to-blue-500 h-3 rounded-full transition-all duration-500"
                                                style={{ width: `${stats?.attendance_rate || 0}%` }}
                                            ></div>
                                        </div>
                                        
                                        {stats?.latest_scan && (
                                            <div className="pt-2 border-t border-gray-200">
                                                <span className="text-xs text-gray-500">Último escaneo</span>
                                                <p className="text-sm font-medium text-gray-900">
                                                    {stats.latest_scan}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Instrucciones */}
                            <div className="bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg border border-blue-200">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-blue-900 mb-3">
                                        Instrucciones
                                    </h3>
                                    <div className="space-y-2 text-sm text-blue-800">
                                        <p>• Posiciona el código QR frente a la cámara</p>
                                        <p>• El escaneo es automático</p>
                                        <p>• Los sonidos confirman el registro</p>
                                        <p>• Usa registro manual si hay problemas</p>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Últimas Asistencias */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Últimas Asistencias Registradas
                                    </h3>
                                    <div className="space-y-3">
                                        {recentAttendances.length > 0 ? (
                                            recentAttendances.map((attendance, index) => (
                                                <div 
                                                    key={attendance.id || index}
                                                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors"
                                                >
                                                    <div className="flex items-center space-x-3">
                                                        <div className="flex-shrink-0">
                                                            <CheckCircleIcon className="w-5 h-5 text-green-500" />
                                                        </div>
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">
                                                                {attendance.guest_name}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {attendance.employee_number} • {attendance.work_area}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-xs text-gray-600">
                                                            {attendance.attended_at}
                                                        </p>
                                                        <p className="text-xs text-gray-400">
                                                            {attendance.time_ago}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="text-center py-8 text-gray-500">
                                                <UserIcon className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                                <p className="text-sm">Aún no hay asistencias registradas</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}