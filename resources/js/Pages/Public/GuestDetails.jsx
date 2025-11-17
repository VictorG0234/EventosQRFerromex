import { Head, Link } from '@inertiajs/react';
import { 
    CalendarIcon, 
    MapPinIcon, 
    ClockIcon, 
    CheckCircleIcon,
    XCircleIcon,
    QrCodeIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';

export default function GuestDetails({ event, guest, token }) {
    return (
        <>
            <Head title={`${guest.full_name} - ${event.name}`} />
            
            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4">
                <div className="max-w-2xl mx-auto">
                    
                    {/* Botón volver */}
                    <Link
                        href={route('public.event.register', token)}
                        className="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-6"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Volver al inicio
                    </Link>

                    {/* Header con estado */}
                    <div className="text-center mb-8">
                        {guest.has_attended ? (
                            <div className="inline-flex items-center justify-center w-20 h-20 bg-green-500 rounded-full mb-4">
                                <CheckCircleIcon className="w-12 h-12 text-white" />
                            </div>
                        ) : (
                            <div className="inline-flex items-center justify-center w-20 h-20 bg-gray-400 rounded-full mb-4">
                                <XCircleIcon className="w-12 h-12 text-white" />
                            </div>
                        )}
                        
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            ¡Bienvenido, {guest.full_name}!
                        </h1>
                        
                        {guest.has_attended ? (
                            <div className="inline-block bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-4 py-2 rounded-full text-sm font-medium">
                                ✓ Asistencia Registrada
                            </div>
                        ) : (
                            <div className="inline-block bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 px-4 py-2 rounded-full text-sm font-medium">
                                Asistencia Pendiente
                            </div>
                        )}
                    </div>

                    {/* Información del evento */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-6">
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                            Información del Evento
                        </h2>
                        <div className="space-y-3">
                            <div className="flex items-start">
                                <span className="font-medium text-gray-600 dark:text-gray-400 w-32">Evento:</span>
                                <span className="text-gray-900 dark:text-white">{event.name}</span>
                            </div>
                            {event.description && (
                                <div className="flex items-start">
                                    <span className="font-medium text-gray-600 dark:text-gray-400 w-32">Descripción:</span>
                                    <span className="text-gray-900 dark:text-white">{event.description}</span>
                                </div>
                            )}
                            <div className="flex items-start">
                                <CalendarIcon className="h-5 w-5 text-blue-600 dark:text-blue-400 mr-2 mt-0.5" />
                                <span className="text-gray-900 dark:text-white">{event.event_date}</span>
                            </div>
                            {event.start_time && (
                                <div className="flex items-start">
                                    <ClockIcon className="h-5 w-5 text-blue-600 dark:text-blue-400 mr-2 mt-0.5" />
                                    <span className="text-gray-900 dark:text-white">{event.start_time}</span>
                                </div>
                            )}
                            {event.location && (
                                <div className="flex items-start">
                                    <MapPinIcon className="h-5 w-5 text-blue-600 dark:text-blue-400 mr-2 mt-0.5" />
                                    <span className="text-gray-900 dark:text-white">{event.location}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Información del invitado */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-6">
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                            Tu Información
                        </h2>
                        <div className="space-y-3">
                            <div className="flex items-start">
                                <span className="font-medium text-gray-600 dark:text-gray-400 w-32">Nombre:</span>
                                <span className="text-gray-900 dark:text-white">{guest.full_name}</span>
                            </div>
                            <div className="flex items-start">
                                <span className="font-medium text-gray-600 dark:text-gray-400 w-32">Compañía:</span>
                                <span className="text-gray-900 dark:text-white">{guest.compania}</span>
                            </div>
                            <div className="flex items-start">
                                <span className="font-medium text-gray-600 dark:text-gray-400 w-32">No. Empleado:</span>
                                <span className="text-gray-900 dark:text-white">{guest.numero_empleado}</span>
                            </div>
                            {guest.puesto && (
                                <div className="flex items-start">
                                    <span className="font-medium text-gray-600 dark:text-gray-400 w-32">Puesto:</span>
                                    <span className="text-gray-900 dark:text-white">{guest.puesto}</span>
                                </div>
                            )}
                            {guest.localidad && (
                                <div className="flex items-start">
                                    <span className="font-medium text-gray-600 dark:text-gray-400 w-32">Localidad:</span>
                                    <span className="text-gray-900 dark:text-white">{guest.localidad}</span>
                                </div>
                            )}
                            {guest.categoria_rifa && (
                                <div className="flex items-start">
                                    <span className="font-medium text-gray-600 dark:text-gray-400 w-32">Categoría:</span>
                                    <span className="text-gray-900 dark:text-white">{guest.categoria_rifa}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Estado de asistencia */}
                    {guest.has_attended && (
                        <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-8 mb-6">
                            <div className="flex items-start">
                                <CheckCircleIcon className="h-6 w-6 text-green-600 dark:text-green-400 mr-3 mt-1" />
                                <div>
                                    <h3 className="text-lg font-semibold text-green-900 dark:text-green-100 mb-1">
                                        Asistencia Confirmada
                                    </h3>
                                    <p className="text-green-700 dark:text-green-300">
                                        Tu asistencia fue registrada el {guest.attended_at}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Código QR */}
                    {guest.qr_code_url && (
                        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Tu Código QR
                            </h2>
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                Presenta este código en el registro del evento
                            </p>
                            <div className="inline-block bg-white p-6 rounded-xl shadow-lg">
                                <img 
                                    src={guest.qr_code_url} 
                                    alt="Código QR" 
                                    className="w-64 h-64 mx-auto"
                                />
                            </div>
                            <a
                                href={guest.qr_code_url}
                                download={`QR_${guest.numero_empleado}.png`}
                                className="inline-flex items-center mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200"
                            >
                                <QrCodeIcon className="h-5 w-5 mr-2" />
                                Descargar QR
                            </a>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
