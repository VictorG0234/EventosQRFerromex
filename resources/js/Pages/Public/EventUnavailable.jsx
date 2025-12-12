import React from 'react';
import { Head } from '@inertiajs/react';
import { Calendar, XCircle } from 'lucide-react';

export default function EventUnavailable({ eventName, message }) {
    return (
        <>
            <Head title="Evento No Disponible" />
            
            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
                <div className="max-w-md w-full">
                    {/* Card */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                        {/* Header con gradiente */}
                        <div className="bg-gradient-to-r from-red-500 to-red-600 p-8 text-center">
                            <div className="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4 backdrop-blur-sm">
                                <XCircle className="w-12 h-12 text-white" />
                            </div>
                            <h1 className="text-2xl font-bold text-white mb-2">
                                Evento No Disponible
                            </h1>
                        </div>

                        {/* Contenido */}
                        <div className="p-8">
                            <div className="text-center mb-6">
                                <div className="inline-flex items-center justify-center gap-2 text-gray-600 dark:text-gray-400 mb-4">
                                    <Calendar className="w-5 h-5" />
                                    <p className="font-medium">{eventName}</p>
                                </div>
                                
                                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                    <p className="text-gray-700 dark:text-gray-300 text-sm">
                                        {message}
                                    </p>
                                </div>
                            </div>

                            {/* Información adicional */}
                            <div className="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 text-sm text-gray-600 dark:text-gray-400">
                                <p className="text-center">
                                    Este evento ha sido desactivado por el administrador.
                                    Si crees que esto es un error, por favor contacta al organizador del evento.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="text-center mt-6">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Sistema de Gestión de Eventos
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
