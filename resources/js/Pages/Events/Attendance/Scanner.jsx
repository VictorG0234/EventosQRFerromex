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
    const [soundEnabled, setSoundEnabled] = useState(true);
    const [manualMode, setManualMode] = useState(false);
    const [manualEmployeeNumber, setManualEmployeeNumber] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const streamRef = useRef(null);
    const scanIntervalRef = useRef(null);

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
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    facingMode: 'environment', // Cámara trasera preferida
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });
            
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                videoRef.current.play();
            }
            
            setIsScanning(true);
            startScanning();
        } catch (error) {
            console.error('Error accessing camera:', error);
            alert('No se pudo acceder a la cámara. Verifica los permisos.');
        }
    };

    // Detener cámara
    const stopCamera = () => {
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

    // Iniciar escaneo continuo
    const startScanning = () => {
        scanIntervalRef.current = setInterval(() => {
            if (videoRef.current && canvasRef.current && !isProcessing) {
                const video = videoRef.current;
                const canvas = canvasRef.current;
                const context = canvas.getContext('2d');
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Obtener datos de imagen para jsQR
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });
                
                if (code && code.data) {
                    // QR detectado - procesar
                    processScan(code.data);
                }
            }
        }, 300); // Escanear cada 300ms
    };

    // Procesar código QR
    const processScan = async (qrData) => {
        if (isProcessing || !qrData) return;
        
        setIsProcessing(true);
        setLastScan(qrData);
        
        try {
            const response = await axios.post(route('events.attendance.scan', event.id), {
                qr_data: qrData
            });

            const result = response.data;
            
            setScanResult(result);
            
            if (result.success) {
                playSound('success');
                // Actualizar estadísticas
                if (result.statistics) {
                    setStats(prev => ({
                        ...prev,
                        ...result.statistics
                    }));
                }
            } else {
                playSound('error');
            }
            
            // Limpiar resultado después de 5 segundos
            setTimeout(() => {
                setScanResult(null);
                setLastScan(null);
            }, 5000);
            
        } catch (error) {
            console.error('Error processing scan:', error);
            const errorMessage = error.response?.data?.message || 'Error de conexión. Intenta nuevamente.';
            setScanResult({
                success: false,
                message: errorMessage,
                type: 'error'
            });
            playSound('error');
        } finally {
            setIsProcessing(false);
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
                        
                        <Link
                            href={route('events.attendance.index', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Ver Asistencias
                        </Link>
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
                                                <button
                                                    onClick={startCamera}
                                                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                >
                                                    <CameraIcon className="w-4 h-4 mr-2" />
                                                    Iniciar Escáner
                                                </button>
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
                                        {isScanning ? (
                                            <div className="relative">
                                                <video
                                                    ref={videoRef}
                                                    className="w-full max-h-96 object-cover rounded-lg"
                                                    autoPlay
                                                    playsInline
                                                    muted
                                                />
                                                <canvas
                                                    ref={canvasRef}
                                                    className="hidden"
                                                />
                                                
                                                {/* Overlay de escaneo */}
                                                <div className="absolute inset-0 border-4 border-blue-500 rounded-lg pointer-events-none">
                                                    <div className="absolute top-4 left-4 w-8 h-8 border-l-4 border-t-4 border-white"></div>
                                                    <div className="absolute top-4 right-4 w-8 h-8 border-r-4 border-t-4 border-white"></div>
                                                    <div className="absolute bottom-4 left-4 w-8 h-8 border-l-4 border-b-4 border-white"></div>
                                                    <div className="absolute bottom-4 right-4 w-8 h-8 border-r-4 border-b-4 border-white"></div>
                                                </div>
                                                
                                                {/* Línea de escaneo animada */}
                                                <div className="absolute inset-x-0 top-1/2 h-0.5 bg-red-500 shadow-lg animate-pulse"></div>
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
                                        {isScanning && (
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
                                        )}
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
                                        <div className={`mt-4 p-4 rounded-lg border ${
                                            scanResult.success 
                                                ? 'bg-green-50 border-green-200' 
                                                : 'bg-red-50 border-red-200'
                                        }`}>
                                            <div className="flex">
                                                {scanResult.success ? (
                                                    <CheckCircleIcon className="w-5 h-5 text-green-400 mr-2" />
                                                ) : (
                                                    <ExclamationTriangleIcon className="w-5 h-5 text-red-400 mr-2" />
                                                )}
                                                <div className="flex-1">
                                                    <p className={`font-medium ${
                                                        scanResult.success ? 'text-green-800' : 'text-red-800'
                                                    }`}>
                                                        {scanResult.message}
                                                    </p>
                                                    
                                                    {scanResult.guest && (
                                                        <div className="mt-2 text-sm text-gray-700">
                                                            <p><strong>Nombre:</strong> {scanResult.guest.name}</p>
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
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}