import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { CalendarIcon, MapPinIcon, ClockIcon } from '@heroicons/react/24/outline';

export default function EventRegistration({ event, token }) {
    const { data, setData, post, processing, errors } = useForm({
        credentials: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('public.event.validate', token));
    };

    return (
        <>
            <Head title={`Registro - ${event.name}`} />
            
            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
                <div className="w-full max-w-md">
                    {/* Logo o header */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-20 h-20 bg-blue-600 rounded-full mb-4">
                            <svg className="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            {event.name}
                        </h1>
                    </div>

                    {/* Card con información del evento */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-6">
                        <div className="space-y-4 mb-8">
                            {event.description && (
                                <p className="text-gray-600 dark:text-gray-400 text-center">
                                    {event.description}
                                </p>
                            )}
                            
                            <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div className="flex items-center text-gray-700 dark:text-gray-300">
                                    <CalendarIcon className="h-5 w-5 mr-3 text-blue-600 dark:text-blue-400" />
                                    <span>{event.event_date}</span>
                                </div>
                                
                                {event.start_time && (
                                    <div className="flex items-center text-gray-700 dark:text-gray-300">
                                        <ClockIcon className="h-5 w-5 mr-3 text-blue-600 dark:text-blue-400" />
                                        <span>{event.start_time}</span>
                                    </div>
                                )}
                                
                                {event.location && (
                                    <div className="flex items-center text-gray-700 dark:text-gray-300">
                                        <MapPinIcon className="h-5 w-5 mr-3 text-blue-600 dark:text-blue-400" />
                                        <span>{event.location}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Formulario de acceso */}
                        <div className="pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4 text-center">
                                Acceso para Invitados
                            </h2>
                            
                            <form onSubmit={submit}>
                                <div className="mb-4">
                                    <label htmlFor="credentials" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Ingresa tu credencial
                                    </label>
                                    <input
                                        id="credentials"
                                        type="text"
                                        placeholder="Ejemplo: Ferromex-12345"
                                        value={data.credentials}
                                        onChange={(e) => setData('credentials', e.target.value)}
                                        className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-center text-lg"
                                        autoFocus
                                    />
                                    <p className="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                                        Formato: Compañía-NumeroEmpleado
                                    </p>
                                    {errors.credentials && (
                                        <div className="mt-2 text-sm text-red-600 dark:text-red-400 text-center">
                                            {errors.credentials}
                                        </div>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {processing ? 'Verificando...' : 'Acceder'}
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="text-center text-sm text-gray-600 dark:text-gray-400">
                        <p>Sistema de Registro de Eventos</p>
                    </div>
                </div>
            </div>
        </>
    );
}
